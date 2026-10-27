<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisException;
use App\Analysis\AnalysisPanel;
use App\Analysis\AnalysisProblem;
use App\Analysis\AnalysisRequest;
use App\Analysis\AnalysisStore;
use App\Analysis\CaseAnalyzer;
use App\Analysis\ContextVariant;
use App\Analysis\QuestionNumber;
use App\Analysis\RelatedContextBuilder;
use App\Analysis\RelatedSection;
use App\Analysis\ReplyDelivery;
use App\Analysis\Summarizer;
use App\Analysis\Summary;
use App\Analysis\TicketContext;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\EocsOrder;
use App\Http\Requests\AnalyzeTicketRequest;
use App\Http\Requests\DeleteAnalysesRequest;
use App\Http\Requests\LinkReturnLabelRequest;
use App\Http\Requests\MarkReplyCopiedRequest;
use App\Http\Requests\SendReplyRequest;
use App\Http\Requests\UpdateReplyRequest;
use App\Http\Requests\UpdateSummaryRequest;
use App\Knowledge\KnowledgeSelector;
use App\Models\ReturnLabel;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\ProductForge\ProductResolver;
use App\Returns\ReturnInstructions;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Zammad\OrderMentionItem;
use App\Zammad\OutgoingAttachment;
use App\Zammad\OutgoingReply;
use App\Zammad\ReplyException;
use App\Zammad\ReplyProblem;
use App\Zammad\ReplyTarget;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadReplySender;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    /**
     * Run an analysis (stage 2, creating or refreshing the summary first when
     * that variant is chosen) and show the ticket with the result.
     */
    public function analyze(string $number, AnalyzeTicketRequest $request, ZammadClient $zammad, EocsClient $eocs, OrderNumberDetector $detector, CaseAnalyzer $analyzer, Summarizer $summarizer, AnalysisStore $store, StaffDirectory $staff, KnowledgeSelector $selector, TestMode $testMode, ProductResolver $productResolver, RelatedContextBuilder $relatedBuilder): RedirectResponse
    {
        set_time_limit((int) config('analysis.timeout') * 2 + 30);

        $back = fn (string $message, bool $retry = false): RedirectResponse => redirect()
            ->to($this->ticketUrl($number, $request, '#analyse'))
            ->withInput()
            ->with('analysis_error', $message)
            ->with('analysis_retry', $retry);

        try {
            $ticket = $testMode->rewind($zammad->ticket($number), $request, $request->input('stand'))['ticket'];
        } catch (ZammadException $exception) {
            return $back($exception->problem->message($number), $exception->problem->canRetry());
        }

        // Order numbers found in the ticket are loaded from EOCS without being
        // asked for, unless the employee opted out. Whatever EOCS does not
        // deliver still reaches the analysis as a known order number.
        $requested = $this->orderNumbers($request, $detector);
        $found = array_slice($detector->detect($ticket), 0, OrderNumberDetector::MAX_SUGGESTIONS);
        // Looking up an EOCS ID takes several seconds, so those are only loaded when chosen by hand.
        $automatic = array_values(array_filter($found, fn (OrderNumber $order): bool => ! $order->isEocsId()));
        $toLoad = collect($request->boolean('ohne_bestelldetails') ? $requested : [...$requested, ...$automatic])
            ->unique(fn (OrderNumber $order): string => $order->value)->take(OrderNumberDetector::MAX_SUGGESTIONS)->values()->all();
        $orders = [];
        $inputNotes = [];

        try {
            foreach ($eocs->lookup($toLoad) as $lookup) {
                $orders = [...$orders, ...$lookup->orders];

                if (! $lookup->found()) {
                    $inputNotes[] = "Bestellung {$lookup->number->value} wurde in EOCS nicht gefunden; der Vorschlag kennt nur die Nummer.";
                }
            }
        } catch (EocsException $exception) {
            $orders = [];
            $inputNotes[] = 'Die Bestelldetails konnten nicht aus EOCS geladen werden ('.rtrim($exception->problem->message(), '.').'). Der Vorschlag kennt nur die Bestellnummern aus dem Ticket.';
        }

        $loaded = array_map(fn (EocsOrder $order): string => $order->externalNumber, $orders);
        $mentions = collect($ticket->orders)->keyBy('number');
        $mentioned = collect([...$requested, ...$found])
            ->unique(fn (OrderNumber $order): string => $order->value)
            ->reject(fn (OrderNumber $order): bool => in_array($order->value, $loaded, true))
            ->map(fn (OrderNumber $order): array => [
                'number' => $order->value,
                'channel' => $order->format->label(),
                'items' => array_map(fn (OrderMentionItem $item): string => $item->name.($item->asin ? " (ASIN {$item->asin})" : ''), $mentions->get($order->value)?->items ?? []),
            ])->values()->all();
        $shown = array_values(array_unique([...array_map(fn (OrderNumber $order): string => $order->value, $requested), ...array_map(fn (OrderNumber $order): string => $order->value, array_filter($toLoad, fn (OrderNumber $order): bool => in_array($order->value, $loaded, true)))]));

        $store->putCaseChoice($number, $request->validated('kundengruppe'), array_values($request->validated('produkte', [])), (string) $staff->current($request), $request->relatedNumbers());

        if ($request->validated('kundengruppe') !== 'unclear' && $ticket->rewoundTo === null) {
            $store->putCustomerGroup($ticket->customerKey(), $request->validated('kundengruppe'));
        }

        $context = new TicketContext($ticket);
        $variant = in_array($request->variant(), $context->variants(), true) ? $request->variant() : ContextVariant::FullThread;
        $summary = $variant === ContextVariant::LastWithSummary ? $store->summary($ticket->summaryKey()) : null;

        if ($variant === ContextVariant::FullThread && $context->offersVariants() && mb_strlen($context->text($variant)) > (int) config('analysis.max_input_characters')) {
            return $back('Der Verlauf ist zu lang für die KI. Bitte „Letzte Kundennachricht + Zusammenfassung“ wählen – die letzte Kundennachricht wird immer vollständig übertragen.');
        }
        // Product information (PROJ-38): a continuation keeps the data package of the case and only fetches
        // products that are new; every other analysis fetches again and points out changed data.
        $parent = $request->parentAnalysis();
        $latestId = $store->latest($ticket->summaryKey());
        $package = $productResolver->resolve(
            $request->forgeSelection(),
            $parent === null ? null : ((array) ($parent['product_data'] ?? []) ?: null),
            $latestId === null ? null : ($store->result($latestId)['product_data'] ?? null),
            $request->eocsSelection(),
        );
        $unknownProduct = $request->unknownProduct();

        // Earlier course of the tickets that belong to this case (PROJ-52): a continuation keeps what the earlier step used.
        if ($parent !== null) {
            $related = RelatedSection::fromStored($parent['related'] ?? null);
        } else {
            $chosen = [];

            foreach ($ticket->rewoundTo === null ? $request->relatedNumbers() : [] as $relatedNumber) {
                try {
                    $chosen[] = ['ticket' => $zammad->ticket($relatedNumber), 'source' => $request->relatedSource($relatedNumber)];
                } catch (ZammadException) {
                    $inputNotes[] = "Ticket {$relatedNumber} konnte nicht gelesen werden und fehlt im früheren Verlauf.";
                }
            }

            $related = $chosen === [] ? null : $relatedBuilder->build($chosen);
        }

        if ($unknownProduct === null && $request->validated('produkte', []) === [] && ! $request->boolean('kein_produktbezug')) {
            $unknownProduct = $package->displayName();
        }

        // A DHL return label ordered in the form (PROJ-64): the model only learns that one goes with the reply.
        $returnLabel = filled($request->validated('return_label')) ? ReturnLabel::query()->find($request->validated('return_label')) : null;

        if (filled($request->validated('return_label')) && ($returnLabel === null || ! $returnLabel->hasFile() || $returnLabel->ticket_number !== $number)) {
            return $back('Das Retourenlabel gehört nicht zu diesem Ticket oder ist nicht mehr verfügbar. Bitte das Label neu erzeugen oder die Option abwählen.');
        }

        try {
            if ($variant === ContextVariant::LastWithSummary && ($summary === null || $summary->isStale($context->earlierFingerprint()))) {
                if ($summary?->edited) {
                    return $back('Die von Hand geänderte Zusammenfassung ist veraltet, weil neue Nachrichten hinzugekommen sind. Bitte neu erstellen oder weiter verwenden.');
                }

                $summary = $summarizer->create($ticket, $context);
            }

            $id = $analyzer->analyze(new AnalysisRequest(
                ticket: $ticket,
                staffName: (string) $staff->current($request),
                customerGroup: $selector->customerGroup($request->validated('kundengruppe')),
                products: array_values($request->validated('produkte', [])),
                orders: $orders,
                manualOrder: $orders === [] ? $request->manualOrder() : [],
                employeeContext: trim((string) $request->validated('kontext', '').($returnLabel === null ? '' : "\n".ReturnInstructions::fact())),
                variant: $variant,
                summary: $summary,
                formInput: [...$request->safe()->only(['kundengruppe', 'produkte', 'kontext', 'return_label', 'kein_produktbezug', 'produkt_ohne_wissen', 'forge_produkte', 'eocs_produkte', 'forge_sku', 'related', 'related_set', 'related_source', 'related_manual', ...array_keys(AnalyzeTicketRequest::MANUAL_ORDER_FIELDS)]), 'variante' => $variant->value],
                mentionedOrders: $mentioned,
                notes: $inputNotes,
                extraKnowledge: array_values(array_unique($request->validated('zusatzwissen', []))),
                decisions: $request->decisions((string) $staff->current($request)) ?? [],
                unknownProduct: $unknownProduct,
                productPackage: $package,
                related: $related,
                claimReferences: array_values(array_merge(...array_map(fn (OrderNumber $number): array => array_map(fn (int $claim): array => ['claim' => $claim, 'order' => $number->value], $number->claims), $found) ?: [[]])),
            ));
        } catch (AnalysisException $exception) {
            return $back($exception->problem->message(), $exception->problem !== AnalysisProblem::Misconfigured);
        }

        return redirect()->to($this->ticketUrl($number, $request, '#ergebnis', ['analyse' => $id, 'bestellungen' => $shown]))->withInput();
    }

    /**
     * Create the summary of the earlier thread (stage 1), or create it anew.
     */
    public function createSummary(string $number, Request $request, ZammadClient $zammad, Summarizer $summarizer, TestMode $testMode): RedirectResponse
    {
        set_time_limit((int) config('analysis.timeout') + 30);

        try {
            $ticket = $testMode->rewind($zammad->ticket($number), $request, $request->input('stand'))['ticket'];
            $context = new TicketContext($ticket);

            if (! $context->offersVariants()) {
                return redirect()->to($this->ticketUrl($number, $request, '#analyse'));
            }

            $summarizer->create($ticket, $context);
        } catch (ZammadException $exception) {
            return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'))->with('summary_error', $exception->problem->message($number));
        } catch (AnalysisException $exception) {
            return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'))->with('summary_error', 'Die Zusammenfassung konnte nicht erstellt werden. '.$exception->problem->message().' Du kannst auch eine andere Variante wählen.');
        }

        return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'));
    }

    /**
     * Keep the employee's corrected summary. A summary of an older thread can
     * also be kept on purpose ("weiter verwenden").
     */
    public function updateSummary(string $number, UpdateSummaryRequest $request, ZammadClient $zammad, AnalysisStore $store, TestMode $testMode): RedirectResponse
    {
        try {
            $ticket = $testMode->rewind($zammad->ticket($number), $request, $request->input('stand'))['ticket'];
        } catch (ZammadException) {
            $ticket = null;
        }

        $key = $ticket?->summaryKey() ?? $number;
        $summary = $store->summary($key);

        if ($summary === null) {
            return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'))->with('summary_error', 'Es gibt noch keine Zusammenfassung zum Bearbeiten.');
        }

        $updated = $summary->withText($request->validated('zusammenfassung'));

        if ($ticket !== null) {
            $fingerprint = (new TicketContext($ticket))->earlierFingerprint();
            $updated = new Summary($updated->text, $updated->until, $fingerprint, true, $updated->model, $updated->promptVersion, $updated->createdAt);
        }
        // Without the ticket the old fingerprint stays; the summary may show as outdated.

        $store->putSummary($key, $updated);

        return redirect()->to($this->ticketUrl($number, $request, '#zusammenfassung'))->with('summary_saved', true);
    }

    /**
     * Save the edited reply draft of an analysis in the background (PROJ-10),
     * or go back to the draft of the language model.
     */
    public function updateReply(string $number, string $analysis, UpdateReplyRequest $request, AnalysisStore $store, StaffDirectory $staff): JsonResponse
    {
        $stored = $store->result($analysis);

        if ($stored === null || ($stored['ticket'] ?? null) !== $number) {
            return response()->json(['message' => 'Dieser Vorschlag ist nicht mehr verfügbar.'], 404);
        }

        if ($store->latest((string) $stored['scope']) !== $analysis) {
            return response()->json(['message' => 'Ältere Vorschläge sind nur lesbar.'], 409);
        }

        if (! is_array($stored['result']['reply'] ?? null)) {
            return response()->json(['message' => 'Zu diesem Vorschlag gibt es noch keinen Antwortentwurf.'], 409);
        }

        $now = CarbonImmutable::now();
        $edit = $request->boolean('original')
            ? null
            : ['text' => (string) $request->validated('text', ''), 'staff' => (string) $staff->current($request), 'at' => $now->toIso8601String()];

        $stored = $store->changeResult($analysis, fn (array $content): array => [...$content, 'reply_edit' => $edit]) ?? $stored;
        $store->markReplyEdited($analysis, $edit === null ? null : $now);

        return response()->json([
            'saved' => true,
            'edited' => $stored['reply_edit'] === null ? null : 'bearbeitet von '.$stored['reply_edit']['staff'].' am '.$now->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr',
        ]);
    }

    /**
     * Ties a return label that was ordered after the proposal to it (PROJ-64): the steps for the customer and the PDF
     * then come back whenever the proposal is opened.
     */
    public function linkReturnLabel(string $number, string $analysis, LinkReturnLabelRequest $request, AnalysisStore $store): JsonResponse
    {
        $stored = $store->result($analysis);
        $label = ReturnLabel::query()->find($request->validated('label'));

        if ($stored === null || ($stored['ticket'] ?? null) !== $number || $label === null || ! $label->hasFile() || $label->ticket_number !== $number) {
            return response()->json(['message' => 'Dieses Label gehört nicht zu diesem Vorschlag.'], 404);
        }

        $store->changeResult($analysis, fn (array $content): array => [...$content, 'inputs' => [...(array) ($content['inputs'] ?? []), 'return_label' => $label->id]]);

        return response()->json(['linked' => true]);
    }

    /**
     * Hand the reply to Zammad in the name of the chosen employee (PROJ-19). Counts for the statistics like a copy.
     */
    public function sendReply(string $number, string $analysis, SendReplyRequest $request, AnalysisStore $store, AnalysisPanel $panel, StaffDirectory $staff, ZammadClient $zammad, ZammadReplySender $sender, ReplyDelivery $delivery): JsonResponse
    {
        $stored = $store->result($analysis);
        $name = $staff->current($request);

        if ($stored === null || ($stored['ticket'] ?? null) !== $number) {
            return response()->json(['message' => 'Dieser Vorschlag ist nicht mehr verfügbar.'], 404);
        }

        $sending = $store->replySending($analysis);

        if ($sending === null || $sending['test'] || $store->latest((string) $stored['scope']) !== $analysis || ! is_array($stored['result']['reply'] ?? null)) {
            return response()->json(['message' => 'Dieser Vorschlag kann nicht gesendet werden (Testlauf, älterer Vorschlag oder ohne Antwortentwurf).'], 409);
        }

        if ($sending['sent_at'] !== null) {
            return response()->json(['message' => 'Diese Antwort wurde bereits gesendet.', 'code' => 'sent'], 409);
        }

        if ($name === null) {
            return response()->json(['message' => 'Bitte zuerst deinen Namen wählen.'], 409);
        }

        $token = $staff->zammadToken($name);

        if ($token === null) {
            return response()->json(['message' => ReplyProblem::NoToken->message($name), 'code' => 'no_token'], 409);
        }

        if ($store->ruleCheckBlocksCopy($analysis)) {
            return response()->json(['message' => 'Die Regelprüfung ist unvollständig und noch nicht bestätigt.'], 409);
        }

        try {
            $ticket = $zammad->ticket($number);
        } catch (ZammadException $exception) {
            return response()->json(['message' => $exception->problem->message($number).' Die Antwort wurde nicht gesendet.'], 502);
        }

        try {
            if ($sending['started'] !== null) {
                if ($sending['started']->diffInSeconds(now()) < 90) {
                    return response()->json(['message' => 'Der Versand läuft gerade. Bitte kurz warten.', 'code' => 'busy'], 409);
                }

                $earlier = $sender->sentSince($token, $ticket->id, $sending['started']);

                if ($earlier !== null) {
                    $store->markReplySent($analysis, $name, $earlier->zammadUser, (string) $request->validated('subject'), 'unbekannt', $earlier->articleId, $this->replyChanged($request, $panel, $stored));

                    return response()->json(['message' => 'Die Antwort war bereits in Zammad angekommen.', 'code' => 'sent'], 409);
                }

                $store->releaseReplySend($analysis);
            }
        } catch (ReplyException $exception) {
            return response()->json(['message' => $exception->problem->message($name)], 502);
        }

        // What the suggestion knew: the newest public message of the ticket at the time (older suggestions: the last one considered).
        $lastSeen = $stored['thread']['newest_article_id'] ?? $stored['thread']['last_article_id'] ?? null;
        $newer = ReplyTarget::newerThan($ticket, is_numeric($lastSeen) ? (int) $lastSeen : null);

        if ($newer !== [] && ! $request->boolean('confirm_newer')) {
            $latest = $newer[array_key_last($newer)];

            return response()->json([
                'message' => 'Seit dem Vorschlag gibt es eine neue Nachricht von '.$latest->senderName.' ('.$latest->createdAt->format('d.m.Y, H:i').' Uhr). Bitte lesen und entscheiden, ob die Antwort noch passt.',
                'code' => 'newer',
            ], 409);
        }

        $target = ReplyTarget::for($ticket);

        if ($target === null) {
            return response()->json(['message' => 'Zu diesem Ticket gibt es keine eindeutige Antwortadresse. Bitte in Zammad antworten.'], 409);
        }

        if (! $store->claimReplySend($analysis)) {
            return response()->json(['message' => 'Diese Antwort wird gerade gesendet oder wurde bereits gesendet.', 'code' => 'busy'], 409);
        }

        $after = $request->validated('after');
        $days = (int) $request->validated('days', 0);
        $state = $after === 'close' ? 'closed' : 'pending reminder';
        $subject = trim((string) $request->validated('subject'));
        $body = trim((string) $request->validated('text'));
        $signature = trim((string) $request->validated('signature', ''));

        $files = $this->outgoingFiles($request);

        try {
            $sent = $sender->send($token, new OutgoingReply($ticket->id, $target->to, $subject, $signature === '' ? $body : $body."\n\n".$signature, $target->inReplyTo, $state, $after === 'close' ? null : ReplyDelivery::reminderDate($days), $files));
        } catch (ReplyException $exception) {
            if ($exception->problem->notSent()) {
                $store->releaseReplySend($analysis);
            }

            return response()->json(['message' => $exception->problem->message($name), 'code' => $exception->problem->value], 502);
        }

        $store->markReplySent($analysis, $name, $sent->zammadUser, $subject, $after === 'close' ? 'closed' : "pending reminder +{$days}d", $sent->articleId, $this->replyChanged($request, $panel, $stored), $files === [] ? null : implode(', ', array_map(fn (OutgoingAttachment $file): string => $file->label(), $files)));

        return response()->json(['sent' => true, 'by' => $name, 'at' => now('Europe/Berlin')->format('d.m.Y \\u\\m H:i'), 'ticket_url' => $ticket->zammadUrl]);
    }

    /**
     * The files that go with the reply (PROJ-61), read from the upload: the type comes from the content, the name is
     * cleaned and made unique. The files are not stored.
     *
     * @return list<OutgoingAttachment>
     */
    private function outgoingFiles(SendReplyRequest $request): array
    {
        $files = [];
        $used = [];

        foreach ($request->file('files', []) as $file) {
            $name = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\"<>:*?|]+/u', '_', basename(str_replace('\\', '/', $file->getClientOriginalName()))));
            $name = $name === '' ? 'Anhang' : $name;
            $base = pathinfo($name, PATHINFO_FILENAME);
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $candidate = $name;

            for ($number = 2; in_array(mb_strtolower($candidate), $used, true); $number++) {
                $candidate = $base.'-'.$number.($extension === '' ? '' : '.'.$extension);
            }

            $used[] = mb_strtolower($candidate);
            $files[] = new OutgoingAttachment($candidate, (string) $file->getMimeType(), (string) file_get_contents($file->getRealPath()));
        }

        return $files;
    }

    /**
     * Whether the text to send (without the signature) differs from the AI's original.
     *
     * @param  array<string, mixed>  $stored
     */
    private function replyChanged(SendReplyRequest $request, AnalysisPanel $panel, array $stored): bool
    {
        return trim((string) $request->validated('text')) !== trim($panel->originalReply($stored));
    }

    /**
     * Note that the reply draft was copied, and whether it differs from the AI's original (PROJ-35).
     */
    public function markCopied(string $number, string $analysis, MarkReplyCopiedRequest $request, AnalysisStore $store, AnalysisPanel $panel, StaffDirectory $staff): JsonResponse
    {
        $stored = $store->result($analysis);

        if ($stored === null || ($stored['ticket'] ?? null) !== $number) {
            return response()->json(['message' => 'Dieser Vorschlag ist nicht mehr verfügbar.'], 404);
        }

        if ($store->latest((string) $stored['scope']) !== $analysis) {
            return response()->json(['message' => 'Ältere Vorschläge sind nur lesbar.'], 409);
        }

        if (! is_array($stored['result']['reply'] ?? null)) {
            return response()->json(['message' => 'Zu diesem Vorschlag gibt es noch keinen Antwortentwurf.'], 409);
        }

        if ($store->ruleCheckBlocksCopy($analysis)) {
            return response()->json(['message' => 'Die Regelprüfung ist unvollständig und noch nicht bestätigt.'], 409);
        }

        $store->markReplyCopied($analysis, (string) $staff->current($request), trim((string) $request->validated('text', '')) !== trim($panel->originalReply($stored)));

        return response()->json(['saved' => true]);
    }

    /**
     * Confirm that the binding rules were checked by hand after an incomplete rule check (PROJ-36).
     */
    public function acknowledgeRuleCheck(string $number, string $analysis, Request $request, AnalysisStore $store, StaffDirectory $staff): RedirectResponse
    {
        $done = $store->acknowledgeRuleCheck($analysis, $number, (string) $staff->current($request));

        $url = QuestionNumber::isQuestion($number) ? route('questions.show', ['number' => $number, 'analyse' => $analysis]).'#ergebnis' : $this->ticketUrl($number, $request, '#ergebnis', ['analyse' => $analysis]);

        return redirect()->to($url)
            ->with($done ? 'success' : 'error', $done ? 'Danke – die Regelprüfung ist als von dir geprüft vermerkt, der Entwurf kann übernommen werden.' : 'Diese Regelprüfung gibt es nicht oder sie ist schon bestätigt.');
    }

    /**
     * Empty all customer content of a ticket's analyses (admin, PROJ-11).
     */
    public function destroyAll(string $number, DeleteAnalysesRequest $request, AnalysisStore $store, StaffDirectory $staff): RedirectResponse
    {
        $store->deleteTicket($number, (string) $staff->current($request));

        return redirect()->route('tickets.show', ['number' => $number])->with('success', 'Alle Analysen dieses Tickets wurden gelöscht.');
    }

    /**
     * @return list<OrderNumber>
     */
    private function orderNumbers(Request $request, OrderNumberDetector $detector): array
    {
        $numbers = [];

        foreach ((array) $request->input('bestellungen', []) as $value) {
            $number = is_string($value) ? $detector->normalize($value) : null;

            if ($number !== null) {
                $numbers[$number->value] ??= $number;
            }
        }

        return array_slice(array_values($numbers), 0, OrderNumberDetector::MAX_SUGGESTIONS);
    }

    /**
     * @param  array<string, string>  $extra
     */
    private function ticketUrl(string $number, Request $request, string $anchor, array $extra = []): string
    {
        $selection = array_values(array_filter((array) $request->input('bestellungen', []), 'is_string'));
        $stand = $request->input('stand');

        return route('tickets.show', ['number' => $number, 'bestellungen' => $selection, ...(is_string($stand) && $stand !== '' ? ['stand' => $stand] : []), ...$extra]).$anchor;
    }
}
