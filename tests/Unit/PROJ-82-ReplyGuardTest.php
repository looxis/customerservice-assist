<?php

use App\Wismo\ReplyGuard;

const GUARD_LINK = 'https://www.dhl.de/de/privatkunden/pakete-empfangen/verfolgen.html?piececode=00340434171079146248';

it('lets a normal reply with the tracking link pass', function () {
    $text = "Guten Tag,\n\nIhre Sendungsnummer lautet: 00340434171079146248. Weitere Informationen gibt es auf der Webseite von DHL unter „Sendungsverfolgung“.\n\n".GUARD_LINK."\nFalls der Link nicht klickbar ist, bitte kopieren.";

    expect(ReplyGuard::violation($text, [GUARD_LINK]))->toBeNull();
});

it('rejects a link that is not a tracking link of the shipment', function (string $text) {
    expect(ReplyGuard::violation($text.' '.GUARD_LINK, [GUARD_LINK]))->not->toBeNull();
})->with([
    'https link' => 'Bitte hier klicken: https://evil.example/login',
    'http link' => 'http://evil.example',
    'www link' => 'Siehe www.evil.example/abc',
    'bare domain' => 'Mehr unter paket-status.xyz',
    'another dhl link' => 'https://www.dhl.de/other?piececode=1',
]);

it('rejects e-mail addresses, amounts and vouchers', function (string $text) {
    expect(ReplyGuard::violation($text, []))->not->toBeNull();
})->with([
    'e-mail' => 'Schreiben Sie an max@example.org',
    'euro sign' => 'Wir erstatten 25,00 €',
    'euro word' => 'Sie erhalten 10 Euro zurück',
    'eur prefix' => 'EUR 5 Guthaben',
    'voucher' => 'Als Entschuldigung ein Gutschein',
    'discount' => 'Wir geben 20 % Rabatt',
]);

it('rejects a very long text', function () {
    expect(ReplyGuard::violation(str_repeat('a ', 2000), []))->not->toBeNull();
});

it('does not mistake abbreviations for internet addresses', function () {
    expect(ReplyGuard::violation('Das Paket ist z. B. bei der Nachbarin, d. h. nicht verloren. Mo.-Fr. erreichbar.', []))->toBeNull();
});
