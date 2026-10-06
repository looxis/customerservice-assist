<?php

namespace App\Analysis;

use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;

/**
 * The parts of a ticket that can go to the language model, built from the
 * cleaned thread of the ticket view (no collapsed quotes, signatures,
 * boilerplate or clickable links).
 */
class TicketContext
{
    /** @var list<TicketArticle> */
    private array $earlier;

    private ?TicketArticle $lastCustomerMessage;

    public function __construct(private readonly Ticket $ticket)
    {
        $last = null;

        foreach ($ticket->articles as $index => $article) {
            if ($article->kind === ArticleKind::Customer) {
                $last = $index;
            }
        }

        $this->lastCustomerMessage = $last === null ? null : $ticket->articles[$last];
        $this->earlier = array_values(array_filter($ticket->articles, fn (TicketArticle $article, int $index): bool => $index !== $last, ARRAY_FILTER_USE_BOTH));
    }

    public function hasLastCustomerMessage(): bool
    {
        return $this->lastCustomerMessage !== null;
    }

    /**
     * Whether a choice between variants makes sense at all.
     */
    public function offersVariants(): bool
    {
        return $this->hasLastCustomerMessage() && $this->earlier !== [];
    }

    /**
     * @return list<ContextVariant>
     */
    public function variants(): array
    {
        return $this->offersVariants() ? ContextVariant::cases() : [ContextVariant::FullThread];
    }

    /**
     * The earlier thread is long enough that a summary should come first.
     */
    public function suggestsSummary(): bool
    {
        if (! $this->offersVariants()) {
            return false;
        }

        $threshold = config('analysis.summary_threshold');
        $count = count($this->earlier);
        $length = array_sum(array_map(fn (TicketArticle $article): int => mb_strlen($this->plain($article)), $this->earlier));

        return $count >= $threshold['messages'] || ($count >= $threshold['long_messages'] && $length > $threshold['characters']);
    }

    public function defaultVariant(): ContextVariant
    {
        return $this->suggestsSummary() ? ContextVariant::LastWithSummary : ContextVariant::FullThread;
    }

    /**
     * Fingerprint of the earlier thread; a summary of a different thread is outdated.
     */
    public function earlierFingerprint(): string
    {
        return hash('sha256', implode("\n---\n", array_map(fn (TicketArticle $article): string => $this->message($article), $this->earlier)));
    }

    public function earlierUntil(): ?string
    {
        return $this->earlier === [] ? null : $this->earlier[array_key_last($this->earlier)]->createdAt->format('d.m.Y, H:i');
    }

    /**
     * The earlier thread as text, for the summary.
     */
    public function earlierText(): string
    {
        return implode("\n\n", array_map(fn (TicketArticle $article): string => $this->message($article), $this->earlier));
    }

    /**
     * The ticket context of a variant, before contact data is replaced.
     */
    public function text(ContextVariant $variant, ?string $summary = null): string
    {
        $all = array_map(fn (TicketArticle $article): string => $this->message($article), $this->ticket->articles);
        $last = $this->lastCustomerMessage === null ? null : $this->message($this->lastCustomerMessage, 'Letzte Kundennachricht');

        return match (true) {
            $variant === ContextVariant::FullThread || $last === null => "## Verlauf (älteste Nachricht zuerst)\n\n".implode("\n\n", $all),
            $variant === ContextVariant::LastMessage => $last,
            default => "## Zusammenfassung des bisherigen Verlaufs\n\n".trim((string) $summary)."\n\n".$last,
        };
    }

    private function message(TicketArticle $article, ?string $title = null): string
    {
        $header = ($title ?? 'Nachricht').' – '.$article->kind->label()
            .' – '.$article->createdAt->format('d.m.Y, H:i')
            .($article->kind === ArticleKind::Internal ? ' – INTERN, nicht für den Kunden' : '')
            .($article->channel ? " – {$article->channel}" : '');

        return "### {$header}\n".$this->plain($article);
    }

    private function plain(TicketArticle $article): string
    {
        $html = preg_replace('/<(br|\/p|\/div|\/li|\/tr|\/h\d|\/blockquote)\b[^>]*>/i', "\n", $article->body->toHtml()) ?? '';
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t\x{00A0}]+\n/u", "\n", $text) ?? $text;

        $text = trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);

        return $text === '' ? '(kein Text, nur Anhang)' : $text;
    }
}
