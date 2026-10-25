<?php

namespace App\Zammad;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads tickets from Zammad 7 over its REST API, read-only, with the app's
 * own token. Every call asks Zammad directly; nothing is cached or stored,
 * and no ticket content is logged.
 */
class ZammadClient
{
    /** Shortest text of ours (letters and digits only) that counts as quoted when it shows up again in a customer's message. */
    private const int ECHO_MIN_LENGTH = 60;

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
     * Orders named in the thread of the ticket being read, keyed by number.
     *
     * @var array<string, OrderMention>
     */
    private array $orders = [];

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
            $this->orders = [];
            $thread = $this->articles(is_array($articles) ? $articles : [], isset($data['customer_id']) ? (int) $data['customer_id'] : null, $customer['email'] ?? null);

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
                articles: $thread,
                zammadUrl: $this->baseUrl()."/#ticket/zoom/{$data['id']}",
                orders: array_values($this->orders),
                customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                organizationId: isset($data['organization_id']) ? (int) $data['organization_id'] : null,
            );
        } catch (ZammadException $exception) {
            Log::warning('Zammad request failed', ['ticket' => $number, 'problem' => $exception->problem->value, 'status' => $exception->httpStatus]);

            throw $exception;
        }
    }

    /**
     * The tickets linked to this one in Zammad, in any direction (PROJ-52).
     *
     * @return list<RelatedTicket>
     *
     * @throws ZammadException
     */
    public function linkedTickets(int $ticketId): array
    {
        try {
            $links = $this->get('links', ['link_object' => 'Ticket', 'link_object_value' => $ticketId])['links'] ?? [];
            $tickets = [];

            foreach (is_array($links) ? $links : [] as $link) {
                $id = is_array($link) && ($link['link_object'] ?? null) === 'Ticket' ? (int) ($link['link_object_value'] ?? 0) : 0;

                if ($id > 0 && $id !== $ticketId && ! isset($tickets[$id])) {
                    $tickets[$id] = $this->relatedTicket($this->get("tickets/{$id}", ['expand' => 'true']), RelatedTicket::LINKED);
                }
            }

            return array_values(array_filter($tickets));
        } catch (ZammadException $exception) {
            Log::warning('Zammad request failed', ['links' => $ticketId, 'problem' => $exception->problem->value, 'status' => $exception->httpStatus]);

            throw $exception;
        }
    }

    /**
     * Tickets that match a search query of Zammad, newest first (PROJ-52).
     *
     * @return list<RelatedTicket>
     *
     * @throws ZammadException
     */
    public function searchTickets(string $query, string $source, int $limit = 20): array
    {
        try {
            $result = $this->get('tickets/search', ['query' => $query, 'limit' => $limit, 'expand' => 'true', 'sort_by' => 'created_at', 'order_by' => 'desc']);
        } catch (ZammadException $exception) {
            Log::warning('Zammad request failed', ['search' => true, 'problem' => $exception->problem->value, 'status' => $exception->httpStatus]);

            throw $exception;
        }

        $rows = array_is_list($result) ? $result : ($result['tickets'] ?? []);

        return array_values(array_filter(array_map(fn (mixed $row): ?RelatedTicket => is_array($row) ? $this->relatedTicket($row, $source) : null, is_array($rows) ? $rows : [])));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function relatedTicket(array $data, string $source): ?RelatedTicket
    {
        if (! isset($data['id'], $data['number'])) {
            return null;
        }

        $state = strtolower((string) ($data['state'] ?? ''));

        return new RelatedTicket(
            (int) $data['id'],
            (string) $data['number'],
            trim((string) ($data['title'] ?? '')) ?: '(ohne Betreff)',
            self::STATE_LABELS[$state] ?? ($state ?: 'unbekannt'),
            (int) ($data['article_count'] ?? 0),
            $this->time($data['created_at'] ?? null),
            $source,
        );
    }

    /**
     * The number of the ticket with this Zammad ID, read without the search
     * index. The pair is remembered so the ticket can be loaded by number
     * even while Zammad's search does not know it yet.
     *
     * @throws ZammadException
     */
    public function numberForId(int $id): string
    {
        try {
            $number = (string) ($this->get("tickets/{$id}")['number'] ?? '');
        } catch (ZammadException $exception) {
            Log::warning('Zammad request failed', ['zammad_id' => $id, 'problem' => $exception->problem->value, 'status' => $exception->httpStatus]);

            throw $exception;
        }

        if ($number === '') {
            throw new ZammadException(ZammadProblem::NotFound);
        }

        Cache::put("zammad.ticket-id.{$number}", $id, now()->addDays(90));

        return $number;
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

        // Not in the search index (yet): use the ID remembered from a pasted address.
        $id = Cache::get("zammad.ticket-id.{$number}");

        if (is_int($id)) {
            $ticket = $this->get("tickets/{$id}", ['expand' => 'true']);

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
    private function articles(array $articles, ?int $customerId = null, ?string $customerEmail = null): array
    {
        usort($articles, fn (array $a, array $b): int => [(string) ($a['created_at'] ?? ''), (int) ($a['id'] ?? 0)] <=> [(string) ($b['created_at'] ?? ''), (int) ($b['id'] ?? 0)]);

        return $this->withoutEchoedOwnMessages(array_map(function (array $article) use ($customerId, $customerEmail): TicketArticle {
            $sender = strtolower((string) ($article['sender'] ?? ''));

            // Zammad files a mail under "Agent" when its sender is also an agent
            // (a colleague writing in, a forwarded customer mail, a test mail).
            // A mail written by the ticket's customer from the customer's own
            // address is the customer's message all the same.
            if ($sender === 'agent' && ! ($article['internal'] ?? false) && $customerId !== null && (int) ($article['created_by_id'] ?? 0) === $customerId
                && filled($customerEmail) && str_contains(strtolower((string) ($article['from'] ?? '')), strtolower((string) $customerEmail))) {
                $sender = 'customer';
            }
            $parsed = $this->messageBody->parse($article['body'] ?? '', $article['content_type'] ?? 'text/plain', ours: $sender !== 'customer');

            if ($parsed['order'] !== null) {
                $number = $parsed['order']->number;
                $this->orders[$number] = isset($this->orders[$number]) ? $this->orders[$number]->merge($parsed['order']) : $parsed['order'];
            }
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
                signature: $parsed['signature'],
                id: isset($article['id']) ? (int) $article['id'] : null,
                order: $parsed['order'],
                attachments: $this->attachments($article['attachments'] ?? []),
                channel: $type === '' ? null : (self::CHANNEL_LABELS[$type] ?? ucfirst($type)),
                automatic: $sender === 'system',
                type: $type === '' ? null : $type,
                from: filled($article['from'] ?? null) ? (string) $article['from'] : null,
                replyTo: filled($article['reply_to'] ?? null) ? (string) $article['reply_to'] : null,
                subject: filled($article['subject'] ?? null) ? (string) $article['subject'] : null,
                messageId: filled($article['message_id'] ?? null) ? (string) $article['message_id'] : null,
            );
        }, array_values(array_filter($articles, 'is_array'))));
    }

    /**
     * Safety net for the split between new text and quote: if the visible text of a customer's message contains the
     * text of an earlier message of ours and the collapsed quote does not, the mail was written the other way round
     * than the split assumed (the customer's new text is in the quote). Text and quote are then exchanged.
     *
     * @param  list<TicketArticle>  $articles
     * @return list<TicketArticle>
     */
    private function withoutEchoedOwnMessages(array $articles): array
    {
        $ours = [];

        foreach ($articles as $index => $article) {
            if ($article->kind === ArticleKind::Agent && ! $article->automatic) {
                $text = TicketArticle::comparableText($article->body);

                if (mb_strlen($text) >= self::ECHO_MIN_LENGTH) {
                    $ours[] = $text;
                }

                continue;
            }

            if ($article->kind !== ArticleKind::Customer || $article->quote === null || $ours === []) {
                continue;
            }

            $visible = TicketArticle::comparableText($article->body);
            $quoted = TicketArticle::comparableText($article->quote);

            if ($quoted === '' || $this->containsAny($visible, $ours) === false || $this->containsAny($quoted, $ours)) {
                continue;
            }

            $articles[$index] = $article->withBodyAndQuoteSwapped();
        }

        return $articles;
    }

    /**
     * @param  list<string>  $needles
     */
    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
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
     * The content of one attachment (PROJ-60), loaded with the app's own token. The caller has checked that
     * the attachment belongs to the ticket.
     *
     * @throws ZammadException
     */
    public function attachmentContent(int $ticketId, int $articleId, int $attachmentId): string
    {
        try {
            $response = $this->request()->withHeaders(['Accept' => '*/*'])->timeout(max(1, (int) ($this->config['attachment_timeout'] ?? 30)))->get("ticket_attachment/{$ticketId}/{$articleId}/{$attachmentId}");
        } catch (ConnectionException) {
            throw new ZammadException(ZammadProblem::Unavailable);
        }

        $this->guard($response);

        return $response->body();
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

            $result[] = new TicketAttachment((string) ($attachment['filename'] ?? 'Anhang'), $contentType, (int) ($attachment['size'] ?? 0), isset($attachment['id']) ? (int) $attachment['id'] : null);
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
