<?php

namespace App\Knowledge;

use Illuminate\Support\Collection;

/**
 * Single source for knowledge documents. Reads and validates the Markdown
 * files once per request or command; no other code reads them directly.
 */
class KnowledgeLibrary
{
    /** @var Collection<int, KnowledgeDocument>|null */
    private ?Collection $documents = null;

    /** @var Collection<int, KnowledgeIssue>|null */
    private ?Collection $issues = null;

    private ?KnowledgeState $state = null;

    /**
     * @param  array<string, mixed>  $config  The "knowledge" configuration.
     */
    public function __construct(private readonly array $config) {}

    /**
     * Every file that was read, including faulty and deprecated documents.
     *
     * @return Collection<int, KnowledgeDocument>
     */
    public function all(): Collection
    {
        $this->load();

        return $this->documents;
    }

    /**
     * Documents the app may apply: free of errors and draft or active.
     *
     * @return Collection<int, KnowledgeDocument>
     */
    public function usable(): Collection
    {
        return $this->all()
            ->filter(fn (KnowledgeDocument $document): bool => ! $this->hasErrors($document)
                && in_array($document->status, $this->config['usable_statuses'], true))
            ->values();
    }

    /**
     * An error-free document by its ID, whatever its status.
     */
    public function find(string $id): ?KnowledgeDocument
    {
        return $this->all()->first(
            fn (KnowledgeDocument $document): bool => $document->id === $id && ! $this->hasErrors($document),
        );
    }

    /**
     * @return Collection<int, KnowledgeIssue>
     */
    public function issues(): Collection
    {
        $this->load();

        return $this->issues;
    }

    /**
     * @return Collection<int, KnowledgeIssue>
     */
    public function errors(): Collection
    {
        return $this->issues()->filter(fn (KnowledgeIssue $issue): bool => $issue->isError())->values();
    }

    /**
     * @return Collection<int, KnowledgeIssue>
     */
    public function warnings(): Collection
    {
        return $this->issues()->reject(fn (KnowledgeIssue $issue): bool => $issue->isError())->values();
    }

    /**
     * Issues of one file, errors first.
     *
     * @return Collection<int, KnowledgeIssue>
     */
    public function issuesFor(KnowledgeDocument|string $document): Collection
    {
        $path = $document instanceof KnowledgeDocument ? $document->path : $document;

        return $this->issues()
            ->filter(fn (KnowledgeIssue $issue): bool => $issue->path === $path)
            ->sortBy(fn (KnowledgeIssue $issue): int => $issue->isError() ? 0 : 1)
            ->values();
    }

    public function hasErrors(KnowledgeDocument $document): bool
    {
        return $this->issuesFor($document)->contains(fn (KnowledgeIssue $issue): bool => $issue->isError());
    }

    public function state(): KnowledgeState
    {
        return $this->state ??= KnowledgeState::detect($this->config['path'], $this->config['commit'] ?? null);
    }

    public function overview(): string
    {
        return (new KnowledgeOverview($this->config))->render($this->all());
    }

    /**
     * Forget what was read so the next call reads the files again.
     */
    public function refresh(): void
    {
        $this->documents = null;
        $this->issues = null;
        $this->state = null;
    }

    private function load(): void
    {
        if ($this->documents !== null) {
            return;
        }

        $reader = new KnowledgeReader($this->config);

        if (! $reader->exists()) {
            $this->documents = collect();
            $this->issues = collect([KnowledgeIssue::error('.', 'Der Knowledge-Ordner fehlt. Es steht kein Unternehmenswissen zur Verfügung.')]);

            return;
        }

        $result = $reader->read();
        $issues = [...$result['issues'], ...(new KnowledgeValidator($this->config))->validate($result['documents'])];

        if ($result['documents'] === []) {
            $issues[] = KnowledgeIssue::warning('.', 'Der Knowledge-Ordner enthält keine Dokumente.');
        }

        $this->documents = collect($result['documents']);
        $this->issues = collect($issues);
    }
}
