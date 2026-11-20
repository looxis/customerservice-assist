<?php

namespace App\Knowledge;

use App\Eocs\EocsOrder;
use App\Models\TrackedTicket;
use App\Settings\SettingsStore;
use App\Zammad\Ticket;
use Throwable;

/**
 * The sales channel of a case (PROJ-89): which channels an employee can choose, which customer group follows from
 * a channel, how the channel is recognised without the employee, and where it is kept with the ticket. A channel
 * can be chosen while an active customer group belongs to it, so switching off the group removes the channel.
 */
class SalesChannels
{
    public const string FIELD = 'verkaufskanal';

    /** The form value for "not known yet": no channel, only knowledge for every channel applies. */
    public const string UNCLEAR = 'unclear';

    public const string BY_STAFF = 'staff';

    public const string BY_ORDER = 'order';

    public const string BY_SENDER = 'sender';

    public const string BY_SUBJECT = 'subject';

    public function __construct(
        private readonly KnowledgeSelector $selector,
        private readonly SettingsStore $settings,
    ) {}

    /**
     * The channels of the form in the order of the customer groups, "unclear" last.
     *
     * @return list<array{key: string, label: string, group: string, without_products: bool}>
     */
    public function options(): array
    {
        $options = [];
        $unclear = null;

        foreach ($this->selector->selectableGroups() as $group) {
            if ($group->isUnclear()) {
                $unclear ??= ['key' => self::UNCLEAR, 'label' => $group->label, 'group' => $group->key, 'without_products' => false];
            } elseif ($group->salesChannel !== null && ! isset($options[$group->salesChannel])) {
                $options[$group->salesChannel] = ['key' => $group->salesChannel, 'label' => $this->label($group->salesChannel), 'group' => $group->key, 'without_products' => (bool) ($this->profile($group->salesChannel)['without_products'] ?? false)];
            }
        }

        return array_values($unclear === null ? $options : [...$options, $unclear]);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_column($this->options(), 'key');
    }

    /**
     * The name of a channel as the settings give it; also for channels that can no longer be chosen.
     */
    public function label(string $channel): string
    {
        try {
            $names = array_column((array) $this->settings->value('kn_sales_channels'), 'label', 'key');
        } catch (Throwable) {
            $names = [];
        }

        return (string) ($names[$channel] ?? $channel);
    }

    /**
     * Whether a channel has no products and no orders (PROJ-94): no product answer, no EOCS, no forms for goods.
     * Only a channel that can still be chosen counts; a deactivated group leaves the ticket as an ordinary one.
     */
    public function withoutProducts(?string $channel): bool
    {
        return $channel !== null && $channel !== '' && (bool) ($this->profile($channel)['without_products'] ?? false)
            && in_array($channel, $this->keys(), true);
    }

    /**
     * The context for the model that goes with a channel (PROJ-94); empty for none.
     */
    public function aiNote(?string $channel): string
    {
        return $channel === null || $channel === '' ? '' : trim((string) ($this->profile($channel)['ai_note'] ?? ''));
    }

    /**
     * The first channel that can be chosen whose subject keyword occurs in the ticket subject, or null.
     */
    public function fromSubject(string $subject): ?string
    {
        $keys = $this->keys();

        foreach ((array) config('knowledge.channel_profiles', []) as $channel => $profile) {
            if (! in_array($channel, $keys, true)) {
                continue;
            }

            foreach (explode(',', (string) ($profile['subject_keywords'] ?? '')) as $keyword) {
                $keyword = trim($keyword);

                if (mb_strlen($keyword) >= 3 && mb_stripos($subject, $keyword) !== false) {
                    return (string) $channel;
                }
            }
        }

        return null;
    }

    /**
     * The channel of a ticket as far as it is known before any order is loaded (PROJ-94): the choice of an
     * employee, else a subject keyword. Decides whether the ticket page asks EOCS at all.
     */
    public function beforeOrders(Ticket $ticket): ?string
    {
        $stored = $this->stored($ticket->number);

        if ($stored !== null && $stored[1] === self::BY_STAFF) {
            return $stored[0];
        }

        return $this->fromSubject($ticket->title);
    }

    /**
     * @return array<string, mixed>
     */
    private function profile(string $channel): array
    {
        return (array) (config('knowledge.channel_profiles')[$channel] ?? []);
    }

    /**
     * The customer group a channel sets in the form, or null for a channel that cannot be chosen.
     */
    public function groupFor(string $channel): ?string
    {
        return array_column($this->options(), 'group', 'key')[$channel] ?? null;
    }

    /**
     * The channel that belongs to a customer group: for proposals from before the channel was asked for.
     */
    public function ofGroup(?string $groupKey): string
    {
        return $this->selector->customerGroup((string) $groupKey)?->salesChannel ?? self::UNCLEAR;
    }

    /**
     * The customer group as it decides about the knowledge of the case: its customer type, the chosen channel.
     * Without a channel (older proposals) the group stays as configured.
     */
    public function applyTo(CustomerGroup $group, ?string $channel): CustomerGroup
    {
        return match (true) {
            $channel === null || $channel === '' => $group,
            $channel === self::UNCLEAR => $group->withChannel(null),
            default => $group->withChannel($channel),
        };
    }

    /**
     * The channel the app recognises on its own and where from: the first loaded order with a known channel, else
     * an Amazon marketplace address of the sender, else a subject keyword of a channel (PROJ-94, `knowledge.channel_profiles`).
     * Only channels that can be chosen.
     *
     * @param  list<EocsOrder>  $orders
     * @return array{0: string, 1: string}|null
     */
    public function detect(Ticket $ticket, array $orders): ?array
    {
        $keys = $this->keys();

        foreach ($orders as $order) {
            if ($order->channel !== null && in_array($order->channel, $keys, true)) {
                return [$order->channel, self::BY_ORDER];
            }
        }

        if (in_array('amazon', $keys, true) && preg_match('/@marketplace\.amazon\.[a-z.]+$/i', trim((string) $ticket->customerEmail)) === 1) {
            return ['amazon', self::BY_SENDER];
        }

        $fromSubject = $this->fromSubject($ticket->title);

        if ($fromSubject !== null) {
            return [$fromSubject, self::BY_SUBJECT];
        }

        return null;
    }

    /**
     * The channel kept with the ticket and who set it, or null when the ticket has none (or is not tracked).
     *
     * @return array{0: string, 1: string|null}|null
     */
    public function stored(string $ticketNumber): ?array
    {
        $row = TrackedTicket::query()->where('number', $ticketNumber)->toBase()->first(['channel', 'channel_source']);

        return $row === null || $row->channel === null ? null : [$row->channel, $row->channel_source];
    }

    /**
     * Keeps the channel with the ticket for the overview on the start page. The choice of an employee always
     * counts ("unclear" is kept as no channel); a recognised channel never replaces a choice of an employee.
     */
    public function remember(string $ticketNumber, string $channel, string $source): void
    {
        $query = TrackedTicket::query()->where('number', $ticketNumber);

        if ($source !== self::BY_STAFF) {
            $query->where(fn ($open) => $open->whereNull('channel_source')->orWhere('channel_source', '!=', self::BY_STAFF))
                ->where(fn ($changed) => $changed->whereNull('channel')->orWhere('channel', '!=', $channel));
        }

        $query->toBase()->update(['channel' => $channel === self::UNCLEAR ? null : $channel, 'channel_source' => $source]);
    }
}
