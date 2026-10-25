<?php

namespace App\Zammad;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * The private cache for attachments that were looked at in the app (PROJ-60). A file is fetched from
 * Zammad once, kept for the retention period and then deleted by attachments:purge.
 */
class AttachmentStore
{
    public function __construct(private readonly ZammadClient $zammad) {}

    /**
     * The path of the cached file; it is fetched from Zammad when it is not in the cache yet.
     *
     * @throws ZammadException
     */
    public function path(int $ticketId, int $articleId, int $attachmentId): string
    {
        $path = "{$ticketId}/{$articleId}-{$attachmentId}";
        $disk = $this->disk();

        if (! $disk->exists($path)) {
            $disk->put($path, $this->zammad->attachmentContent($ticketId, $articleId, $attachmentId));
        }

        return $path;
    }

    /**
     * Deletes the files that are older than the retention period and the folders that are left empty.
     *
     * @return array{files: int, folders: int}
     */
    public function purge(): array
    {
        $disk = $this->disk();
        $before = now()->subDays((int) config('analysis.attachments.retention_days'))->getTimestamp();
        $files = 0;
        $folders = 0;

        foreach ($disk->allFiles() as $file) {
            if ($disk->lastModified($file) < $before) {
                $disk->delete($file);
                $files++;
            }
        }

        foreach ($disk->directories() as $directory) {
            if ($disk->allFiles($directory) === []) {
                $disk->deleteDirectory($directory);
                $folders++;
            }
        }

        return ['files' => $files, 'folders' => $folders];
    }

    public function disk(): Filesystem
    {
        return Storage::disk('ticket-attachments');
    }
}
