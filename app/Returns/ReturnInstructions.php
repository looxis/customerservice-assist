<?php

namespace App\Returns;

/**
 * The fixed texts that go with a return label (PROJ-64): the instructions for the customer, the greeting for a
 * mail of its own and its subject. Written by the app, never by the language model. The wording follows DHL's
 * hints for customers; the pick-up is not ordered, so the customer takes the parcel to a branch or parcel shop.
 *
 * The texts exist in five languages. Where the parcel is handed in depends on the country of the sender, not on
 * the language: in Germany at DHL, abroad at the carrier the label names (DHL's partner in that country).
 */
final class ReturnInstructions
{
    /**
     * Per language: heading, the four steps, the line of the shipment number, and the mail around them. "{label}"
     * stands for the name of the label, step 3 comes from "drop".
     *
     * @var array<string, array{heading: string, pack: string, print: string, keep: string, number: string, label: array{0: string, 1: string}, drop: array{0: string, 1: string}, greeting: string, intro: string, closing: string, subject: string}>
     */
    private const array TEXTS = [
        'de' => [
            'heading' => 'So senden Sie die Ware zurück:',
            'pack' => 'Verpacken Sie die Ware sicher in einem stabilen Karton.',
            'print' => 'Drucken Sie das angehängte {label} (PDF) gut lesbar aus und kleben Sie es auf die größte Fläche des Pakets. Alte Versandetiketten und Barcodes müssen entfernt oder vollständig abgedeckt sein.',
            'keep' => 'Bewahren Sie den Einlieferungsbeleg auf.',
            'number' => 'Retouren-Sendungsnummer: ',
            'label' => ['DHL-Retourenlabel', 'Retourenlabel'],
            'drop' => [
                'Geben Sie das Paket in einer DHL-Filiale oder einem DHL-Paketshop ab. Eine Abholung ist nicht beauftragt.',
                'Geben Sie das Paket bei dem Versanddienst ab, der auf dem Label genannt ist (Postfiliale oder Paketshop). Eine Abholung ist nicht beauftragt.',
            ],
            'greeting' => 'Guten Tag,',
            'intro' => 'anbei erhalten Sie das vorfrankierte {label} für die Rücksendung zu Ihrer Bestellung {order}.',
            'closing' => 'Bei Fragen melden Sie sich gern bei uns.',
            'subject' => 'DHL Retoure – Bestellung ',
        ],
        'en' => [
            'heading' => 'How to return the goods:',
            'pack' => 'Pack the goods securely in a sturdy box.',
            'print' => 'Print the attached {label} (PDF) clearly and stick it on the largest side of the parcel. Old shipping labels and barcodes must be removed or completely covered.',
            'keep' => 'Keep the proof of posting.',
            'number' => 'Return tracking number: ',
            'label' => ['DHL return label', 'return label'],
            'drop' => [
                'Hand in the parcel at a DHL branch or a DHL parcel shop. A pick-up has not been arranged.',
                'Hand in the parcel to the carrier named on the label (post office or parcel shop). A pick-up has not been arranged.',
            ],
            'greeting' => 'Hello,',
            'intro' => 'please find attached the prepaid {label} for returning your order {order}.',
            'closing' => 'If you have any questions, please get in touch with us.',
            'subject' => 'DHL return – order ',
        ],
        'fr' => [
            'heading' => 'Voici comment nous retourner la marchandise :',
            'pack' => 'Emballez soigneusement la marchandise dans un carton solide.',
            'print' => 'Imprimez lisiblement l’{label} ci-jointe (PDF) et collez-la sur la plus grande face du colis. Les anciennes étiquettes d’expédition et les anciens codes-barres doivent être retirés ou entièrement recouverts.',
            'keep' => 'Conservez la preuve de dépôt.',
            'number' => 'Numéro de suivi du retour : ',
            'label' => ['étiquette de retour DHL', 'étiquette de retour'],
            'drop' => [
                'Déposez le colis dans une agence DHL ou un point relais DHL. Aucun enlèvement n’est prévu.',
                'Déposez le colis auprès du transporteur indiqué sur l’étiquette (bureau de poste ou point relais). Aucun enlèvement n’est prévu.',
            ],
            'greeting' => 'Bonjour,',
            'intro' => 'vous trouverez ci-joint l’{label} prépayée pour le renvoi de votre commande {order}.',
            'closing' => 'Pour toute question, n’hésitez pas à nous contacter.',
            'subject' => 'Retour DHL – commande ',
        ],
        'it' => [
            'heading' => 'Ecco come restituire la merce:',
            'pack' => 'Imballi la merce in modo sicuro in un cartone resistente.',
            'print' => 'Stampi in modo ben leggibile l’{label} allegata (PDF) e la incolli sul lato più grande del pacco. Le vecchie etichette di spedizione e i vecchi codici a barre devono essere rimossi o coperti completamente.',
            'keep' => 'Conservi la ricevuta di consegna.',
            'number' => 'Numero di spedizione del reso: ',
            'label' => ['etichetta di reso DHL', 'etichetta di reso'],
            'drop' => [
                'Consegni il pacco presso una filiale DHL o un punto di ritiro DHL. Non è previsto alcun ritiro a domicilio.',
                'Consegni il pacco al corriere indicato sull’etichetta (ufficio postale o punto di ritiro). Non è previsto alcun ritiro a domicilio.',
            ],
            'greeting' => 'Buongiorno,',
            'intro' => 'in allegato trova l’{label} prepagata per la restituzione del Suo ordine {order}.',
            'closing' => 'Per qualsiasi domanda siamo a Sua disposizione.',
            'subject' => 'Reso DHL – ordine ',
        ],
        'nl' => [
            'heading' => 'Zo stuurt u de artikelen terug:',
            'pack' => 'Verpak de artikelen goed in een stevige doos.',
            'print' => 'Print het bijgevoegde {label} (pdf) goed leesbaar en plak het op de grootste zijde van het pakket. Oude verzendlabels en barcodes moeten worden verwijderd of volledig afgedekt.',
            'keep' => 'Bewaar het verzendbewijs.',
            'number' => 'Retourzendingsnummer: ',
            'label' => ['DHL-retourlabel', 'retourlabel'],
            'drop' => [
                'Geef het pakket af bij een DHL-filiaal of een DHL-pakketshop. Er is geen ophaalservice aangevraagd.',
                'Geef het pakket af bij de vervoerder die op het label staat (postkantoor of pakketpunt). Er is geen ophaalservice aangevraagd.',
            ],
            'greeting' => 'Goedendag,',
            'intro' => 'bijgaand ontvangt u het voorgefrankeerde {label} voor het terugsturen van uw bestelling {order}.',
            'closing' => 'Heeft u vragen? Neem dan gerust contact met ons op.',
            'subject' => 'DHL-retour – bestelling ',
        ],
    ];

    /**
     * The steps for the customer, to add to a reply.
     */
    public static function block(string $shipmentNo, ReturnLanguage $language = ReturnLanguage::German, ReturnCountry $country = ReturnCountry::Germany): string
    {
        $text = self::TEXTS[$language->value];
        $abroad = $country->isDomestic() ? 0 : 1;

        return implode("\n", [
            $text['heading'],
            '1. '.$text['pack'],
            '2. '.str_replace('{label}', $text['label'][$abroad], $text['print']),
            '3. '.$text['drop'][$abroad],
            '4. '.$text['keep'],
            '',
            $text['number'].$shipmentNo,
        ]);
    }

    /**
     * The text of a mail that only carries the label.
     */
    public static function mail(string $shipmentNo, string $orderNumber, ReturnLanguage $language = ReturnLanguage::German, ReturnCountry $country = ReturnCountry::Germany): string
    {
        $text = self::TEXTS[$language->value];
        $intro = str_replace(['{label}', '{order}'], [$text['label'][$country->isDomestic() ? 0 : 1], $orderNumber], $text['intro']);

        return $text['greeting']."\n\n".$intro."\n\n".self::block($shipmentNo, $language, $country)."\n\n".$text['closing'];
    }

    /**
     * What the language model is told when a label goes with the reply: only that it exists, never the address or the
     * number. The steps for the customer are added by the app, not written by the model.
     */
    public static function fact(): string
    {
        return 'Ein vorfrankiertes DHL-Retourenlabel wird der Antwort als PDF angehängt; der Kunde schickt die Ware selbst zurück (keine Abholung). Die Schritte zur Rücksendung fügt die App fest unter den Entwurf ein: Schreibe sie nicht selbst, sondern erwähne nur kurz, dass das Label anhängt.';
    }

    public static function subject(string $orderNumber, ReturnLanguage $language = ReturnLanguage::German): string
    {
        return self::TEXTS[$language->value]['subject'].$orderNumber;
    }

    public static function filename(string $orderNumber): string
    {
        return 'DHL-Retoure-'.trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $orderNumber), '_').'.pdf';
    }
}
