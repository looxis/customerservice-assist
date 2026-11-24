<?php

namespace App\Analysis;

use App\Labeling\LabelCategory;

/**
 * The forms of "Vorschlag erstellen" (PROJ-90): one guided form per ticket category that has one, and the free
 * text for everything else. A further category becomes one more entry here plus its fields.
 */
final class GuidedForms
{
    /**
     * A form without a category (Neuversand, PROJ-93; Diskussion beenden, PROJ-98) is never preselected, only chosen
     * by hand. "Diskussion beenden" is only offered when ClosingDecision::available() says so.
     *
     * "goods": the form assumes goods and shipping, so a channel without products (PROJ-94) does not offer it.
     *
     * @var array<string, array{label: string, category: LabelCategory|null, goods: bool}>
     */
    private const array GUIDED = [
        SupportDecision::FORM => ['label' => 'Technischer Support', 'category' => LabelCategory::TechnischerSupport, 'goods' => false],
        ComplaintDecision::FORM => ['label' => 'Reklamation', 'category' => LabelCategory::Reklamation, 'goods' => true],
        PaymentDecision::FORM => ['label' => 'Zahlung / Rechnung', 'category' => LabelCategory::Zahlung, 'goods' => false],
        ReshipmentDecision::FORM => ['label' => 'Neuversand', 'category' => null, 'goods' => true],
        ClosingDecision::FORM => ['label' => 'Diskussion beenden', 'category' => null, 'goods' => false],
    ];

    /**
     * The forms an employee can choose from, in the order of the selection.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [...array_map(fn (array $form): string => $form['label'], self::GUIDED), ComplaintDecision::FREE => 'Freitext'];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    /**
     * The forms that assume goods and shipping (PROJ-94).
     *
     * @return list<string>
     */
    public static function forGoods(): array
    {
        return array_keys(array_filter(self::GUIDED, fn (array $form): bool => $form['goods']));
    }

    /**
     * The form a ticket of this category starts with: its guided form, else the free text. Without products
     * (PROJ-94) a form for goods is never the start.
     */
    public static function defaultFor(?string $category, bool $withoutProducts = false): string
    {
        foreach (self::GUIDED as $key => $form) {
            if ($withoutProducts && $form['goods']) {
                continue;
            }

            if ($form['category'] !== null && $form['category']->value === $category) {
                return $key;
            }
        }

        return ComplaintDecision::FREE;
    }

    /**
     * The name of a guided form for the summary of a proposal; null for the free text or an unknown key.
     */
    public static function guidedLabel(mixed $key): ?string
    {
        return is_string($key) ? (self::GUIDED[$key]['label'] ?? null) : null;
    }
}
