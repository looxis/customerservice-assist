<?php

namespace App\Http\Requests;

use App\Returns\ReturnCountry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Label erzeugen" (PROJ-64): the order, the optional ticket and the address the employee checked. Nothing is
 * cut off silently; the limits are those of the DHL interface. The receiver never comes from the form. Labels exist
 * for the countries of ReturnCountry; the postal code has to fit the country.
 */
class CreateReturnLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        $country = ReturnCountry::tryFrom((string) $this->input('country'));

        return [
            'ticket_number' => ['nullable', 'string', 'regex:/^\d{1,20}$/'],
            'order_number' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9._-]+$/'],
            'eocs_order_id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:50', 'regex:/^[^\x00-\x1F\x7F]*$/u'],
            'street' => ['required', 'string', 'max:50', 'regex:/^[^\x00-\x1F\x7F]*$/u'],
            'house_number' => ['required', 'string', 'max:10', 'regex:/^[^\x00-\x1F\x7F]*$/u'],
            'postal_code' => ['required', 'string', 'max:10', 'regex:'.($country ?? ReturnCountry::Germany)->postalCodePattern()],
            'city' => ['required', 'string', 'max:40', 'regex:/^[^\x00-\x1F\x7F]*$/u'],
            'country' => ['required', Rule::in(ReturnCountry::codes())],
            'confirmed' => ['accepted'],
            'additional' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        $country = ReturnCountry::tryFrom((string) $this->input('country')) ?? ReturnCountry::Germany;
        $unsupported = 'Retourenlabel erzeugt die App nur für Rücksendungen aus '.ReturnCountry::sentence().'. Für andere Länder bitte das Geschäftskundenportal nutzen.';

        return [
            'ticket_number.regex' => 'Die Ticketnummer ist ungültig.',
            'order_number.required' => 'Bitte eine Bestellung wählen.',
            'order_number.regex' => 'Die Bestellnummer ist ungültig.',
            'name.required' => 'Bitte den Namen eintragen.',
            'name.max' => 'Der Name darf höchstens 50 Zeichen lang sein. Bitte kürzen.',
            'street.required' => 'Bitte die Straße eintragen.',
            'street.max' => 'Die Straße darf höchstens 50 Zeichen lang sein. Bitte kürzen.',
            'house_number.required' => 'Bitte die Hausnummer eintragen.',
            'house_number.max' => 'Die Hausnummer darf höchstens 10 Zeichen lang sein.',
            'postal_code.required' => 'Bitte die Postleitzahl eintragen.',
            'postal_code.regex' => $country->postalCodeHint(),
            'postal_code.max' => $country->postalCodeHint(),
            'city.required' => 'Bitte den Ort eintragen.',
            'city.max' => 'Der Ort darf höchstens 40 Zeichen lang sein. Bitte kürzen.',
            'country.in' => $unsupported,
            'country.required' => $unsupported,
            'confirmed.accepted' => 'Bitte bestätigen, dass die Adresse geprüft ist.',
        ];
    }
}
