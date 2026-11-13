<?php

use App\Tracking\DeliveryText;

it('knows a reminder to collect the parcel soon', function (string $text) {
    expect(DeliveryText::isPickupReminder($text))->toBeTrue()
        ->and(DeliveryText::isProblem($text))->toBeFalse()
        ->and(DeliveryText::isPickupReady($text))->toBeTrue();
})->with([
    'ups german' => 'Holen Sie Ihr Paket bald ab, da es sich dem maximalen Aufbewahrungslimit nähert und eventuell an den Absender zurückgesendet wird.',
    'english' => 'Please collect your parcel soon, otherwise it may be returned to sender.',
]);

it('keeps a return in progress, an expired storage time and a missed pickup as problems', function (string $text) {
    expect(DeliveryText::isPickupReminder($text))->toBeFalse()
        ->and(DeliveryText::isProblem($text))->toBeTrue();
})->with([
    'return' => 'Wird an Absender zurückgesendet',
    'expired' => 'Die max. Aufbewahrungsdauer am UPS Access Point Standort ist abgelaufen. Der Absender erhält das Paket zurück.',
    'not collected' => 'Das Paket wurde nicht abgeholt.',
    'deadline' => 'Die Abholfrist ist abgelaufen.',
]);

it('treats empty texts as neither', function () {
    expect(DeliveryText::isPickupReminder(null))->toBeFalse()->and(DeliveryText::isProblem(''))->toBeFalse();
});
