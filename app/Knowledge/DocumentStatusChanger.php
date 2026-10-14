<?php

namespace App\Knowledge;

/**
 * Changes the `status` of a knowledge document in its file (PROJ-23): only that one line, written
 * safely, only when the file is still in the state the admin saw. Committing is done by hand.
 */
class DocumentStatusChanger
{
    /** @var array<string, list<string>> */
    private const array TRANSITIONS = [
        'draft' => ['active'],
        'active' => ['draft', 'deprecated'],
        'deprecated' => ['draft'],
    ];

    public const array LABELS = [
        'active' => 'Als gültig bestätigen',
        'draft' => 'Zurück auf Entwurf',
        'deprecated' => 'Als veraltet markieren',
    ];

    public function __construct(private readonly KnowledgeLibrary $library) {}

    /**
     * The statuses the document may be set to from its current one; empty for documents without a clear status.
     *
     * @return list<string>
     */
    public function targets(KnowledgeDocument $document): array
    {
        if (! $document->parsed || $this->inExcludedFolder($document) || $document->id === null) {
            return [];
        }

        return self::TRANSITIONS[(string) $document->status] ?? [];
    }

    /**
     * What stands in the way of setting the status, in words for the admin. Confirming also needs a clean document.
     *
     * @return list<string>
     */
    public function blockers(KnowledgeDocument $document, string $target): array
    {
        $blockers = [];

        if ($target === 'active') {
            if ($this->library->hasErrors($document)) {
                $blockers[] = 'Das Dokument hat Prüfungsfehler.';
            }

            if (preg_match('/^#+\s*Noch zu klären/miu', $document->body) === 1) {
                $blockers[] = 'Die Datei enthält noch einen Abschnitt „Noch zu klären“.';
            }
        }

        if (! $this->writable($document)) {
            $blockers[] = 'Die Datei kann in dieser Umgebung nicht geschrieben werden.';
        }

        return $blockers;
    }

    public function writable(KnowledgeDocument $document): bool
    {
        $file = $this->file($document->path);

        return is_file($file) && ! is_link($file) && is_writable($file) && is_writable(dirname($file));
    }

    /**
     * Related documents that are still drafts (a hint, never a block).
     *
     * @return list<string>
     */
    public function relatedDrafts(KnowledgeDocument $document): array
    {
        return collect($document->list('related_knowledge'))
            ->filter(fn (string $id): bool => $this->library->all()->contains(fn (KnowledgeDocument $other): bool => $other->id === $id && $other->status === 'draft'))
            ->values()
            ->all();
    }

    /**
     * @throws StatusChangeException
     */
    public function change(string $path, string $target, string $fingerprint): KnowledgeDocument
    {
        $this->library->refresh();
        $document = $this->library->all()->first(fn (KnowledgeDocument $candidate): bool => $candidate->path === $path);

        if ($document === null) {
            throw new StatusChangeException('Das Dokument gibt es nicht mehr.');
        }

        if (! hash_equals($document->fingerprint, $fingerprint)) {
            throw new StatusChangeException('Die Datei wurde seit dem Öffnen der Seite verändert. Bitte die Seite neu laden und noch einmal prüfen.');
        }

        if (! in_array($target, $this->targets($document), true)) {
            throw new StatusChangeException('Dieser Statuswechsel ist für das Dokument nicht möglich.');
        }

        if (($blockers = $this->blockers($document, $target)) !== []) {
            throw new StatusChangeException(implode(' ', $blockers));
        }

        $file = $this->file($document->path);
        $content = (string) file_get_contents($file);
        $changed = $this->replaceStatus($content, $target);

        if ($changed === null) {
            throw new StatusChangeException('In der Datei wurde keine eindeutige Zeile „status:“ gefunden.');
        }

        $this->write($file, $changed);
        $this->library->refresh();

        return $this->library->all()->first(fn (KnowledgeDocument $candidate): bool => $candidate->path === $path) ?? $document;
    }

    /**
     * Replace the one `status:` line of the frontmatter, leaving everything else byte for byte.
     */
    public function replaceStatus(string $content, string $target): ?string
    {
        if (preg_match('/\A(?:\xEF\xBB\xBF)?---[ \t]*\R/', $content, $open) !== 1) {
            return null;
        }

        $start = strlen($open[0]);

        if (preg_match('/^---[ \t]*\R?/m', $content, $close, PREG_OFFSET_CAPTURE, $start) !== 1) {
            return null;
        }

        $head = substr($content, $start, $close[0][1] - $start);

        if (preg_match_all('/^status:[^\r\n]*/m', $head) !== 1) {
            return null;
        }

        $head = preg_replace('/^status:[^\r\n]*/m', 'status: '.$target, $head, 1);

        return substr($content, 0, $start).$head.substr($content, $close[0][1]);
    }

    private function inExcludedFolder(KnowledgeDocument $document): bool
    {
        return str_starts_with($document->path, 'templates/') || str_starts_with($document->path, 'examples/');
    }

    private function file(string $path): string
    {
        return rtrim((string) config('knowledge.path'), '/').'/'.$path;
    }

    /**
     * Write to a temporary file next to the original and replace the original at once.
     *
     * @throws StatusChangeException
     */
    private function write(string $file, string $content): void
    {
        $directory = dirname($file);
        $temporary = tempnam($directory, '.status-');

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
