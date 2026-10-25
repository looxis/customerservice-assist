<?php

namespace App\Zammad;

/**
 * One file that goes with the reply (PROJ-61). The content is only held for the call to Zammad, never stored.
 */
final readonly class OutgoingAttachment
{
    public function __construct(
        public string $filename,
        public string $mimeType,
        public string $content,
    ) {}

    public function size(): int
    {
        return strlen($this->content);
    }

    /**
     * "name (1,2 MB)", for the log of the reply.
     */
    public function label(): string
    {
        $size = $this->size();

        return $this->filename.' ('.match (true) {
            $size >= 1024 * 1024 => number_format($size / 1024 / 1024, 1, ',', '.').' MB',
            $size >= 1024 => number_format($size / 1024, 0, ',', '.').' KB',
            default => $size.' Byte',
        }.')';
    }
}
