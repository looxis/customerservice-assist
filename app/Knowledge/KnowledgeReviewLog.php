<?php

namespace App\Knowledge;

use App\Analysis\AppStats;
use App\Models\KnowledgeReviewFlag;
use Illuminate\Support\Collection;

/**
 * Reports "possibly outdated, please check" about knowledge documents (PROJ-56): any staff member reports, admins
 * decide. A report never switches a document off.
 */
class KnowledgeReviewLog
{
    public const array REASONS = [
        'not-helping' => 'Hilft nicht / führt nicht zum Ziel',
        'customer-reaction' => 'Kunden reagieren schlecht darauf',
        'rule-outdated' => 'Die Regel stimmt nicht mehr',
        'other' => 'Sonstiges',
    ];

    public function __construct(private readonly KnowledgeLibrary $library) {}

    /**
     * Report a document. A staff member has at most one open report per document: a new one replaces it.
     */
    public function report(string $documentId, string $reason, ?string $comment, string $staff, ?string $analysisUuid, ?string $ticketNumber): ?KnowledgeReviewFlag
    {
        $document = $this->library->find($documentId);

        if ($document === null || ! array_key_exists($reason, self::REASONS)) {
            return null;
        }

        $comment = $comment === null || trim($comment) === '' ? null : trim($comment);

        AppStats::forgetReviewCount();

        $flag = KnowledgeReviewFlag::query()->where('document_id', $documentId)->where('staff_name', $staff)->where('status', 'open')->first();
        $data = ['document_fingerprint' => $document->fingerprint, 'analysis_uuid' => $analysisUuid, 'ticket_number' => $ticketNumber, 'reason' => $reason, 'comment' => $comment];

        if ($flag === null) {
            return KnowledgeReviewFlag::query()->create([...$data, 'document_id' => $documentId, 'staff_name' => $staff, 'status' => 'open']);
        }

        $flag->update($data);
        $flag->touch();

        return $flag;
    }

    /**
     * Open reports per document: how many, by how many people, and whether the document counts as reported
     * several times.
     *
     * @return array<string, array{flags: int, staff: int, many: bool}>
     */
    public function openCounts(): array
    {
        return KnowledgeReviewFlag::query()->where('status', 'open')->get(['document_id', 'staff_name'])
            ->groupBy('document_id')
            ->map(function (Collection $flags): array {
                $count = $flags->count();
                $staff = $flags->pluck('staff_name')->unique()->count();

                return ['flags' => $count, 'staff' => $staff, 'many' => $count >= (int) config('knowledge.review.many_flags') && $staff >= (int) config('knowledge.review.many_staff')];
            })->all();
    }

    public function isReportedMany(string $documentId): bool
    {
        return $this->openCounts()[$documentId]['many'] ?? false;
    }

    /**
     * Documents with open reports for the admin list, most reported first.
     *
     * @return Collection<int, array{id: string, title: string|null, path: string|null, status: string|null, flags: Collection<int, KnowledgeReviewFlag>, staff: int, many: bool, changed: bool}>
     */
    public function openByDocument(): Collection
    {
        return KnowledgeReviewFlag::query()->where('status', 'open')->orderBy('created_at')->get()
            ->groupBy('document_id')
            ->map(function (Collection $flags, string $id): array {
                $document = $this->library->find($id);
                $staff = $flags->pluck('staff_name')->unique()->count();

                return [
                    'id' => $id,
                    'title' => $document?->title,
                    'path' => $document?->path,
                    'status' => $document?->status,
                    'flags' => $flags,
                    'staff' => $staff,
                    'many' => $flags->count() >= (int) config('knowledge.review.many_flags') && $staff >= (int) config('knowledge.review.many_staff'),
                    'changed' => $document !== null && $flags->contains(fn (KnowledgeReviewFlag $flag): bool => $flag->document_fingerprint !== null && $flag->document_fingerprint !== $document->fingerprint),
                ];
            })
            ->sortByDesc(fn (array $row): int => $row['flags']->count())
            ->values();
    }

    public function openDocumentCount(): int
    {
        return KnowledgeReviewFlag::query()->where('status', 'open')->distinct()->count('document_id');
    }

    /**
     * "Checked, stays": close all open reports of the document.
     */
    public function markChecked(string $documentId, string $staff): int
    {
        AppStats::forgetReviewCount();

        return KnowledgeReviewFlag::query()->where('document_id', $documentId)->where('status', 'open')
            ->update(['status' => 'checked', 'resolved_by' => $staff, 'resolved_at' => now()]);
    }

    /**
     * The text for the AI chat: the document and what staff reported about it.
     */
    public function chatText(string $documentId): string
    {
        $document = $this->library->find($documentId);
        $flags = KnowledgeReviewFlag::query()->where('document_id', $documentId)->where('status', 'open')->orderBy('created_at')->get();

        $lines = [
            'Wissensdokument zur Überprüfung aus der Customer Service Assist App – bitte prüfen, ob es überarbeitet, befristet oder auf deprecated gesetzt werden soll, und gegebenenfalls nach dem Authoring Guide ändern.',
            '',
            'Dokument: '.$documentId.' – '.($document?->title ?? '(nicht mehr vorhanden)').($document?->status === null ? '' : ' (Status '.$document->status.')'),
        ];

        foreach ($flags as $flag) {
            $lines[] = '- Meldung von '.$flag->staff_name.' am '.$flag->created_at->setTimezone('Europe/Berlin')->format('d.m.Y').': '.(self::REASONS[$flag->reason] ?? $flag->reason).($flag->comment === null ? '' : ' – '.$flag->comment);
        }

        if ($document !== null) {
            array_push($lines, '', '--- Inhalt der Datei '.$document->path.' ---', $document->body);
        }

        return implode("\n", $lines);
    }

    /**
     * Drop reports after the retention period (comments can contain case details).
     */
    public function purge(): int
    {
        return KnowledgeReviewFlag::query()->where('created_at', '<', now()->subMonths((int) config('analysis.content_retention_months')))->delete();
    }
}
