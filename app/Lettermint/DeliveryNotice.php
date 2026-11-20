<?php

namespace App\Lettermint;

/**
 * One notification mail found at Lettermint for an order (PROJ-97). `outcome` is the evidence from the event history:
 * accepted (the recipient's mail server took it, `delivered`), handed_over (`processed` only), pending (queued or
 * scheduled), failed (bounce, failure or suppression as the last word) or unknown. `at` is the time of that event in
 * German time; null when the event is missing.
 */
final readonly class DeliveryNotice
{
    public const array KIND_LABELS = ['today' => 'Paket kommt heute', 'pickup' => 'Abholhinweis', 'other' => 'Sonstige Mail zur Bestellung'];

    public const array OUTCOME_LABELS = [
        'accepted' => 'vom Empfänger-Mailserver angenommen',
        'handed_over' => 'an den Versanddienst übergeben, Annahme beim Empfänger offen',
        'pending' => 'noch nicht versendet (Warteschlange oder geplant)',
        'failed' => 'nicht zugestellt',
        'unknown' => 'Status unbekannt',
    ];

    public function __construct(
        public string $kind,
        public string $subject,
        public string $outcome,
        public ?string $at,
        public ?string $failure = null,
        public ?bool $toTicketSender = null,
        public ?string $sentAt = null,
    ) {}

    public function proven(): bool
    {
        return $this->outcome === 'accepted';
    }

    /**
     * Whether LOOXIS sent the mail (PROJ-99), whatever became of it at the recipient's mail server.
     */
    public function sent(): bool
    {
        return $this->sentAt !== null || $this->outcome === 'accepted';
    }

    /**
     * What the language model sees: no subject and no address, only kind, evidence and time.
     *
     * @return array<string, mixed>
     */
    public function facts(): array
    {
        return array_filter([
            'mail' => self::KIND_LABELS[$this->kind],
            'proven' => $this->proven(),
            'result' => self::OUTCOME_LABELS[$this->outcome],
            'time' => $this->at,
            'failure' => $this->failure,
            'to_ticket_sender' => $this->toTicketSender,
        ], fn ($value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function toStored(): array
    {
        return ['kind' => $this->kind, 'subject' => $this->subject, 'outcome' => $this->outcome, 'at' => $this->at, 'failure' => $this->failure, 'to_ticket_sender' => $this->toTicketSender, 'sent_at' => $this->sentAt];
    }
}
