<?php

namespace App\Http\Requests\Concerns;

use App\Knowledge\SalesChannels;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

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

    public function salesChannel(): string
    {
        $chosen = $this->validated(SalesChannels::FIELD);

        return is_string($chosen) && $chosen !== '' ? $chosen : app(SalesChannels::class)->ofGroup($this->validated('kundengruppe'));
    }
}
