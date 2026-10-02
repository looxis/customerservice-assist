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
            ->name('*.md')
            ->exclude($this->config['ignored_folders'])
            ->sortByName();

        foreach ($files as $file) {
            $path = str_replace('\\', '/', $file->getRelativePathname());

            if (in_array($path, $this->config['ignored_files'], true)) {
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

        if (! str_starts_with($content, "---\n")) {
            return $this->unreadable($path, $folderType, $content, 'Das Frontmatter fehlt: Die Datei muss mit einer Zeile "---" beginnen.');
        }

        if (! preg_match('/\A---\n(.*?)^---[ \t]*$\n?(.*)\z/sm', $content, $matches)) {
            return $this->unreadable($path, $folderType, $content, 'Das Frontmatter ist nicht abgeschlossen: Die schließende Zeile "---" fehlt.');
        }

        try {
            $frontmatter = Yaml::parse($matches[1], Yaml::PARSE_DATETIME);
        } catch (ParseException $exception) {
            $line = $exception->getParsedLine() > 0 ? ' (Zeile '.($exception->getParsedLine() + 1).')' : '';

            return $this->unreadable($path, $folderType, $content, "Das Frontmatter ist kein gültiges YAML{$line}. Häufige Ursache: ein Doppelpunkt im Titel ohne Anführungszeichen.");
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
        return [
            new KnowledgeDocument($path, $folderType, [], '', hash('sha256', $content), parsed: false),
            KnowledgeIssue::error($path, $message),
        ];
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
