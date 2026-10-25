<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowTicketAttachmentRequest;
use App\Zammad\AttachmentStore;
use App\Zammad\TicketAttachment;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    /**
     * An attachment of a ticket (PROJ-60): shown in the viewer when it is a JPG, PNG or WebP image,
     * otherwise (and for "Herunterladen") as a download. Only attachments that stand in the messages
     * of this ticket are served.
     */
    public function show(string $number, int $article, int $attachment, ShowTicketAttachmentRequest $request, ZammadClient $zammad, AttachmentStore $store): StreamedResponse|Response|JsonResponse
    {
        try {
            $ticket = $zammad->ticket($number);
        } catch (ZammadException $exception) {
            return $this->problem($exception->problem->message($number), $exception->problem->status());
        }

        $found = collect($ticket->articles)->first(fn ($candidate): bool => $candidate->id === $article);
        $file = collect($found?->attachments ?? [])->first(fn (TicketAttachment $candidate): bool => $candidate->id === $attachment);

        if ($file === null) {
            abort(404);
        }

        if ($file->size > (int) config('analysis.attachments.max_bytes')) {
            return $this->problem('Diese Datei ist zu groß für die Vorschau. Bitte in Zammad öffnen.', 413);
        }

        try {
            $path = $store->path($ticket->id, $article, $attachment);
        } catch (ZammadException $exception) {
            return $this->problem('Der Anhang konnte nicht von Zammad geladen werden. Bitte erneut versuchen.', 502);
        }

        $disk = $store->disk();
        $detected = (string) ($disk->mimeType($path) ?: 'application/octet-stream');
        $inline = ! $request->boolean('download') && $file->isViewable() && in_array($detected, TicketAttachment::VIEWABLE_TYPES, true);

        return $disk->response($path, $this->safeName($file->filename), [
            'Content-Type' => $inline ? $detected : 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
        ], $inline ? 'inline' : 'attachment');
    }

    private function problem(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    private function safeName(string $name): string
    {
        $clean = trim((string) preg_replace('/[\x00-\x1F\x7F\/\\\\"]+/u', '_', basename(str_replace('\\', '/', $name))));

        return $clean === '' ? 'Anhang' : $clean;
    }
}
