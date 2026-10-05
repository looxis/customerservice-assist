<?php

namespace App\Knowledge;

final readonly class KnowledgeDocument
{
    public ?string $id;

    public ?string $title;

    public ?string $type;

    public ?string $status;

    /**
     * @param  string  $path  Path relative to the knowledge base folder.
     * @param  string|null  $folderType  Document type implied by the folder the file lives in.
     * @param  array<string, mixed>  $frontmatter  Raw frontmatter; when it could not be read, at most a guessed `id`.
     * @param  string  $fingerprint  SHA-256 of the normalised file content.
     */
    public function __construct(
        public string $path,
        public ?string $folderType,
        public array $frontmatter,
        public string $body,
        public string $fingerprint,
        public bool $parsed = true,
    ) {
        $this->id = $this->string('id');
        $this->title = $this->string('title');
        $this->type = $this->string('type');
        $this->status = $this->string('status');
    }

    /**
     * A trimmed, non-empty scalar frontmatter value, or null.
     */
    public function string(string $field): ?string
    {
        $value = $this->frontmatter[$field] ?? null;

        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * A frontmatter list. Empty means "applies to all"; a single value counts as a one-item list.
     *
     * @return list<string>
     */
    public function list(string $field): array
    {
        $value = $this->frontmatter[$field] ?? null;

        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value) ? $value : [$value];

        return array_values(array_map(
            fn (mixed $item): string => trim((string) $item),
            array_filter($items, fn (mixed $item): bool => is_scalar($item) && trim((string) $item) !== ''),
        ));
    }

    /** @return list<string> */
    public function products(): array
    {
        return $this->list('products');
    }

    /** @return list<string> */
    public function categories(): array
    {
        return $this->list('categories');
    }

    /** @return list<string> */
    public function topics(): array
    {
        return $this->list('topics');
    }

    /** @return list<string> */
    public function customerTypes(): array
    {
        return $this->list('customer_types');
    }

    /** @return list<string> */
    public function salesChannels(): array
    {
        return $this->list('sales_channels');
    }

    /** @return list<string> */
    public function relatedKnowledge(): array
    {
        return $this->list('related_knowledge');
    }

    /** @return list<string> */
    public function orderKeywords(): array
    {
        return $this->list('order_keywords');
    }

    /**
     * The product a product file describes: its file name, or the subfolder
     * name when the product is split into several files. Null for other types.
     */
    public function productSlug(): ?string
    {
        if ($this->folderType !== 'product') {
            return null;
        }

        $segments = explode('/', $this->path);

        return count($segments) > 2 ? $segments[1] : pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Products a document is bound to; a product file is always bound to its own product.
     *
     * @return list<string>
     */
    public function boundProducts(): array
    {
        $slug = $this->productSlug();

        return array_values(array_unique($slug === null ? $this->products() : [$slug, ...$this->products()]));
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function length(): int
    {
        return mb_strlen($this->body);
    }

    public function filename(): string
    {
        return basename($this->path);
    }

    public function shortFingerprint(): string
    {
        return substr($this->fingerprint, 0, 12);
    }
}
