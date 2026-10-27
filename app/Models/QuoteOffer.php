<?php

namespace App\Models;

use App\Quotes\QuoteCalculator;
use Database\Factories\QuoteOfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A saved offer (PROJ-34) with a snapshot of its items. The totals are always calculated, never stored.
 */
#[Fillable(['number', 'created_on', 'valid_until', 'company', 'contact', 'email', 'street', 'city', 'note', 'items', 'ticket_number', 'created_by'])]
class QuoteOffer extends Model
{
    /** @use HasFactory<QuoteOfferFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_on' => 'date',
            'valid_until' => 'date',
            'items' => 'array',
        ];
    }

    /**
     * @return array{net: float, vat: float, gross: float}
     */
    public function totals(): array
    {
        return QuoteCalculator::offerTotals($this->items ?? []);
    }

    /**
     * What the page needs to show or reopen this offer.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'created_on' => $this->created_on->format('Y-m-d'),
            'valid_until' => $this->valid_until->format('Y-m-d'),
            'company' => $this->company,
            'contact' => $this->contact,
            'email' => $this->email,
            'street' => $this->street,
            'city' => $this->city,
            'note' => $this->note,
            'items' => $this->items ?? [],
            'ticket_number' => $this->ticket_number,
            'created_by' => $this->created_by,
            'gross' => $this->totals()['gross'],
            'print_url' => route('quote.offers.print', ['offer' => $this->id]),
        ];
    }
}
