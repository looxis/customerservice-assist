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
     * A form without a category (Neuversand, PROJ-93) is never preselected, only chosen by hand.
     *
     * @var array<string, array{label: string, category: LabelCategory|null}>
     */
    private const array GUIDED = [
        SupportDecision::FORM => ['label' => 'Technischer Support', 'category' => LabelCategory::TechnischerSupport],
        ComplaintDecision::FORM => ['label' => 'Reklamation', 'category' => LabelCategory::Reklamation],
        ReshipmentDecision::FORM => ['label' => 'Neuversand', 'category' => null],
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
     * The form a ticket of this category starts with: its guided form, else the free text.
     */
    public static function defaultFor(?string $category): string
    {
        foreach (self::GUIDED as $key => $form) {
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
