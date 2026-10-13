<?php

namespace App\Knowledge\Retrieval;

use App\Analysis\LanguageDetector;
use App\Analysis\TicketContext;
use App\Eocs\EocsOrder;
use App\Knowledge\CustomerGroup;
use App\Orders\OrderNumberDetector;
use App\Zammad\ArticleKind;
use App\Zammad\Ticket;
use App\Zammad\TicketArticle;
use Carbon\CarbonImmutable;

/**
 * Collects the facts of one case for the retrieval (PROJ-36): the customer
 * texts in tiers by how current they are, the case-level facts and the facts
 * of the order the concern refers to. Nothing is stored.
 */
class CaseFactsBuilder
{
    /** A reply this short ("ok danke") carries no concern of its own; the message before it counts too. */
    private const int SHORT_MESSAGE = 40;

    /** A ticket with at most this many customer messages is young: its title still names the concern. */
    private const int YOUNG_TICKET = 2;

    /** How many customer messages before the current concern still count as recent. */
    private const int RECENT_MESSAGES = 3;

    public function __construct(
        private readonly LanguageDetector $language,
        private readonly OrderNumberDetector $orderNumbers,
    ) {}

    /**
     * @param  list<string>  $products
     * @param  list<EocsOrder>  $orders  Orders loaded from EOCS.
     * @param  list<array{number: string, channel: string|null, items: list<string>}>  $mentionedOrders  Order numbers known from the ticket but not loaded.
     * @param  array<int, array{text: string|null}>  $translations  German translations by Zammad message ID (the Translator's result).
     * @param  string|null  $summary  The summary of the earlier thread, if one is used.
     * @param  string  $employeeContext  What the employee noted about the case (for example what an uploaded picture shows); counts as the current concern.
     */
    public function build(Ticket $ticket, CustomerGroup $group, array $products, array $orders, array $mentionedOrders = [], array $translations = [], ?string $summary = null, ?CarbonImmutable $now = null, string $employeeContext = ''): CaseFacts
    {
        $now ??= CarbonImmutable::now();
        $customer = array_values(array_filter($ticket->articles, fn (TicketArticle $article): bool => $article->kind === ArticleKind::Customer));
        [$tiers, $parts] = $this->tiers($ticket, $customer, $translations, $summary);
        if (trim($employeeContext) !== '') {
            $tiers['A'][] = trim($employeeContext);
            $parts['A'][] = [trim($employeeContext), null];
        }

        [$candidates, $reference] = $this->candidates($tiers['A'], $orders, $mentionedOrders, $now);

        foreach ($candidates as $candidate) {
            array_push($tiers['B'], ...$candidate->texts);
            array_push($parts['B'], ...array_map(fn (string $text): array => [$text, null], $candidate->texts));
        }

        $last = $customer === [] ? null : $customer[array_key_last($customer)];
        $lastText = $last === null ? '' : TicketContext::plainText($last);

        return new CaseFacts([
            'customer_type' => $group->customerType,
            'sales_channel' => $group->salesChannel,
            'products' => array_values($products),
            'ticket_closed' => $ticket->closed,
            'ticket_age_days' => max(0, (int) floor($ticket->createdAt->setTimezone('Europe/Berlin')->startOfDay()->diffInDays($now->setTimezone('Europe/Berlin')->startOfDay(), false))),
            'customer_message_count' => count($customer),
            'current_message_has_attachment' => $last === null ? null : $last->attachments !== [],
            'message_foreign' => $last === null ? null : $this->language->isForeign($lastText),
        ], $candidates, $reference, $tiers, $parts);
    }

    /**
     * @param  list<TicketArticle>  $customer
     * @param  array<int, array{text: string|null}>  $translations
     * @return array{0: array{A: list<string>, B: list<string>, C: list<string>}, 1: array{A: list<array{0: string, 1: string|null}>, B: list<array{0: string, 1: string|null}>, C: list<array{0: string, 1: string|null}>}}
     */
    private function tiers(Ticket $ticket, array $customer, array $translations, ?string $summary): array
    {
        $texts = array_map(fn (TicketArticle $article): string => $this->text($article, $translations), $customer);
        $pairs = array_map(fn (TicketArticle $article): array => $this->pair($article, $translations), $customer);
        $a = [];
        $b = [];
        $c = [];
        $pa = [];
        $pb = [];
        $pc = [];

        if ($texts !== []) {
            $count = count($texts);
            $current = $count - 1;
            $a[] = $texts[$current];
            $pa[] = $pairs[$current];

            if ($current > 0 && mb_strlen(trim($texts[$current])) < self::SHORT_MESSAGE) {
                $current--;
                $a[] = $texts[$current];
                $pa[] = $pairs[$current];
            }

            for ($index = $current - 1; $index >= 0; $index--) {
                if (count($b) < self::RECENT_MESSAGES) {
                    $b[] = $texts[$index];
                    $pb[] = $pairs[$index];
                } else {
                    $c[] = $texts[$index];
                    $pc[] = $pairs[$index];
                }
            }
        }

        if (trim($ticket->title) !== '') {
            if (count($customer) <= self::YOUNG_TICKET) {
                array_unshift($a, $ticket->title);
                array_unshift($pa, [$ticket->title, null]);
            } else {
                $c[] = $ticket->title;
                $pc[] = [$ticket->title, null];
            }
        }

        if (trim((string) $summary) !== '') {
            $b[] = trim((string) $summary);
            $pb[] = [trim((string) $summary), null];
        }

        return [['A' => $a, 'B' => $b, 'C' => $c], ['A' => $pa, 'B' => $pb, 'C' => $pc]];
    }

    /**
     * Original text and German translation of a customer message apart (null without translation).
     *
     * @param  array<int, array{text: string|null}>  $translations
     * @return array{0: string, 1: string|null}
     */
    private function pair(TicketArticle $article, array $translations): array
    {
        $text = $article->hasText() ? TicketContext::plainText($article) : '';
        $translated = $article->id === null ? null : ($translations[$article->id]['text'] ?? null);

        return [trim($text), is_string($translated) && trim($translated) !== '' ? trim($translated) : null];
    }

    /**
     * The plain text of a customer message, with its German translation when there is one.
     *
     * @param  array<int, array{text: string|null}>  $translations
     */
    private function text(TicketArticle $article, array $translations): string
    {
        $text = $article->hasText() ? TicketContext::plainText($article) : '';
        $translated = $article->id === null ? null : ($translations[$article->id]['text'] ?? null);

        return trim($text.(is_string($translated) && trim($translated) !== '' ? "\n".$translated : ''));
    }

    /**
     * The orders the concern may refer to and how they were chosen. An order
     * number named in the current concern decides; without one, a single known
     * order is the reference; several are all candidates. A named order that
     * was not loaded stays unknown: no other order stands in for it.
     *
     * @param  list<string>  $current
     * @param  list<EocsOrder>  $orders
     * @param  list<array{number: string, channel: string|null, items: list<string>}>  $mentioned
     * @return array{0: list<OrderFacts>, 1: string}
     */
    private function candidates(array $current, array $orders, array $mentioned, CarbonImmutable $now): array
    {
        $known = [];

        foreach ($orders as $order) {
            $known[$order->externalNumber] = OrderFacts::fromEocs($order, $now);
            $known[(string) $order->id] ??= $known[$order->externalNumber];
        }

        foreach ($mentioned as $mention) {
            $known[$mention['number']] ??= OrderFacts::unknown($mention['number']);
        }

        $named = [];

        foreach ($this->orderNumbers->inText(implode("\n", $current)) as $number) {
            if (isset($known[$number->value])) {
                $named[spl_object_id($known[$number->value])] = $known[$number->value];
            }
        }

        if ($named !== []) {
            $named = array_values($named);

            return [$named, count($named) === 1 ? CaseFacts::REFERENCE_NAMED : CaseFacts::REFERENCE_UNCLEAR];
        }

        $all = [];

        foreach ($known as $facts) {
            $all[spl_object_id($facts)] = $facts;
        }

        $all = array_values($all);

        return match (count($all)) {
            0 => [[], CaseFacts::REFERENCE_NONE],
            1 => [$all, CaseFacts::REFERENCE_SINGLE],
            default => [$all, CaseFacts::REFERENCE_UNCLEAR],
        };
    }
}
