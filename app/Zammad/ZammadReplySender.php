<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The only place where the app writes to Zammad (PROJ-19): it hands one reply, with the new status, to a
 * ticket in the name of the employee whose personal token is used. The token is only ever part of the
 * request header; nothing about it is stored, logged or put into a message.
 */
class ZammadReplySender
{
    /**
     * @param  array{url: ?string, timeout: int|string}  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * Who the token belongs to; proves the token works before anything is sent.
     *
     * @throws ReplyException
     */
    public function whoAmI(string $token): array
    {
        $response = $this->call($token, fn (PendingRequest $request): Response => $request->get('users/me'), certain: true);
        $name = trim(trim((string) ($response->json('firstname') ?? '')).' '.trim((string) ($response->json('lastname') ?? '')));

        return ['id' => (int) $response->json('id'), 'name' => $name !== '' ? $name : (string) ($response->json('login') ?? 'Zammad-Benutzer')];
    }

    /**
     * Change the ticket and add the reply in one call, so reply and status never get apart.
     *
     * @throws ReplyException
     */
    public function send(string $token, OutgoingReply $reply): SentReply
    {
        $me = $this->whoAmI($token);
        $started = CarbonImmutable::now();

        $payload = [
            'state' => $reply->state,
            'article' => [
                'type' => 'email',
                'sender' => 'Agent',
                'internal' => false,
                'content_type' => 'text/plain',
                'to' => $reply->to,
                'subject' => $reply->subject,
                'body' => $reply->body,
                ...($reply->inReplyTo === null ? [] : ['in_reply_to' => $reply->inReplyTo]),
                ...($reply->attachments === [] ? [] : ['attachments' => array_map(fn (OutgoingAttachment $file): array => [
                    'filename' => $file->filename,
                    'data' => base64_encode($file->content),
                    'mime-type' => $file->mimeType,
                ], $reply->attachments)]),
            ],
        ];

        if ($reply->pendingUntil !== null) {
            $payload['pending_time'] = $reply->pendingUntil->utc()->toIso8601String();
        }

        $this->call($token, fn (PendingRequest $request): Response => $request->put("tickets/{$reply->ticketId}", $payload), certain: false, timeout: $reply->attachments === [] ? null : (int) ($this->config['reply_attachment_timeout'] ?? 120));

        return new SentReply($this->newestArticleBy($token, $reply->ticketId, $me['id'], $started->subMinute()), $me['name']);
    }

    /**
     * Create a new ticket for the customer with the e-mail as its first article, in the name of the employee
     * (PROJ-64). The customer is found or created by the address.
     *
     * @throws ReplyException
     */
    public function createTicket(string $token, OutgoingTicket $ticket): CreatedTicket
    {
        $this->whoAmI($token);

        $payload = [
            'title' => $ticket->subject,
            'group' => $ticket->group,
            'customer_id' => 'guess:'.$ticket->to,
            'state' => $ticket->state,
            'article' => [
                'type' => 'email',
                'sender' => 'Agent',
                'internal' => false,
                'content_type' => 'text/plain',
                'to' => $ticket->to,
                'subject' => $ticket->subject,
                'body' => $ticket->body,
                ...($ticket->attachments === [] ? [] : ['attachments' => array_map(fn (OutgoingAttachment $file): array => [
                    'filename' => $file->filename,
                    'data' => base64_encode($file->content),
                    'mime-type' => $file->mimeType,
                ], $ticket->attachments)]),
            ],
        ];

        if ($ticket->pendingUntil !== null) {
            $payload['pending_time'] = $ticket->pendingUntil->utc()->toIso8601String();
        }

        $response = $this->call($token, fn (PendingRequest $request): Response => $request->post('tickets', $payload), certain: false, timeout: $ticket->attachments === [] ? null : (int) ($this->config['reply_attachment_timeout'] ?? 120));

        $id = (int) $response->json('id');
        $number = (string) $response->json('number');

        if ($id <= 0 || $number === '') {
            throw new ReplyException(ReplyProblem::Unclear);
        }

        return new CreatedTicket($id, $number, rtrim((string) $this->config['url'], '/').'/#ticket/zoom/'.$id);
    }

    /**
     * Whether this user already wrote a public reply to the ticket since the given time: after an unclear
     * outcome this decides whether sending again is safe.
     *
     * @throws ReplyException
     */
    public function sentSince(string $token, int $ticketId, CarbonImmutable $since): ?SentReply
    {
        $me = $this->whoAmI($token);
        $articleId = $this->newestArticleBy($token, $ticketId, $me['id'], $since);

        return $articleId === null ? null : new SentReply($articleId, $me['name']);
    }

    private function newestArticleBy(string $token, int $ticketId, int $userId, CarbonImmutable $since): ?int
    {
        try {
            $articles = $this->call($token, fn (PendingRequest $request): Response => $request->get("ticket_articles/by_ticket/{$ticketId}"), certain: true)->json();
        } catch (ReplyException) {
            return null;
        }

        $found = collect(is_array($articles) ? $articles : [])
            ->filter(fn (mixed $article): bool => is_array($article) && (int) ($article['created_by_id'] ?? 0) === $userId && ! ($article['internal'] ?? false)
                && strtolower((string) ($article['sender'] ?? '')) === 'agent' && CarbonImmutable::parse((string) ($article['created_at'] ?? '1970-01-01'))->greaterThanOrEqualTo($since))
            ->sortByDesc(fn (array $article): int => (int) ($article['id'] ?? 0))
            ->first();

        return is_array($found) ? (int) $found['id'] : null;
    }

    /**
     * @param  callable(PendingRequest): Response  $call
     * @param  bool  $certain  Whether a failure proves that nothing changed in Zammad (reads, and the check before sending).
     * @param  int|null  $timeout  Seconds; longer when the call carries attachments.
     *
     * @throws ReplyException
     */
    private function call(string $token, callable $call, bool $certain, ?int $timeout = null): Response
    {
        if (blank($this->config['url'])) {
            throw new ReplyException(ReplyProblem::Unavailable);
        }

        $timeout = max(1, $timeout ?? (int) $this->config['timeout']);

        $request = Http::baseUrl(rtrim((string) $this->config['url'], '/').'/api/v1/')
            ->withHeaders(['Authorization' => 'Token token='.$token])
            ->acceptJson()
            ->connectTimeout($timeout)
            ->timeout($timeout);

        try {
            $response = $call($request);
        } catch (ConnectionException) {
            Log::warning('Zammad reply: connection problem.');

            throw new ReplyException($certain ? ReplyProblem::Unavailable : ReplyProblem::Unclear);
        }

        if ($response->successful()) {
            return $response;
        }

        Log::warning('Zammad reply: status '.$response->status().'.');

        throw new ReplyException(match (true) {
            $response->status() === 401 => ReplyProblem::TokenRejected,
            $response->status() === 403 => ReplyProblem::Forbidden,
            $response->status() === 404 => ReplyProblem::NotFound,
            in_array($response->status(), [400, 422], true) => ReplyProblem::Rejected,
            $certain => ReplyProblem::Unavailable,
            default => ReplyProblem::Unclear,
        });
    }
}
