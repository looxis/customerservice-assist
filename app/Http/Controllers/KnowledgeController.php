<?php

namespace App\Http\Controllers;

use App\Http\Requests\KnowledgeIndexRequest;
use App\Knowledge\KnowledgeDocument;
use App\Knowledge\KnowledgeLibrary;
use App\Knowledge\KnowledgeMarkdown;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class KnowledgeController extends Controller
{
    public function index(KnowledgeIndexRequest $request, KnowledgeLibrary $library): View
    {
        $filters = $request->filters();
        $types = config('knowledge.types');
        $documents = $library->all();
        $usable = $library->usable();

        $groups = $this->filter($documents, $filters, $library)
            ->sortBy(fn (KnowledgeDocument $document): string => ($document->id ?? '~').$document->path)
            ->groupBy(fn (KnowledgeDocument $document): string => $this->groupOf($document))
            ->sortBy(fn (Collection $group, string $type): int => array_search($type, array_keys($types), true) === false
                ? PHP_INT_MAX
                : array_search($type, array_keys($types), true));

        return view('knowledge.index', [
            'library' => $library,
            'groups' => $groups,
            'filters' => $filters,
            'types' => $types,
            'statuses' => config('knowledge.statuses'),
            'counts' => [
                'total' => $documents->count(),
                'usable' => $usable->count(),
                'draft' => $usable->where('status', 'draft')->count(),
                'active' => $usable->where('status', 'active')->count(),
            ],
            'issuesByPath' => $library->issues()->groupBy('path'),
            'documentPaths' => $documents->pluck('path')->all(),
        ]);
    }

    public function show(string $path, KnowledgeLibrary $library, KnowledgeMarkdown $markdown): View
    {
        // The path is only ever compared with documents the library has read;
        // it is never used to open a file.
        $document = $library->all()->first(fn (KnowledgeDocument $candidate): bool => $candidate->path === $path);

        abort_if($document === null, 404);

        $links = $library->all()
            ->filter(fn (KnowledgeDocument $candidate): bool => $candidate->id !== null
                && $candidate->path !== $document->path
                && ! $library->hasErrors($candidate))
            ->mapWithKeys(fn (KnowledgeDocument $candidate): array => [
                $candidate->id => route('knowledge.show', ['path' => $candidate->path]),
            ])
            ->all();

        $previous = url()->previous();

        return view('knowledge.show', [
            'document' => $document,
            'issues' => $library->issuesFor($document),
            'usable' => $library->usable()->contains(fn (KnowledgeDocument $candidate): bool => $candidate->path === $document->path),
            'html' => $document->parsed ? $markdown->render($document->body, $links) : null,
            'links' => $links,
            'typeLabel' => config("knowledge.types.{$document->type}.label"),
            'backUrl' => parse_url($previous, PHP_URL_PATH) === parse_url(route('knowledge.index'), PHP_URL_PATH)
                ? $previous
                : route('knowledge.index'),
        ]);
    }

    /**
     * @param  Collection<int, KnowledgeDocument>  $documents
     * @param  array{q?: string, type?: string, status?: string, issues?: string}  $filters
     * @return Collection<int, KnowledgeDocument>
     */
    private function filter(Collection $documents, array $filters, KnowledgeLibrary $library): Collection
    {
        return $documents->filter(function (KnowledgeDocument $document) use ($filters, $library): bool {
            if (isset($filters['type']) && $this->groupOf($document) !== $filters['type']) {
                return false;
            }

            if (isset($filters['status']) && $document->status !== $filters['status']) {
                return false;
            }

            if (isset($filters['issues']) && $library->issuesFor($document)->isEmpty()) {
                return false;
            }

            return ! isset($filters['q'])
                || mb_stripos($document->id.' '.$document->title, $filters['q']) !== false;
        });
    }

    /**
     * The section a document is listed in: its type, or the type of its folder
     * when the type is missing or unknown.
     */
    private function groupOf(KnowledgeDocument $document): string
    {
        return array_key_exists((string) $document->type, config('knowledge.types'))
            ? $document->type
            : ($document->folderType ?? '');
    }
}
