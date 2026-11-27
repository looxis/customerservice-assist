<?php

namespace App\Analysis;

use App\Labeling\LabelCategory;

/**
 * The forms of "Ticket bearbeiten", shown as "Geführte Bearbeitung" (PROJ-90, PROJ-103): one guided form per ticket category that has one, and the free
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
     * "categories": the ticket categories the form is preselected for; one form can serve several (PROJ-102).
     *
     * @var array<string, array{label: string, categories: list<LabelCategory>, goods: bool}>
     */
    private const array GUIDED = [
        SupportDecision::FORM => ['label' => 'Technischer Support', 'categories' => [LabelCategory::TechnischerSupport], 'goods' => false],
        ComplaintDecision::FORM => ['label' => 'Reklamation', 'categories' => [LabelCategory::Reklamation], 'goods' => true],
        PaymentDecision::FORM => ['label' => 'Zahlung / Rechnung', 'categories' => [LabelCategory::Zahlung], 'goods' => false],
        WismoUpdateDecision::FORM => ['label' => 'Aktualisierte WISMO-Infos', 'categories' => [LabelCategory::Wismo], 'goods' => true],
        CancellationDecision::FORM => ['label' => 'Storno / Änderung', 'categories' => [LabelCategory::Storno, LabelCategory::Adressaenderung], 'goods' => true],
        ReshipmentDecision::FORM => ['label' => 'Neuversand', 'categories' => [], 'goods' => true],
        ClosingDecision::FORM => ['label' => 'Diskussion beenden', 'categories' => [], 'goods' => false],
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

            if (in_array($category, array_map(fn (LabelCategory $own): string => $own->value, $form['categories']), true)) {
                return $key;
            }
        }

        return ComplaintDecision::FREE;
    }

    /**
     * The ticket categories a guided form stands for (PROJ-103); empty for the free text, an unknown key and the forms
     * without a category (Neuversand, Diskussion beenden).
     *
     * @return list<string>
     */
    public static function categoriesOf(mixed $key): array
    {
        $form = is_string($key) ? (self::GUIDED[$key] ?? null) : null;

        return $form === null ? [] : array_map(fn (LabelCategory $category): string => $category->value, $form['categories']);
    }

    /**
     * The name of a guided form for the summary of a proposal; null for the free text or an unknown key.
     */
    public static function guidedLabel(mixed $key): ?string
    {
        return is_string($key) ? (self::GUIDED[$key]['label'] ?? null) : null;
    }
}
