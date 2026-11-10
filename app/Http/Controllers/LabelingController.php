<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExcludeLabelTicketRequest;
use App\Http\Requests\StartLabelRunRequest;
use App\Http\Requests\StoreLabelDecisionRequest;
use App\Http\Requests\UpdateLabelSettingsRequest;
use App\Labeling\LabelCategory;
use App\Labeling\LabelClassifier;
use App\Labeling\LabelingSettings;
use App\Labeling\LabelRunner;
use App\Labeling\LabelStats;
use App\Labeling\LabelTagWriter;
use App\Labeling\ReviewQueue;
use App\Labeling\ReviewRecorder;
use App\Labeling\SenderFilter;
use App\Models\LabelReview;
use App\Models\LabelRun;
use App\Models\LabelTicket;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Zammad\ReplyException;
use App\Zammad\ReplyProblem;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelingController extends Controller
{
    /**
     * Pages of the ticket tagging for training data, for admins (PROJ-78).
     */
    public function index(Request $request, TestMode $testMode, LabelStats $stats, LabelingSettings $settings): View
    {
        $this->authorizeAdmin($request, $testMode);

        return view('labeling.index', [
            'categories' => $stats->categories(),
            'references' => $stats->references(),
            'captured' => LabelTicket::query()->where('state', '!=', LabelTicket::EXCLUDED)->count(),
            'settings' => $settings->all(),
            'model' => LabelClassifier::modelName(),
            'runs' => LabelRun::query()->latest('id')->limit(10)->get()->map(fn (LabelRun $run): array => [
                'id' => $run->id,
                'kind' => LabelRun::KINDS[$run->kind] ?? $run->kind,
                'started' => $run->started_at?->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr',
                'state' => match ($run->state) {
                    'queued', 'running' => 'läuft',
                    'done' => 'fertig',
                    'cancelled' => 'abgebrochen',
                    default => 'fehlgeschlagen',
                },
                'active' => $run->isActive(),
                'counter' => $this->counter($run),
                'admin' => $run->admin,
            ])->all(),
        ]);
    }

    /**
     * "bereich" picks the needed or the optional part of the queues, "kategorie" limits them to one proposed category.
     * With "zurueck" (1 or 2) the page shows the admin's last or second to last decision again, to change it.
     */
    public function review(Request $request, TestMode $testMode, ReviewQueue $reviewQueue, ReviewRecorder $recorder, StaffDirectory $staff, LabelStats $stats): View
    {
        $this->authorizeAdmin($request, $testMode);

        $requested = (string) $request->query('warteschlange');
        $part = array_key_exists((string) $request->query('bereich'), ReviewQueue::PARTS) ? (string) $request->query('bereich') : ($requested === 'unsicher' ? ReviewQueue::OPTIONAL : ReviewQueue::NEEDED);
        $category = LabelCategory::tryFrom((string) $request->query('kategorie'));
        $needs = $stats->needs();
        $queues = $reviewQueue->queues($category, $needs);
        $queue = array_key_exists($requested, $queues[$part]) ? $requested : (collect($queues[$part])->filter(fn (array $info): bool => $info['count'] > 0)->keys()->first() ?? array_key_first($queues[$part]));
        $reviewer = $staff->current($request);
        $recent = $reviewer === null ? collect() : $recorder->recent($reviewer);
        $step = min(max($request->integer('zurueck'), 0), $recent->count());
        $revisit = $step > 0 ? $recent[$step - 1] : null;
        $ticket = $revisit?->labelTicket ?? $reviewQueue->next($queue, $part, $category, $needs);
        $prediction = $revisit?->prediction ?? $ticket?->latestPrediction;

        return view('labeling.review', [
            'queues' => $queues,
            'queue' => $queue,
            'part' => $part,
            'category' => $category,
            'filters' => $this->reviewFilters($queue, $part, $category?->value),
            'step' => $step,
            'recent' => $recent->count(),
            'revisit' => $revisit,
            'ticket' => $ticket === null ? null : [
                'id' => $ticket->id,
                'zammad_id' => $ticket->zammad_id,
                'number' => $ticket->number,
                'created' => $ticket->zammad_created_at?->setTimezone('Europe/Berlin')->format('d.m.Y, H:i').' Uhr',
                'group' => $ticket->group_name ?? '–',
                'test' => $ticket->isTest(),
                'subject' => $ticket->subject,
                'sender' => $ticket->sender,
                'text' => (string) $ticket->text,
                'evidence' => (string) $prediction?->evidence,
                'suggestion' => $prediction?->categoryEnum(),
                'confidence' => (float) $prediction?->confidence,
                'unclear' => (bool) $prediction?->unclear,
                'multiple' => (bool) $prediction?->multiple_concerns,
                'reason' => (string) $prediction?->reason,
                'decision' => $ticket->decision,
                'previous' => $ticket->decisionLabel(),
                'remaining' => $queues[$part][$queue]['count'],
            ],
            'categories' => LabelCategory::cases(),
        ]);
    }

    public function tickets(Request $request, TestMode $testMode): View
    {
        $this->authorizeAdmin($request, $testMode);

        $filters = [
            'kategorie' => (string) $request->query('kategorie'),
            'zuordnung' => (string) $request->query('zuordnung'),
            'entscheidung' => (string) $request->query('entscheidung'),
        ];

        $rows = LabelTicket::query()
            ->where('state', '!=', LabelTicket::EXCLUDED)
            ->with('latestPrediction')
            ->when(LabelCategory::tryFrom($filters['kategorie']) !== null, fn ($query) => $query->whereHas('latestPrediction', fn ($prediction) => $prediction->where('category', $filters['kategorie'])))
            ->when(in_array($filters['zuordnung'], ['training', 'test'], true), fn ($query) => $query->where('split', $filters['zuordnung']))
            ->when($filters['entscheidung'] === 'offen', fn ($query) => $query->whereNull('decision'))
            ->when($filters['entscheidung'] === LabelTicket::UNSURE, fn ($query) => $query->where('decision', LabelTicket::UNSURE))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (LabelTicket $ticket): array => [
                'zammad_id' => $ticket->zammad_id,
                'number' => $ticket->number,
                'group' => $ticket->group_name ?? '–',
                'source' => $ticket->source === 'vorhanden' ? 'vorhanden' : 'Pool',
                'excerpt' => $ticket->text === null ? '(Text gelöscht)' : $ticket->text,
                'suggestion' => $ticket->latestPrediction?->categoryEnum(),
                'confidence' => (float) $ticket->latestPrediction?->confidence,
                'decision' => $ticket->decisionLabel(),
                'unsure' => $ticket->decision === LabelTicket::UNSURE,
                'tag' => $ticket->tag_value === null ? null : ($ticket->tag_kind ?? '–'),
                'tag_value' => $ticket->tag_value,
                'tag_problem' => $ticket->tag_problem,
                'test' => $ticket->isTest(),
            ]);

        return view('labeling.tickets', [
            'filters' => $filters,
            'rows' => $rows,
            'categories' => LabelCategory::cases(),
        ]);
    }

    public function storeRun(StartLabelRunRequest $request, StaffDirectory $staff, LabelRunner $runner, LabelingSettings $settings): RedirectResponse
    {
        if ($request->input('kind') === 'fetch' && $request->has('excluded_groups')) {
            $settings->save(['excluded_groups' => trim((string) $request->input('excluded_groups'))]);
        }

        $classifies = in_array($request->input('kind'), ['classify', 'reclassify'], true);

        $runner->start((string) $request->input('kind'), (string) $staff->current($request), [
            'dry_run' => $request->input('kind') === 'tags' && $request->boolean('dry_run'),
            ...($classifies && $request->filled('limit') ? ['limit' => $request->integer('limit')] : []),
            ...($classifies && $request->filled('from') ? ['from' => (string) $request->validated('from')] : []),
            ...($request->input('kind') === 'reclassify' && $request->filled('category') ? ['category' => (string) $request->validated('category')] : []),
        ]);

        return back()->with('success', 'Der Lauf wurde gestartet.');
    }

    public function cancelRun(Request $request, TestMode $testMode, LabelRunner $runner, LabelRun $run): RedirectResponse
    {
        $this->authorizeAdmin($request, $testMode);
        $runner->cancel($run);

        return back()->with('success', 'Der Lauf wurde abgebrochen.');
    }

    public function release(Request $request, TestMode $testMode, StaffDirectory $staff, LabelStats $stats, string $category): RedirectResponse
    {
        $this->authorizeAdmin($request, $testMode);
        $enum = LabelCategory::tryFrom($category) ?? abort(404);

        return $stats->release($enum, (string) $staff->current($request))
            ? back()->with('success', "„{$enum->label()}“ ist freigegeben: eindeutige Tickets dürfen jetzt ohne Einzelprüfung getaggt werden.")
            : back()->with('error', 'Die Stichprobe reicht noch nicht für die Freigabe.');
    }

    public function storeDecision(StoreLabelDecisionRequest $request, StaffDirectory $staff, ReviewRecorder $recorder, LabelTicket $ticket): RedirectResponse
    {
        $recorder->record($ticket, (string) $request->validated('category'), (string) $staff->current($request));

        return redirect()->route('labeling.review', $this->reviewFilters($request->validated('queue'), $request->validated('bereich'), $request->validated('kategorie')));
    }

    /**
     * Changes one of the admin's last two decisions ("Zurück" on the review page).
     */
    public function amendDecision(StoreLabelDecisionRequest $request, StaffDirectory $staff, ReviewRecorder $recorder, LabelReview $review): RedirectResponse
    {
        $queue = $this->reviewFilters($request->validated('queue'), $request->validated('bereich'), $request->validated('kategorie'));

        if (! $recorder->recent((string) $staff->current($request))->contains('id', $review->id)) {
            return redirect()->route('labeling.review', $queue)->with('error', 'Diese Entscheidung lässt sich nicht mehr ändern. „Zurück“ reicht nur bis zu deinen letzten '.ReviewRecorder::REVISABLE.' Entscheidungen.');
        }

        $recorder->amend($review, (string) $request->validated('category'));

        return redirect()->route('labeling.review', $queue)->with('success', "Die Entscheidung zu Ticket#{$review->labelTicket->number} ist geändert: {$review->labelTicket->decisionLabel()}.");
    }

    /**
     * Takes a ticket out of the ticket tagging for good (test tickets, wrong tickets): its texts and predictions are
     * deleted, a bare marker stays so that later retrievals skip it. With "delete_in_zammad" the ticket is deleted in
     * Zammad first and kept untouched here if that fails.
     */
    public function exclude(ExcludeLabelTicketRequest $request, StaffDirectory $staff, LabelTagWriter $writer, LabelTicket $ticket): RedirectResponse
    {
        $name = (string) $staff->current($request);

        if ($request->boolean('delete_in_zammad')) {
            $token = $staff->zammadToken($name);

            if ($token === null) {
                return back()->with('error', ReplyProblem::NoToken->message($name));
            }

            try {
                $writer->deleteTicket($token, $ticket->zammad_id);
            } catch (ReplyException $exception) {
                return back()->with('error', 'Das Ticket wurde nicht gelöscht und bleibt unverändert hier: '.($exception->problem === ReplyProblem::Forbidden ? "Zammad erlaubt {$name} das Löschen nicht (dafür braucht das Token Admin-Rechte für Tickets)." : $exception->problem->message($name)));
            }
        }

        $ticket->exclude($name);

        return redirect()->route('labeling.review', $this->reviewFilters($request->validated('queue'), $request->validated('bereich'), $request->validated('kategorie')))
            ->with('success', "Ticket#{$ticket->number} ist entfernt und wird nicht mehr abgerufen".($request->boolean('delete_in_zammad') ? ' und wurde in Zammad gelöscht.' : '.'));
    }

    public function updateSettings(UpdateLabelSettingsRequest $request, LabelingSettings $settings, SenderFilter $senders, StaffDirectory $staff): RedirectResponse
    {
        $settings->save($request->validated());

        $removed = $request->has('excluded_senders') ? $senders->removeCaptured('Absenderfilter') : 0;

        return back()->with('success', 'Die Einstellungen sind gespeichert.'.($removed > 0 ? " {$removed} Tickets vom Absenderfilter sind entfernt worden." : ''));
    }

    private function counter(LabelRun $run): string
    {
        return match ($run->kind) {
            'tags' => $run->total === 0 ? "{$run->planned} geplant (Trockenlauf)" : "{$run->done} von {$run->total}, {$run->failed} Fehler",
            default => "{$run->done} von {$run->total}".($run->failed > 0 ? ", {$run->failed} Fehler" : '').(isset($run->options['from']) ? ', ab '.CarbonImmutable::parse($run->options['from'])->format('d.m.Y') : '').(isset($run->options['category']) ? ', nur Vorschläge '.(LabelCategory::tryFrom($run->options['category'])?->label() ?? $run->options['category']) : ''),
        };
    }

    /**
     * The query of the review page that keeps queue, part and category; the needed part is the default and left out.
     *
     * @return array<string, string>
     */
    private function reviewFilters(?string $queue, ?string $part, ?string $category): array
    {
        return array_filter(['warteschlange' => $queue, 'bereich' => $part === ReviewQueue::NEEDED ? null : $part, 'kategorie' => $category]);
    }

    private function authorizeAdmin(Request $request, TestMode $testMode): void
    {
        abort_unless($testMode->isAvailable($request), 403);
    }
}
