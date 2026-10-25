<?php

namespace App\Zammad;

final readonly class TicketAttachment
{
    public function __construct(
        public string $filename,
        public string $contentType,
        public int $size,
        public ?int $id = null,
    ) {}

    /**
     * Image types the browser can show in the viewer (PROJ-60); everything else is only offered as a download.
     */
    public const array VIEWABLE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function isViewable(): bool
    {
        return $this->id !== null && in_array($this->contentType, self::VIEWABLE_TYPES, true);
    }

    /**
     * Kind of file in plain words, e.g. "Bild" or "PDF".
     */
    public function kindLabel(): string
    {
        return match (true) {
            str_starts_with($this->contentType, 'image/') => 'Bild',
            $this->contentType === 'application/pdf' => 'PDF',
            str_starts_with($this->contentType, 'video/') => 'Video',
            str_contains($this->contentType, 'word') || str_contains($this->contentType, 'opendocument.text') => 'Dokument',
            str_contains($this->contentType, 'sheet') || str_contains($this->contentType, 'excel') || $this->contentType === 'text/csv' => 'Tabelle',
            default => 'Datei',
        };
    }

    public function sizeLabel(): string
    {
        return match (true) {
            $this->size >= 1024 * 1024 => number_format($this->size / 1024 / 1024, 1, ',', '.').' MB',
            $this->size >= 1024 => number_format($this->size / 1024, 0, ',', '.').' KB',
            default => $this->size.' Byte',
        };
    }
}
