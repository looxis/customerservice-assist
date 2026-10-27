<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportQuoteBackupRequest;
use App\Http\Requests\SaveQuoteOfferRequest;
use App\Http\Requests\UpdateQuoteArticleCostsRequest;
use App\Models\QuoteArticle;
use App\Models\QuoteOffer;
use App\Quotes\QuoteBackupImporter;
use App\Quotes\QuoteCalculator;
use App\Staff\StaffDirectory;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The page "Angebot berechnen" (PROJ-34): discount calculator on the shop price and offers. Everyone sees
 * articles, costs and offers; only admins change costs.
 */
class QuoteController extends Controller
{
    public function index(Request $request, StaffDirectory $staff): View
    {
        $ticket = (string) $request->query('ticket', '');

        return view('quote.index', [
            'articles' => QuoteArticle::query()->where('active', true)->orderBy('name')->get()->map->payload()->all(),
            'offers' => QuoteOffer::query()->latest('created_on')->latest()->limit(100)->get()->map->payload()->all(),
            'isAdmin' => $staff->isAdmin($staff->current($request)),
            'ticketNumber' => preg_match('/^[0-9]{1,20}$/', $ticket) === 1 ? $ticket : null,
        ]);
    }

    public function store(SaveQuoteOfferRequest $request, StaffDirectory $staff): JsonResponse
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $offer = QuoteOffer::create($this->offerData($request) + [
                    'number' => $this->nextNumber(),
                    'created_on' => now()->toDateString(),
                    'valid_until' => now()->addDays(7)->toDateString(),
                    'created_by' => (string) $staff->current($request),
                ]);

                return response()->json($offer->payload(), 201);
            } catch (UniqueConstraintViolationException) {
                // Another person took the number in the same moment: try the next one.
            }
        }

        return response()->json(['message' => 'Die Angebotsnummer konnte nicht vergeben werden. Bitte noch einmal speichern.'], 409);
    }

    public function update(SaveQuoteOfferRequest $request, QuoteOffer $offer): JsonResponse
    {
        $offer->update($this->offerData($request));

        return response()->json($offer->payload());
    }

    public function destroy(QuoteOffer $offer): JsonResponse
    {
        $offer->delete();

        return response()->json(['deleted' => true]);
    }

    public function print(QuoteOffer $offer): View
    {
        return view('quote.print', ['offer' => $offer, 'totals' => $offer->totals()]);
    }

    public function updateCosts(UpdateQuoteArticleCostsRequest $request, QuoteArticle $article, StaffDirectory $staff): JsonResponse
    {
        $data = $request->validated();
        $rows = array_values(array_map(fn (array $row): array => [
            'id' => (string) ($row['id'] ?? ''),
            'qty' => (float) $row['qty'],
            'unit_cost' => (float) $row['unit_cost'],
        ], $data['raw_articles']));

        $article->update([
            ...collect($data)->except('raw_articles')->all(),
            'raw_articles' => $rows,
            'purchase_cost' => $rows === [] ? null : QuoteCalculator::purchaseCost($rows),
            'costs_updated_by' => (string) $staff->current($request),
        ]);

        return response()->json($article->payload());
    }

    public function import(ImportQuoteBackupRequest $request, QuoteBackupImporter $importer, StaffDirectory $staff): RedirectResponse
    {
        try {
            $counts = $importer->import((string) file_get_contents($request->file('backup')->getRealPath()), (string) $staff->current($request));
        } catch (InvalidArgumentException $e) {
            return redirect()->route('quote.index')->with('error', $e->getMessage());
        }

        return redirect()->route('quote.index')->with('success', "Sicherung eingespielt: {$counts['articles']} Artikel (davon {$counts['with_costs']} mit vollständigen Kosten), {$counts['offers']} Angebote.");
    }

    /**
     * @return array<string, mixed>
     */
    private function offerData(SaveQuoteOfferRequest $request): array
    {
        $data = $request->validated();

        return [
            ...collect($data)->only(['company', 'contact', 'email', 'street', 'city', 'note', 'ticket_number'])->all(),
            'items' => array_values(array_map(fn (array $item): array => [
                'sku' => (string) $item['sku'],
                'name' => (string) $item['name'],
                'qty' => (int) $item['qty'],
                'discount_pct' => (float) $item['discount_pct'],
                'unit_gross' => round((float) $item['unit_gross'], 2),
                'vat' => (float) $item['vat'],
            ], $data['items'])),
        ];
    }

    /**
     * The next number of today: AN-YYYYMMDD-001, -002, …
     */
    private function nextNumber(): string
    {
        $prefix = 'AN-'.now()->format('Ymd').'-';
        $last = DB::table('quote_offers')->where('number', 'like', $prefix.'%')->max('number');

        return $prefix.str_pad((string) (((int) substr((string) $last, -3)) + 1), 3, '0', STR_PAD_LEFT);
    }
}
