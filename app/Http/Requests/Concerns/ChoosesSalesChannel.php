<?php

namespace App\Http\Requests\Concerns;

use App\Analysis\GuidedForms;
use App\Knowledge\SalesChannels;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Validator;

/**
 * The sales channel of the proposal form (PROJ-89), shared by the ticket form and the question form. The form
 * always sends the field, so an empty choice is an error; a request without the field (a continued case from
 * before the channel was asked for) takes the channel of the customer group.
 */
trait ChoosesSalesChannel
{
    /**
     * @return array<string, list<string|In>>
     */
    protected function salesChannelRules(): array
    {
        return [SalesChannels::FIELD => ['sometimes', 'required', 'string', Rule::in(app(SalesChannels::class)->keys())]];
    }

    /**
     * @return array<string, string>
     */
    protected function salesChannelMessages(): array
    {
        return [
            SalesChannels::FIELD.'.required' => 'Bitte den Verkaufskanal wählen.',
            SalesChannels::FIELD.'.in' => 'Diesen Verkaufskanal gibt es nicht (mehr). Bitte einen anderen wählen.',
        ];
    }

    /**
     * Whether the chosen channel has no products and no orders (PROJ-94, e.g. WeCreateYour).
     */
    public function channelWithoutProducts(): bool
    {
        $chosen = $this->input(SalesChannels::FIELD);

        return is_string($chosen) && app(SalesChannels::class)->withoutProducts($chosen);
    }

    /**
     * For a channel without products the case is one without a product and without orders, whatever the browser
     * still sends (an old page, a hidden field): products, orders and order data typed in by hand are dropped.
     */
    protected function dropProductsForChannel(): void
    {
        if (! $this->channelWithoutProducts()) {
            return;
        }

        $this->merge([
            'produkte' => [], 'produkt_ohne_wissen' => '', 'forge_produkte' => [], 'eocs_produkte' => [], 'forge_sku' => [], 'kein_produktbezug' => '1',
            'bestellungen' => [], 'ohne_bestelldetails' => '0', 'return_label' => null,
            'bestellung_nummer' => null, 'bestellung_kanal' => null, 'bestellung_datum' => null, 'bestellung_produkt' => null, 'bestellung_personalisierung' => null,
        ]);
    }

    /**
     * A form for goods and shipping (Reklamation, Neuversand) cannot be used with a channel without products.
     */
    protected function checkFormForChannel(Validator $validator): void
    {
        if ($this->channelWithoutProducts() && in_array($this->input('formular'), GuidedForms::forGoods(), true)) {
            $validator->errors()->add('formular', 'Für den Verkaufskanal „'.app(SalesChannels::class)->label((string) $this->input(SalesChannels::FIELD)).'“ gibt es keine Formulare für Ware und Versand. Bitte „Freitext“ oder „Technischer Support“ wählen.');
        }
    }

    public function salesChannel(): string
    {
        $chosen = $this->validated(SalesChannels::FIELD);

        return is_string($chosen) && $chosen !== '' ? $chosen : app(SalesChannels::class)->ofGroup($this->validated('kundengruppe'));
    }
}
