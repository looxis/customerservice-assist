<?php

namespace App\Zammad;

final readonly class TicketAttachment
{
    public function __construct(
        public string $filename,
        public string $contentType,
        public int $size,
    ) {}

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
