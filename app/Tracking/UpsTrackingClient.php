<?php

namespace App\Tracking;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The UPS Tracking API (PROJ-82), signed in with OAuth client credentials; the token is cached until shortly before it
 * ends. The pickup address comes from `accessPointInformation` and only counts when it is complete.
 */
class UpsTrackingClient implements TrackingClient
{
    private const string TOKEN_KEY = 'ups.tracking.token';

    /**
     * @param  array{url: ?string, client_id: ?string, client_secret: ?string, timeout: int|string}  $config
     */
    public function __construct(private readonly array $config) {}

    public function track(string $trackingNumber, ?string $postalCode): TrackingStatus
    {
        $link = Carriers::link('UPS', $trackingNumber);
        $response = $this->get($trackingNumber, $this->token());

        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_KEY);
            $response = $this->get($trackingNumber, $this->token());
        }

        if (in_array($response->status(), [400, 404], true)) {
            return TrackingStatus::unknown('UPS', $trackingNumber, $link);
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new TrackingException('UPS-Tracking: Zugang abgelehnt (HTTP '.$response->status().').', false);
        }

        if (! $response->successful()) {
            throw new TrackingException('UPS-Tracking: HTTP '.$response->status().'.');
        }

        $package = $response->json('trackResponse.shipment.0.package.0');

        return is_array($package) ? $this->status($package, $trackingNumber, $link) : TrackingStatus::unknown('UPS', $trackingNumber, $link);
    }

    private function get(string $trackingNumber, string $token): Response
    {
        try {
            return Http::withToken($token)->withHeaders(['transId' => 'csa-'.bin2hex(random_bytes(8)), 'transactionSrc' => 'customerservice-assist', 'Accept' => 'application/json'])
                ->timeout((int) $this->config['timeout'])
                ->get(rtrim((string) $this->config['url'], '/').'/api/track/v1/details/'.rawurlencode($trackingNumber), ['locale' => 'de_DE']);
        } catch (ConnectionException) {
            throw new TrackingException('UPS ist nicht erreichbar.');
        }
    }

    private function token(): string
    {
        if (blank($this->config['client_id'] ?? null) || blank($this->config['client_secret'] ?? null) || blank($this->config['url'] ?? null)) {
            throw new TrackingException('UPS-Tracking ist nicht eingerichtet (UPS_CLIENT_ID, UPS_CLIENT_SECRET).', false);
        }

        $cached = Cache::get(self::TOKEN_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $response = Http::asForm()->withBasicAuth((string) $this->config['client_id'], (string) $this->config['client_secret'])
                ->timeout((int) $this->config['timeout'])
                ->post(rtrim((string) $this->config['url'], '/').'/security/v1/oauth/token', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException) {
            throw new TrackingException('UPS ist nicht erreichbar.');
        }

        if (in_array($response->status(), [400, 401, 403], true)) {
            throw new TrackingException('UPS-Anmeldung abgelehnt (HTTP '.$response->status().').', false);
        }

        $token = (string) $response->json('access_token');

        if (! $response->successful() || $token === '') {
            throw new TrackingException('UPS-Anmeldung: HTTP '.$response->status().'.');
        }

        Cache::put(self::TOKEN_KEY, $token, max(60, (int) $response->json('expires_in', 3600) - 120));

        return $token;
    }

    /**
     * @param  array<string, mixed>  $package
     */
    private function status(array $package, string $trackingNumber, ?string $link): TrackingStatus
    {
        $activities = array_values(array_filter((array) ($package['activity'] ?? []), 'is_array'));
        $latest = $activities[0] ?? [];
        $current = (array) ($package['currentStatus'] ?? []);
        $latestText = DeliveryText::strip($latest['status']['description'] ?? '');
        $currentText = DeliveryText::strip($current['description'] ?? '');
        $simple = DeliveryText::strip($current['simplifiedTextDescription'] ?? '');
        $problem = null;

        foreach ([$currentText, ...array_map(fn (array $activity): string => DeliveryText::strip($activity['status']['description'] ?? ''), $activities)] as $text) {
            if (DeliveryText::isProblem($text)) {
                $problem = $text;

                break;
            }
        }

        $dates = collect((array) ($package['deliveryDate'] ?? []))->filter(fn (mixed $entry): bool => is_array($entry));
        $delivered = ($latest['status']['type'] ?? null) === 'D' || $dates->contains(fn (array $entry): bool => ($entry['type'] ?? null) === 'DEL');
        $estimate = $dates->first(fn (array $entry): bool => ($entry['type'] ?? null) !== 'DEL' && ! empty($entry['date']));
        $ready = ! $delivered && DeliveryText::isPickupReady($currentText.' '.$simple.' '.$latestText);

        return new TrackingStatus(
            carrier: 'UPS',
            trackingNumber: $trackingNumber,
            known: true,
            status: $simple !== '' ? $currentText.' – '.$simple : ($currentText !== '' ? $currentText : ($latestText !== '' ? $latestText : 'Status unbekannt')),
            location: self::city($latest),
            at: self::time($latest),
            delivered: $delivered,
            deliveryDetails: $delivered ? ($latestText !== '' ? $latestText : $currentText) : null,
            receivedBy: $delivered ? (trim((string) ($package['deliveryInformation']['receivedBy'] ?? '')) ?: null) : null,
            pickupLocation: $delivered || $problem !== null ? null : self::accessPoint($package['accessPointInformation'] ?? null),
            pickupReady: $ready,
            pickupDeadline: self::date($package['accessPointInformation']['pickupByDate'] ?? null),
            estimate: is_array($estimate) ? self::date($estimate['date']) : null,
            problem: $problem,
            link: $link,
        );
    }

    /**
     * The address of the access point as one line, only when street, postcode and city are all there; otherwise null,
     * so that no part of an address is ever passed off as the pickup point.
     */
    private static function accessPoint(mixed $info): ?string
    {
        $address = is_array($info) ? (array) ($info['address'] ?? []) : [];
        $text = fn (string $key): string => trim((string) ($address[$key] ?? ''));

        if ($text('addressLine1') === '' || $text('postalCode') === '' || $text('city') === '') {
            return null;
        }

        $street = trim($text('addressLine1').' '.$text('addressLine2'));
        $country = $text('countryCode') !== '' ? $text('countryCode') : $text('country');

        return collect([trim((string) ($info['companyName'] ?? '')), $street, $text('postalCode').' '.$text('city'), $country])->filter()->implode(', ');
    }

    /**
     * @param  array<string, mixed>  $activity
     */
    private static function city(array $activity): ?string
    {
        $city = trim((string) ($activity['location']['address']['city'] ?? ''));

        return $city === '' ? null : $city;
    }

    /**
     * @param  array<string, mixed>  $activity
     */
    private static function time(array $activity): ?string
    {
        $date = (string) ($activity['date'] ?? '');
        $time = (string) ($activity['time'] ?? '');

        if (preg_match('/^\d{8}$/', $date) !== 1) {
            return null;
        }

        try {
            $at = CarbonImmutable::createFromFormat('Ymd His', $date.' '.(preg_match('/^\d{6}$/', $time) === 1 ? $time : '000000'), 'Europe/Berlin');

            return $at->format('d.m.Y').(preg_match('/^\d{6}$/', $time) === 1 ? ', '.$at->format('H:i').' Uhr' : '');
        } catch (\Throwable) {
            return null;
        }
    }

    private static function date(mixed $value): ?string
    {
        $value = (string) $value;

        if (preg_match('/^\d{8}$/', $value) !== 1) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Ymd', $value, 'Europe/Berlin')->format('d.m.Y');
        } catch (\Throwable) {
            return null;
        }
    }
}
