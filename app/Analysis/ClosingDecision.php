<?php

namespace App\Analysis;

use App\Labeling\LabelCategory;
use App\Zammad\ArticleKind;
use App\Zammad\Ticket;

/**
 * The form "Diskussion beenden" in "Vorschlag erstellen" (PROJ-98): the customer repeats the same arguments after
 * the case has been explained and decided, and the employee ends the discussion with a short, firm statement. The
 * form is only offered for some ticket categories and after several customer replies. The app turns the answers into
 * fixed sentences for the language model that override the knowledge and the general tone rules; the internal note
 * for Zammad is a fixed text of the app, never written by the model.
 */
final readonly class ClosingDecision
{
    public const string FORM = 'abschluss';

    public const string OFFER_STANDS = 'besteht';

    public const string NO_OFFER = 'keins';

    public const array OFFER_ANSWERS = [self::OFFER_STANDS, self::NO_OFFER];

    /**
     * The customer replies after our first answer from which on the form is offered.
     */
    public const int MIN_CUSTOMER_REPLIES = 3;

    /**
     * The ticket categories the form is offered for.
     */
    public const array CATEGORIES = [LabelCategory::Wismo, LabelCategory::Reklamation, LabelCategory::Adressaenderung, LabelCategory::Storno];

    /**
     * The internal note the app suggests when the reply is sent.
     */
    public const string NOTE = 'Diskussion abschließend beendet. Weitere Nachrichten zum unveränderten Sachverhalt, ohne neue substantielle Hinweise, die nicht schon zuvor hätten eingebracht werden können, werden nicht mehr kommentiert, sondern nur noch geschlossen.';

    /**
     * @param  'besteht'|'keins'  $offer  Whether an offer made earlier still stands.
     * @param  string  $keyPoint  Optional one sentence of the employee that names the decisive reason.
     */
    public function __construct(
        public string $offer,
        public string $keyPoint = '',
    ) {}

    /**
     * The answers of a request, or null when another form was used or the main question is unanswered.
     *
     * @param  array<string, mixed>  $input  Validated request fields or the stored inputs of an analysis.
     */
    public static function fromInput(array $input): ?self
    {
        if (($input['formular'] ?? null) !== self::FORM || ! in_array($input['abschluss_angebot'] ?? null, self::OFFER_ANSWERS, true)) {
            return null;
        }

        return new self(
            offer: $input['abschluss_angebot'],
            keyPoint: trim((string) ($input['abschluss_kernaussage'] ?? '')),
        );
    }

    /**
     * Whether the form is offered: the ticket category is one of CATEGORIES and the customer has replied at least
     * MIN_CUSTOMER_REPLIES times after our first answer.
     */
    public static function available(?string $category, Ticket $ticket): bool
    {
        $allowed = array_map(fn (LabelCategory $allowed): string => $allowed->value, self::CATEGORIES);

        return in_array($category, $allowed, true) && self::customerReplies($ticket) >= self::MIN_CUSTOMER_REPLIES;
    }

    /**
     * The messages of the customer after our first answer; automatic messages do not count.
     */
    public static function customerReplies(Ticket $ticket): int
    {
        $answered = false;
        $replies = 0;

        foreach ($ticket->articles as $article) {
            if ($article->kind === ArticleKind::Agent) {
                $answered = true;
            } elseif ($answered && $article->kind === ArticleKind::Customer && ! $article->automatic) {
                $replies++;
            }
        }

        return $replies;
    }

    public function offerStands(): bool
    {
        return $this->offer === self::OFFER_STANDS;
    }

    /**
     * The fields to store with the analysis, so a later proposal can prefill the form.
     *
     * @return array<string, string>
     */
    public function storedInput(): array
    {
        return ['formular' => self::FORM, 'abschluss_angebot' => $this->offer, 'abschluss_kernaussage' => $this->keyPoint];
    }

    /**
     * The sentences for the language model, added to the information of the employee.
     */
    public function facts(): string
    {
        $lines = ['Angaben aus dem Formular „Diskussion beenden“ (Entscheidung des Mitarbeiters, sie geht dem gesamten Wissen und allen allgemeinen Vorgaben zu Empathie, Länge, Anknüpfen an frühere Antworten und Pflichtinhalten vor):'];
        $lines[] = '- Der Kunde wiederholt dieselben Fragen und Argumente. Wir beenden die Diskussion jetzt mit einem kurzen, klaren Abschluss.';
        $lines[] = '- Inhalt des Entwurfs: Wir haben den Sachverhalt intensiv geprüft, ihn im Verlauf mehrerer Nachrichten ausführlich erklärt und sind auf alle Fragen und Argumente ausführlich eingegangen'
            .($this->offerStands() ? ' und haben Optionen angeboten' : '')
            .'. Wir haben unseren bisherigen Erklärungen nichts hinzuzufügen und beenden die Diskussion an dieser Stelle.';

        if ($this->offerStands()) {
            $lines[] = '- Die bereits angebotenen Optionen gelten weiterhin: Sage das in genau einem Satz, ohne sie erneut aufzuzählen oder zu erklären.';
        } else {
            $lines[] = '- Es ist kein Angebot offen: Biete nichts an und erwähne keine früheren Angebote.';
        }

        if ($this->keyPoint !== '') {
            $lines[] = '- Nenne als maßgeblichen Grund nur diesen einen Satz des Mitarbeiters, sinngemäß und ohne weitere Erklärung: '.$this->keyPoint;
        } else {
            $lines[] = '- Nenne keinen Grund und wiederhole keine frühere Erklärung.';
        }

        $lines[] = '- Höchstens fünf Sätze zwischen Anrede und Schluss. Sachlich, höflich und unmissverständlich.';
        $lines[] = '- Kein Dank für Verständnis, kein Ausdruck von Verständnis, Bedauern oder Entschuldigung, kein „wir verstehen“, „es tut uns leid“ oder „wir hoffen“. Gehe nicht auf einzelne Argumente oder Fragen der letzten Kundennachricht ein und erkläre nichts erneut.';
        $lines[] = '- Keine neue Zusage, kein neues Angebot, keine Rückfrage, keine Bitte um Bestätigung, keine Ersatzproduktion, keine Erstattung, keine Platzhalter in eckigen Klammern. Regeln, Abläufe und Formulierungshilfen aus dem Wissen setzt du in diesem Entwurf nicht um.';
        $lines[] = '- `recommendation`: Antwort senden und das Ticket schließen. `actions`: keine.';

        return implode("\n", $lines);
    }
}
