<?php

namespace App\Zammad;

/**
 * Where a reply goes (PROJ-19), read from the ticket on the server like Zammad's own "Antworten": the reply
 * address of the last customer message, the subject and the message the reply refers to. Never from the form.
 */
final readonly class ReplyTarget
{
    public function __construct(public string $to, public string $subject, public ?string $inReplyTo) {}

    /**
     * Null when there is no customer message to answer by mail (empty ticket, phone note, no usable address).
     */
    public static function for(Ticket $ticket): ?self
    {
        $last = null;

        foreach ($ticket->articles as $article) {
            if ($article->kind === ArticleKind::Customer && ! $article->automatic && ! in_array($article->type, ['phone', 'note'], true)) {
                $last = $article;
            }
        }

        if ($last === null) {
            return null;
        }

        $to = self::address($last->replyTo) ?? self::address($last->from);

        if ($to === null) {
            return null;
        }

        $subject = trim($last->subject ?? '') !== '' ? trim((string) $last->subject) : $ticket->title;

        return new self($to, preg_match('/^(re|aw)\s*:/i', $subject) === 1 ? $subject : 'Re: '.$subject, $last->messageId);
    }

    /**
     * Public messages that came in after the message with this ID (what the suggestion knew); internal notes do not count.
     *
     * @return list<TicketArticle>
     */
    public static function newerThan(Ticket $ticket, ?int $lastSeenId): array
    {
        if ($lastSeenId === null) {
            return [];
        }

        $newer = [];
        $seen = false;

        foreach ($ticket->articles as $article) {
            if ($seen && $article->kind !== ArticleKind::Internal) {
                $newer[] = $article;
            }

            $seen = $seen || $article->id === $lastSeenId;
        }

        return $seen ? $newer : [];
    }

    private static function address(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $candidate = preg_match('/<([^<>]+)>\s*$/', $value, $match) === 1 ? $match[1] : trim($value, " \t\n\r\"'");

        if (filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false) {
            return $candidate;
        }

        return self::internationalDomain($candidate);
    }

    /**
     * An address whose domain has umlauts (for example kunde@autoschlüssel.de) in the form mail servers
     * accept: the domain as Punycode (xn--…). Null when it is not a valid address even then.
     */
    private static function internationalDomain(string $candidate): ?string
    {
        $at = strrpos($candidate, '@');

        if ($at === false || ! function_exists('idn_to_ascii')) {
            return null;
        }

        $domain = idn_to_ascii(substr($candidate, $at + 1), IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        if (! is_string($domain) || $domain === '') {
            return null;
        }

        $address = substr($candidate, 0, $at + 1).$domain;

        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false ? $address : null;
    }
}
