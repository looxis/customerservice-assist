<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads tickets from Zammad 7 over its REST API, read-only, with the app's
 * own token. Every call asks Zammad directly; nothing is cached or stored,
 * and no ticket content is logged.
 */
class ZammadClient
{
    private const array STATE_LABELS = [
        'new' => 'neu',
        'open' => 'offen',
        'pending reminder' => 'warten auf Erinnerung',
        'pending close' => 'warten auf Schließen',
        'closed' => 'geschlossen',
        'merged' => 'zusammengeführt',
    ];

    private const array CHANNEL_LABELS = [
        'email' => 'E-Mail',
        'note' => 'Notiz',
        'phone' => 'Telefon',
        'web' => 'Web-Formular',
        'sms' => 'SMS',
        'chat' => 'Chat',
        'fax' => 'Fax',
    ];

    /**
     * @param  array{url: ?string, token: ?string, timeout: int|string, timezone: string}  $config
     */
    public function __construct(
        private readonly array $config,
        private readonly MessageBody $messageBody,
    ) {}

    /**
     * @throws ZammadException
     */
    public function ticket(string $number): Ticket
    {
        try {
            $data = $this->findByNumber($number);
            $articles = $this->get("ticket_articles/by_ticket/{$data['id']}", ['expand' => 'true']);
            $customer = isset($data['customer_id']) ? $this->customer((int) $data['customer_id']) : null;
            $state = strtolower((string) ($data['state'] ?? ''));

            return new Ticket(
                number: (string) $data['number'],
                id: (int) $data['id'],
                title: trim((string) ($data['title'] ?? '')) ?: '(ohne Betreff)',
                state: self::STATE_LABELS[$state] ?? ($state ?: 'unbekannt'),
                closed: in_array($state, ['closed', 'merged'], true),
                mergedIntoNumber: $state === 'merged' ? $this->mergeTarget((int) $data['id']) : null,
                group: is_string($data['group'] ?? null) ? $data['group'] : null,
                customerName: $customer['name'] ?? null,
                customerEmail: $customer['email'] ?? null,
                createdAt: $this->time($data['created_at'] ?? null),
                articles: $this->articles(is_array($articles) ? $articles : []),
                zammadUrl: $this->baseUrl()."/#ticket/zoom/{$data['id']}",
            );
        } catch (ZammadException $exception) {
            Log::warning('Zammad request failed', ['ticket' => $number, 'problem' => $exception->problem->value, 'status' => $exception->httpStatus]);

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function findByNumber(string $number): array
    {
        $result = $this->get('tickets/search', ['query' => "number:{$number}", 'limit' => 5, 'expand' => 'true']);
        $tickets = is_array($result) && array_is_list($result) ? $result : ($result['tickets'] ?? []);

        foreach ($tickets as $ticket) {
            if (is_array($ticket) && (string) ($ticket['number'] ?? '') === $number) {
                return $ticket;
            }
        }

        throw new ZammadException(ZammadProblem::NotFound);
    }

    /**
     * @return array{name: ?string, email: ?string}|null
     */
    private function customer(int $id): ?array
    {
        try {
            $user = $this->get("users/{$id}");
        } catch (ZammadException $exception) {
            if ($exception->problem === ZammadProblem::Unavailable) {
                throw $exception;
            }

            return null;
        }

        $name = trim(($user['firstname'] ?? '').' '.($user['lastname'] ?? ''));

        return ['name' => $name !== '' ? $name : null, 'email' => $user['email'] ?? null];
    }

    /**
     * Number of the ticket a merged ticket was merged into, from its "parent" link.
     */
    private function mergeTarget(int $id): ?string
    {
        try {
            $links = $this->get('links', ['link_object' => 'Ticket', 'link_object_value' => $id]);

            foreach ($links['links'] ?? [] as $link) {
                if (($link['link_type'] ?? null) === 'parent' && ($link['link_object'] ?? null) === 'Ticket') {
                    $targetId = $link['link_object_value'];
                    $targetNumber = $links['assets']['Ticket'][$targetId]['number'] ?? $this->get("tickets/{$targetId}")['number'] ?? null;

                    return $targetNumber === null ? null : (string) $targetNumber;
                }
            }
        } catch (ZammadException) {
            // The ticket itself loaded; a missing merge target is not worth an error.
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $articles
     * @return list<TicketArticle>
     */
    private function articles(array $articles): array
    {
        usort($articles, fn (array $a, array $b): int => [(string) ($a['created_at'] ?? ''), (int) ($a['id'] ?? 0)] <=> [(string) ($b['created_at'] ?? ''), (int) ($b['id'] ?? 0)]);

        return array_map(function (array $article): TicketArticle {
            $sender = strtolower((string) ($article['sender'] ?? ''));
            $parsed = $this->messageBody->parse($article['body'] ?? '', $article['content_type'] ?? 'text/plain', ours: $sender !== 'customer');
            $type = strtolower((string) ($article['type'] ?? ''));

            return new TicketArticle(
                kind: match (true) {
                    (bool) ($article['internal'] ?? false) => ArticleKind::Internal,
                    $sender === 'customer' => ArticleKind::Customer,
                    default => ArticleKind::Agent,
                },
                senderName: $this->senderName($article),
                createdAt: $this->time($article['created_at'] ?? null),
                body: $parsed['body'],
                quote: $parsed['quote'],
                attachments: $this->attachments($article['attachments'] ?? []),
                channel: $type === '' ? null : (self::CHANNEL_LABELS[$type] ?? ucfirst($type)),
                automatic: $sender === 'system',
            );
        }, array_values(array_filter($articles, 'is_array')));
    }

    /**
     * @param  array<string, mixed>  $article
     */
    private function senderName(array $article): string
    {
        $from = trim((string) ($article['from'] ?? ''));
        $name = trim(preg_replace('/\s*<[^>]*>\s*$/', '', $from) ?? '', " \"'");

        if ($name !== '' && ! str_contains($name, '@')) {
            return $name;
        }

        return $name !== '' ? $name : (is_string($article['created_by'] ?? null) ? $article['created_by'] : 'Unbekannt');
    }

    /**
     * Real attachments only: without images embedded in the mail text (e.g.
     * signature logos) and without Zammad's copy of the original mail.
     *
     * @return list<TicketAttachment>
     */
    private function attachments(mixed $attachments): array
    {
        $result = [];

        foreach (is_array($attachments) ? $attachments : [] as $attachment) {
            $preferences = array_change_key_case((array) ($attachment['preferences'] ?? []), CASE_LOWER);
            $contentType = strtolower((string) ($preferences['content-type'] ?? $preferences['mime-type'] ?? 'application/octet-stream'));
            $embedded = ! empty($preferences['content-id']) || str_starts_with(strtolower((string) ($preferences['content-disposition'] ?? '')), 'inline');

            $originalMail = ! empty($preferences['content-alternative']) || ! empty($preferences['original-format']);

            if ($originalMail || ($embedded && str_starts_with($contentType, 'image/'))) {
                continue;
            }

            $result[] = new TicketAttachment((string) ($attachment['filename'] ?? 'Anhang'), $contentType, (int) ($attachment['size'] ?? 0));
        }

        return $result;
    }

    private function time(mixed $value): CarbonImmutable
    {
        return CarbonImmutable::parse(is_string($value) ? $value : 'now')->setTimezone($this->config['timezone']);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     *
     * @throws ZammadException
     */
    private function get(string $path, array $query = []): array
    {
        try {
            $response = $this->request()->get($path, $query);
        } catch (ConnectionException) {
            throw new ZammadException(ZammadProblem::Unavailable);
        }

        $this->guard($response);

        $json = $response->json();

        if (! is_array($json)) {
            throw new ZammadException(ZammadProblem::Unavailable, $response->status());
        }

        return $json;
    }

    /**
     * @throws ZammadException
     */
    private function guard(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        throw new ZammadException(match ($response->status()) {
            401 => ZammadProblem::Misconfigured,
            403 => ZammadProblem::Forbidden,
            404 => ZammadProblem::NotFound,
            default => ZammadProblem::Unavailable,
        }, $response->status());
    }

    /**
     * @throws ZammadException
     */
    private function request(): PendingRequest
    {
        if (blank($this->config['url']) || blank($this->config['token'])) {
            throw new ZammadException(ZammadProblem::Misconfigured);
        }

        $timeout = max(1, (int) $this->config['timeout']);

        return Http::baseUrl($this->baseUrl().'/api/v1/')
            ->withHeaders(['Authorization' => 'Token token='.$this->config['token']])
            ->acceptJson()
            ->connectTimeout($timeout)
            ->timeout($timeout);
    }

    private function baseUrl(): string
    {
        return rtrim((string) $this->config['url'], '/');
    }
}
