<?php

namespace App\Http\Controllers;

use App\Analysis\ProductQuestionLog;
use App\Http\Requests\UpdateProductQuestionRequest;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductQuestionController extends Controller
{
    /**
     * The frequent detail questions about products for admins (PROJ-57).
     */
    public function index(Request $request, ProductQuestionLog $log, TestMode $testMode): View
    {
        abort_unless($testMode->isAvailable($request), 403);

        $status = array_key_exists((string) $request->query('status'), ProductQuestionLog::STATUSES) ? (string) $request->query('status') : 'open';
        $products = $log->products();
        $product = $request->query('produkt');
        $productKey = is_string($product) && array_key_exists($product === 'ohne' ? '' : $product, $products) ? ($product === 'ohne' ? '' : $product) : null;
        $all = $request->boolean('alle');
        $topics = $log->topics($status, $productKey, $all);

        return view('product-questions.index', [
            'status' => $status,
            'topics' => $topics,
            'entries' => $log->entries($topics),
            'counts' => $log->counts($productKey),
            'products' => $products,
            'productKey' => $productKey,
            'all' => $all,
            'threshold' => (int) config('analysis.product_questions.threshold'),
            'months' => (int) config('analysis.product_questions.window_months'),
            'chatText' => fn (array $topic, $topicEntries): string => $log->chatText($topic, $topicEntries),
        ]);
    }

    /**
     * Mark a topic done or ignored, or open it again.
     */
    public function update(UpdateProductQuestionRequest $request, ProductQuestionLog $log, StaffDirectory $staff): RedirectResponse
    {
        $log->mark((string) $request->validated('product_key'), (string) $request->validated('topic'), $request->validated('status'), (string) $staff->current($request));

        return redirect()->route('product-questions.index', array_filter(['status' => $request->input('back_status'), 'produkt' => $request->input('back_product'), 'alle' => $request->input('back_all')]))->with('success', match ($request->validated('status')) {
            'done' => 'Thema als erledigt markiert.',
            'ignored' => 'Thema wird ignoriert.',
            default => 'Thema wieder geöffnet.',
        });
    }
}
