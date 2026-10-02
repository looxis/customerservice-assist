<?php

namespace App\Knowledge;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class KnowledgeReader
{
    /**
     * @param  array{path: string, ignored_files: list<string>, ignored_folders: list<string>, types: array<string, array{folder: string, prefix: string}>}  $config
     */
    public function __construct(private readonly array $config) {}

    public function exists(): bool
    {
        return is_dir($this->config['path']);
    }

    /**
     * Read every Markdown file in the knowledge base. Unreadable files still
     * yield a document (flagged as not parsed) plus an error issue.
     *
     * @return array{documents: list<KnowledgeDocument>, issues: list<KnowledgeIssue>}
     */
    public function read(): array
    {
        $documents = [];
        $issues = [];

        if (! $this->exists()) {
            return ['documents' => $documents, 'issues' => $issues];
        }

        $files = Finder::create()
            ->files()
            ->in($this->config['path'])
            ->name('/\.(md|markdown)$/i')
            ->sortByName();

        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getRelativePathname());

            if (in_array($path, $this->config['ignored_files'], true) || $this->inIgnoredFolder($path)) {
                continue;
            }

            if ($file->isLink()) {
                $issues[] = KnowledgeIssue::warning($path, 'Symbolische Verknüpfung: Die Datei wird nicht gelesen. Knowledge-Dateien müssen direkt im Knowledge-Ordner liegen.');

                continue;
            }

            [$document, $issue] = $this->readFile($path, $file->getPathname());

            $documents[] = $document;

            if ($issue !== null) {
                $issues[] = $issue;
            }
        }

        return ['documents' => $documents, 'issues' => $issues];
    }

    /**
     * @return array{0: KnowledgeDocument, 1: KnowledgeIssue|null}
     */
    private function readFile(string $path, string $absolutePath): array
    {
        $folderType = $this->folderType($path);
        $raw = @file_get_contents($absolutePath);

        if ($raw === false) {
            return $this->unreadable($path, $folderType, '', 'Die Datei kann nicht gelesen werden (fehlende Leserechte?).');
        }

        if (! mb_check_encoding($raw, 'UTF-8')) {
            return $this->unreadable($path, $folderType, $raw, 'Die Datei ist nicht als UTF-8 gespeichert.');
        }

        $content = $this->normalise($raw);

        // Blank lines before the frontmatter are a common copy-and-paste slip.
        $text = ltrim($content);

        if (str_starts_with($text, '```')) {
            return $this->unreadable($path, $folderType, $content, 'Die Datei beginnt mit einer Code-Markierung (```), die beim Kopieren mitgekommen ist. Bitte die erste und die letzte Zeile mit den Backticks entfernen.');
        }

        if (! str_starts_with($text, "---\n")) {
            return $this->unreadable($path, $folderType, $content, 'Das Frontmatter fehlt: Die Datei muss mit einer Zeile "---" beginnen.');
        }

        if (! preg_match('/\A---\n(.*?)^---[ \t]*$\n?(.*)\z/sm', $text, $matches)) {
            return $this->unreadable($path, $folderType, $content, 'Das Frontmatter ist nicht abgeschlossen: Die schließende Zeile "---" fehlt.');
        }

        try {
            $frontmatter = Yaml::parse($matches[1], Yaml::PARSE_DATETIME);
        } catch (ParseException $exception) {
            $line = $exception->getParsedLine() > 0 ? ' (Zeile '.($exception->getParsedLine() + 1).')' : '';

            $reason = match (true) {
                str_contains($exception->getMessage(), 'Duplicate key') => 'Ein Feld kommt doppelt vor.',
                str_contains($exception->getMessage(), 'tabs') => 'Zum Einrücken werden Tabulatoren verwendet; YAML erlaubt nur Leerzeichen.',
                default => 'Häufige Ursache: ein Doppelpunkt im Titel ohne Anführungszeichen.',
            };

            return $this->unreadable($path, $folderType, $content, "Das Frontmatter ist kein gültiges YAML{$line}. {$reason}");
        }

        if (! is_array($frontmatter) || array_is_list($frontmatter)) {
            return $this->unreadable($path, $folderType, $content, 'Das Frontmatter enthält keine Felder.');
        }

        return [new KnowledgeDocument($path, $folderType, $frontmatter, trim($matches[2]), hash('sha256', $content)), null];
    }

    /**
     * @return array{0: KnowledgeDocument, 1: KnowledgeIssue}
     */
    private function unreadable(string $path, ?string $folderType, string $content, string $message): array
    {
        $id = $this->guessId($path, $content);

        return [
            new KnowledgeDocument($path, $folderType, $id === null ? [] : ['id' => $id], '', hash('sha256', $content), parsed: false),
            KnowledgeIssue::error($path, $message),
        ];
    }

    /**
     * The ID of a file whose frontmatter cannot be read, taken from its "id:"
     * line or its file name, so the ID still counts as assigned.
     */
    private function guessId(string $path, string $content): ?string
    {
        $prefixes = implode('|', array_map(
            fn (array $definition): string => preg_quote($definition['prefix'], '/'),
            $this->config['types'],
        ));

        if (preg_match('/^id:\s*["\']?((?:'.$prefixes.')-\d{3})(?!\d)/mi', $content, $matches)
            || preg_match('/^((?:'.$prefixes.')-\d{3})(?!\d)/i', pathinfo($path, PATHINFO_FILENAME), $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function inIgnoredFolder(string $path): bool
    {
        foreach ($this->config['ignored_folders'] as $folder) {
            if (str_starts_with($path, $folder.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strip the BOM and unify line endings so the same content always yields
     * the same fingerprint, whatever system the file was saved on.
     */
    private function normalise(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        return str_replace(["\r\n", "\r"], "\n", $content);
    }

    /**
     * The document type implied by the folder, e.g. "examples/good/x.md" → "example-good".
     */
    private function folderType(string $path): ?string
    {
        foreach ($this->config['types'] as $type => $definition) {
            if (str_starts_with($path, $definition['folder'].'/')) {
                return $type;
            }
        }

        return null;
    }
}
