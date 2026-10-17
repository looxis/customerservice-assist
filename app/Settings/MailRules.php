<?php

namespace App\Settings;

use App\Orders\OrderFormats;
use App\Zammad\AmazonNotice;
use App\Zammad\MessageBody;

/**
 * The rules of the mail cleaning (PROJ-27), built from the settings of the group "Mail-Anzeige": the text lists
 * become the patterns that MessageBody and the Amazon recognition work with.
 */
class MailRules
{
    public function __construct(private readonly SettingsStore $settings) {}

    /**
     * @param  array<string, mixed>  $draft  Values not saved yet (group key => value) that replace the saved ones, for trying out.
     */
    public function messageBody(array $draft = []): MessageBody
    {
        $value = fn (string $key): mixed => array_key_exists($key, $draft) ? $draft[$key] : $this->settings->value($key);
        $hosts = (array) $value('mail_hosts');

        return new MessageBody(
            signatures: (array) $value('mail_signatures'),
            allowedLinkHosts: $this->hostsWith($hosts, 'text'),
            clickableLinkHosts: $this->hostsWith($hosts, 'clickable'),
            footers: (array) $value('mail_footers'),
            quoteHeaderPattern: PatternBuilder::quoteHeaders((array) $value('mail_quote_headers')),
            closingPatterns: PatternBuilder::phrases((array) $value('mail_closings')),
            quoteMarkers: (array) $value('mail_quote_markers'),
            signatureLinesShown: (int) $value('mail_signature_lines'),
            amazon: new AmazonNotice((array) $value('mail_amazon_intros'), (array) $value('mail_amazon_order_labels'), (array) $value('mail_amazon_message_labels'), app(OrderFormats::class)->amazon()->fragment()),
        );
    }

    /**
     * @param  list<array{host: string, mode: string}>  $hosts
     * @return list<string>
     */
    private function hostsWith(array $hosts, string $mode): array
    {
        return array_values(array_map(fn (array $entry): string => $entry['host'], array_filter($hosts, fn (array $entry): bool => ($entry['mode'] ?? null) === $mode)));
    }
}
