<?php

namespace App\Knowledge;

use Carbon\CarbonImmutable;

/**
 * The decision about a document in its trial period (PROJ-56), taken by an admin: keep it for good, extend the trial
 * or end it. Changes only the frontmatter lines concerned, safely and only when the file is still in the state the
 * admin saw. Committing is done by hand.
 */
class TrialChanger
{
    public const array LABELS = [
        'permanent' => 'Bewährt, dauerhaft gültig',
        'extend' => 'Testphase verlängern',
        'end' => 'Beenden (veraltet)',
    ];

    public function __construct(private readonly KnowledgeLibrary $library, private readonly DocumentStatusChanger $status) {}

    /**
     * What the admin may decide for the document; empty when it is no trial.
     *
     * @return list<string>
     */
    public function targets(KnowledgeDocument $document): array
    {
        if (! $document->parsed || $document->id === null || ! $document->isTrial() || str_starts_with($document->path, 'templates/') || str_starts_with($document->path, 'examples/')) {
            return [];
        }

        return array_keys(self::LABELS);
    }

    /**
     * @throws StatusChangeException
     */
    public function change(string $path, string $decision, string $fingerprint, ?string $until = null): KnowledgeDocument
    {
        $this->library->refresh();
        $document = $this->library->all()->first(fn (KnowledgeDocument $candidate): bool => $candidate->path === $path);

        if ($document === null) {
            throw new StatusChangeException('Das Dokument gibt es nicht mehr.');
        }

        if (! hash_equals($document->fingerprint, $fingerprint)) {
            throw new StatusChangeException('Die Datei wurde seit dem Öffnen der Seite verändert. Bitte die Seite neu laden und noch einmal prüfen.');
        }

        if (! in_array($decision, $this->targets($document), true)) {
            throw new StatusChangeException('Diese Entscheidung ist für das Dokument nicht möglich.');
        }

        if (! $this->status->writable($document)) {
            throw new StatusChangeException('Die Datei kann in dieser Umgebung nicht geschrieben werden.');
        }

        $file = rtrim((string) config('knowledge.path'), '/').'/'.$document->path;
        $content = (string) file_get_contents($file);

        $changed = match ($decision) {
            'permanent' => $this->edit($content, remove: ['trial_until', 'trial_goal']),
            'extend' => $this->edit($content, set: ['trial_until' => $this->validUntil($until)]),
            default => $this->edit($content, status: 'deprecated'),
        };

        if ($changed === null) {
            throw new StatusChangeException('In der Datei wurde der Kopfbereich nicht eindeutig gefunden.');
        }

        $this->write($file, $changed);
        $this->library->refresh();

        return $this->library->all()->first(fn (KnowledgeDocument $candidate): bool => $candidate->path === $path) ?? $document;
    }

    /**
     * The new last day: a date in the future, not further than the allowed extension.
     *
     * @throws StatusChangeException
     */
    private function validUntil(?string $until): string
    {
        $date = $until === null ? false : CarbonImmutable::createFromFormat('!Y-m-d', $until, 'Europe/Berlin');

        if ($date === false || $date->format('Y-m-d') !== $until) {
            throw new StatusChangeException('Bitte ein Datum im Format JJJJ-MM-TT angeben.');
        }

        $today = CarbonImmutable::now('Europe/Berlin')->startOfDay();

        if ($date->lessThan($today)) {
            throw new StatusChangeException('Das neue Enddatum muss heute oder später liegen.');
        }

        if ($date->greaterThan($today->addDays((int) config('knowledge.trial.max_extension_days')))) {
            throw new StatusChangeException('Die Testphase kann höchstens um ein Jahr verlängert werden.');
        }

        return $until;
    }

    /**
     * Change lines of the frontmatter and leave everything else byte for byte: remove fields (with their continuation
     * lines), set the value of one field, or set the status.
     *
     * @param  list<string>  $remove
     * @param  array<string, string>  $set
     */
    public function edit(string $content, array $remove = [], array $set = [], ?string $status = null): ?string
    {
        if (preg_match('/\A(?:\xEF\xBB\xBF)?---[ \t]*\R/', $content, $open) !== 1) {
            return null;
        }

        $start = strlen($open[0]);

        if (preg_match('/^---[ \t]*\R?/m', $content, $close, PREG_OFFSET_CAPTURE, $start) !== 1) {
            return null;
        }

        $head = substr($content, $start, $close[0][1] - $start);

        foreach ($remove as $field) {
            $head = preg_replace('/^'.preg_quote($field, '/').':[^\r\n]*(?:\R[ \t]+[^\r\n]*)*\R?/m', '', $head) ?? $head;
        }

        foreach ($set as $field => $value) {
            if (preg_match_all('/^'.preg_quote($field, '/').':[^\r\n]*/m', $head) !== 1) {
                return null;
            }

            $head = preg_replace('/^'.preg_quote($field, '/').':[^\r\n]*/m', $field.': '.$value, $head, 1);
        }

        if ($status !== null) {
            $changed = $this->status->replaceStatus('---'."\n".$head.'---'."\n", $status);

            if ($changed === null) {
                return null;
            }

            $head = substr($changed, 4, -4);
        }

        return substr($content, 0, $start).$head.substr($content, $close[0][1]);
    }

    /**
     * @throws StatusChangeException
     */
    private function write(string $file, string $content): void
    {
        $temporary = tempnam(dirname($file), '.trial-');

        if ($temporary === false) {
            throw new StatusChangeException('Die Datei konnte nicht geschrieben werden.');
        }

        $mode = fileperms($file) & 0777;

        if (file_put_contents($temporary, $content) === false || ! chmod($temporary, $mode) || ! rename($temporary, $file)) {
            @unlink($temporary);

            throw new StatusChangeException('Die Datei konnte nicht geschrieben werden. Sie ist unverändert.');
        }
    }
}
