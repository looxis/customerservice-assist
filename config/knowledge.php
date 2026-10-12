<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Knowledge Base Location
    |--------------------------------------------------------------------------
    |
    | Directory that holds the Markdown knowledge files. The README and the
    | templates folder inside it are never read as knowledge documents.
    |
    */

    'path' => base_path('knowledge'),

    'ignored_files' => ['README.md'],

    'ignored_folders' => ['templates'],

    /*
    |--------------------------------------------------------------------------
    | Deployed Commit
    |--------------------------------------------------------------------------
    |
    | Fallback for the knowledge state when the server has no git repository.
    | The deployment writes the short hash of the released commit here.
    |
    */

    'commit' => env('KNOWLEDGE_COMMIT'),

    /*
    |--------------------------------------------------------------------------
    | Document Types
    |--------------------------------------------------------------------------
    |
    | Every type lives in exactly one folder and has its own ID prefix
    | (e.g. POLICY-001). The order is the precedence of the knowledge base and
    | the order of the overview page. See docs/KNOWLEDGE_BASE_DESIGN.md.
    |
    */

    'types' => [
        'policy' => ['folder' => 'policies', 'prefix' => 'POLICY', 'label' => 'Policies'],
        'permission' => ['folder' => 'permissions', 'prefix' => 'PERMISSION', 'label' => 'Permissions'],
        'product' => ['folder' => 'products', 'prefix' => 'PRODUCT', 'label' => 'Produkte'],
        'process' => ['folder' => 'processes', 'prefix' => 'PROCESS', 'label' => 'Prozesse'],
        'procedure' => ['folder' => 'procedures', 'prefix' => 'PROCEDURE', 'label' => 'Arbeitsabläufe'],
        'playbook' => ['folder' => 'playbooks', 'prefix' => 'PLAYBOOK', 'label' => 'Playbooks'],
        'tone' => ['folder' => 'tone', 'prefix' => 'TONE', 'label' => 'Ton'],
        'glossary' => ['folder' => 'glossary', 'prefix' => 'GLOSSARY', 'label' => 'Glossar'],
        'example-good' => ['folder' => 'examples/good', 'prefix' => 'EXAMPLE-GOOD', 'label' => 'Gute Beispiele'],
        'example-bad' => ['folder' => 'examples/bad', 'prefix' => 'EXAMPLE-BAD', 'label' => 'Schlechte Beispiele'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fixed Value Lists
    |--------------------------------------------------------------------------
    |
    | Values outside these lists are validation errors. Usable statuses are
    | handed to the app; deprecated documents are never used.
    |
    */

    'statuses' => ['draft', 'active', 'deprecated'],

    'usable_statuses' => ['draft', 'active'],

    'customer_types' => ['b2c', 'b2b-reseller', 'b2b-pro', 'b2b-whitelabel'],

    'sales_channels' => ['looxis-de', 'amazon', 'fachhaendler', 'looxis-pro', 'masterpics'],

    'categories' => ['complaint', 'product-question', 'order-process-question', 'quote-request'],

    // What the categories mean, for the language model and the result view.
    'category_labels' => [
        'complaint' => 'Reklamation',
        'product-question' => 'Frage zum Produkt',
        'order-process-question' => 'Frage zum Bestell- oder Produktionsablauf',
        'quote-request' => 'Angebots- oder Auftragsanfrage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions (Vorgänge)
    |--------------------------------------------------------------------------
    |
    | The concrete measure a case is about. One list for procedures
    | (`actions`), permissions (`action`) and the analysis result (PROJ-9,
    | PROJ-30). New values can be added here without code changes.
    |
    */

    'actions' => [
        'return' => 'Retoure',
        'reshipment' => 'Neuversand',
        'reproduction' => 'Neuproduktion',
        'refund' => 'Erstattung',
        'partial-refund' => 'Teilerstattung / Kulanz',
        'cancellation' => 'Storno',
        'address-change' => 'Adressänderung',
        'photo-request' => 'Foto anfordern',
        'escalation' => 'Eskalation / Vier-Augen-Prüfung',
        'invoice-send' => 'Rechnung versenden',
        'quote' => 'Angebot erstellen',
        'order-confirmation' => 'Auftrag bestätigen',
    ],

    /*
    | Sections a procedure must have (level-one headings); missing ones warn.
    */

    'procedure_sections' => ['Voraussetzungen', 'Arbeitsschritte', 'Abschlusskontrolle'],

    /*
    |--------------------------------------------------------------------------
    | Retired Values
    |--------------------------------------------------------------------------
    |
    | Former values that are now errors, with a hint which value replaces them.
    |
    */

    'retired_values' => [
        'sales_channels' => [
            'shop' => 'Der eigene Shop heißt jetzt `looxis-de`.',
        ],
        'customer_types' => [
            'b2b' => 'Stattdessen `b2b-reseller` (Foto-Fachhändler / Reseller), `b2b-pro` (LOOXIS-Pro) oder `b2b-whitelabel` (White-Label-Kunde, z. B. masterpics) verwenden.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Customer Groups
    |--------------------------------------------------------------------------
    |
    | The employee picks one group per case. Each group fixes a customer type
    | and a sales channel; "unclear" fixes neither and only gets documents
    | that apply to everyone. The order is the order of the form field.
    |
    */

    'customer_groups' => [
        'private-looxis-de' => ['label' => 'Privatkunde, looxis.de', 'customer_type' => 'b2c', 'sales_channel' => 'looxis-de'],
        'private-amazon' => ['label' => 'Privatkunde, Amazon', 'customer_type' => 'b2c', 'sales_channel' => 'amazon'],
        'reseller' => ['label' => 'Foto-Fachhändler / Reseller', 'customer_type' => 'b2b-reseller', 'sales_channel' => 'fachhaendler'],
        'looxis-pro' => ['label' => 'LOOXIS-Pro', 'customer_type' => 'b2b-pro', 'sales_channel' => 'looxis-pro'],
        'whitelabel-masterpics' => ['label' => 'White-Label-Kunde, masterpics', 'customer_type' => 'b2b-whitelabel', 'sales_channel' => 'masterpics'],
        'unclear' => ['label' => 'Noch unklar', 'customer_type' => null, 'sales_channel' => null],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Channel Names
    |--------------------------------------------------------------------------
    |
    | Translates the channel name of an order (as EOCS delivers it) into a
    | sales channel, to suggest the customer group. Compared without regard
    | to case. Unknown names suggest "unclear". Filled in with PROJ-7.
    |
    */

    'order_channels' => [
        'looxis-de' => 'looxis-de',
        'shop' => 'looxis-de',
        'looxis.de' => 'looxis-de',
        'looxis.de vanilo' => 'looxis-de',
        'fachhaendler.looxis.de' => 'fachhaendler',
        'reseller.looxis.fr' => 'fachhaendler',
        'looxis-pro.com' => 'looxis-pro',
        'masterpics white label de' => 'masterpics',
        'amazon' => 'amazon',
        'fachhaendler' => 'fachhaendler',
        'fachhaendler.looxis.de' => 'fachhaendler',
        'looxis-pro' => 'looxis-pro',
        'looxis.fr' => 'fachhaendler',
        'masterpics' => 'masterpics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Selection Limits
    |--------------------------------------------------------------------------
    |
    | At most this many reference cases go into one selection. Above the
    | character limit (document text without frontmatter) reference cases are
    | dropped first; binding knowledge is never cut.
    |
    */

    'selection' => [
        // Instructions for people, never sent to the language model (PROJ-30).
        'excluded_types' => ['procedure'],

        'max_good_examples' => 3,
        'max_bad_examples' => 2,
        'max_characters' => 60000,
    ],

    'min_order_keyword_length' => 3,

    // Customer terms (PROJ-33) are matched in free text, so they need a little more.
    'min_customer_term_length' => 4,

    /*
    |--------------------------------------------------------------------------
    | Targeted Retrieval (PROJ-36)
    |--------------------------------------------------------------------------
    |
    | `classes`: how a document is retrieved. `required` switches off the
    | transition mode: while false, a document without `retrieval` counts as
    | `core` and is only listed as "not classified yet"; once the migration is
    | done it becomes an error.
    |
    | `when_keys`: the keys a `when` condition may use. `level` says whether the
    | value belongs to the whole case or to one order, `type` how it is written
    | (bool, number, list = one of several values, slugs = product slugs),
    | `unavailable_for_groups` the customer groups for which the value is never
    | known. Prüfung, Übersicht und Auswertung read this one list.
    |
    */

    'retrieval' => [
        'classes' => [
            'core' => 'Grundwissen',
            'triggered' => 'Bei Bedarf',
            'binding' => 'Verbindlich',
        ],

        'required' => (bool) env('KNOWLEDGE_RETRIEVAL_REQUIRED', false),

        'min_trigger_length' => 4,

        'max_triggers' => 25,

        'when_keys' => [
            'customer_type' => ['level' => 'case', 'type' => 'list', 'values' => 'customer_types', 'source' => 'Auswahl des Mitarbeiters (Kundengruppe)', 'availability' => 'außer bei „noch unklar“', 'unavailable_for_groups' => ['unclear']],
            'sales_channel' => ['level' => 'case', 'type' => 'list', 'values' => 'sales_channels', 'source' => 'Auswahl des Mitarbeiters (Kundengruppe)', 'availability' => 'außer bei „noch unklar“', 'unavailable_for_groups' => ['unclear']],
            'products' => ['level' => 'case', 'type' => 'slugs', 'source' => 'Auswahl des Mitarbeiters', 'availability' => 'immer (leer = kein Produkt)'],
            'ticket_closed' => ['level' => 'case', 'type' => 'bool', 'source' => 'Zammad-Status', 'availability' => 'immer'],
            'ticket_age_days' => ['level' => 'case', 'type' => 'number', 'source' => 'Erstellzeit des Tickets', 'availability' => 'immer'],
            'customer_message_count' => ['level' => 'case', 'type' => 'number', 'source' => 'Zählung der Kundennachrichten', 'availability' => 'immer'],
            'current_message_has_attachment' => ['level' => 'case', 'type' => 'bool', 'source' => 'Anhänge der aktuellen Kundennachricht', 'availability' => 'immer'],
            'message_foreign' => ['level' => 'case', 'type' => 'bool', 'source' => 'Spracherkennung (Heuristik)', 'availability' => 'nicht bei sehr kurzen Texten'],
            'order_channel' => ['level' => 'order', 'type' => 'list', 'values' => 'sales_channels', 'source' => 'EOCS-Kanal über die Zuordnungstabelle', 'availability' => 'nur mit geladener Bestellung'],
            'order_age_days' => ['level' => 'order', 'type' => 'number', 'source' => 'EOCS-Bestelldatum', 'availability' => 'nur mit geladener Bestellung'],
            'shipped' => ['level' => 'order', 'type' => 'bool', 'source' => 'Sendungen aus EOCS (mindestens eine)', 'availability' => 'nur mit geladener Bestellung und Sendung'],
            'days_since_shipped' => ['level' => 'order', 'type' => 'number', 'source' => 'frühestes Versanddatum', 'availability' => 'nur mit lesbarem Versanddatum'],
            'days_since_last_shipped' => ['level' => 'order', 'type' => 'number', 'source' => 'spätestes Versanddatum', 'availability' => 'nur mit lesbarem Versanddatum'],
            'shipment_count' => ['level' => 'order', 'type' => 'number', 'source' => 'Anzahl der Sendungen aus EOCS', 'availability' => 'nur mit geladener Sendung'],
            'delivery_confirmed' => ['level' => 'order', 'type' => 'bool', 'source' => 'Zustellkennzeichen (mindestens eine Sendung)', 'availability' => 'nur mit Zustellkennzeichen'],
            'all_shipments_delivered' => ['level' => 'order', 'type' => 'bool', 'source' => 'Zustellkennzeichen aller geladenen Sendungen', 'availability' => 'nur wenn alle Sendungen gekennzeichnet sind'],
            'has_claim' => ['level' => 'order', 'type' => 'bool', 'source' => 'Folgebestellung nach dem Schema R<n>-…', 'availability' => 'nur wenn gefunden'],
            'claim_count' => ['level' => 'order', 'type' => 'number', 'source' => 'gefundene Folgebestellungen (Mindestanzahl)', 'availability' => 'nur wenn gefunden'],
            'item_quantity_total' => ['level' => 'order', 'type' => 'number', 'source' => 'Summe der Positionsmengen', 'availability' => 'nur mit geladenen Positionen'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    |
    | Documents with a longer body probably cover more than one topic and
    | get a warning.
    |
    */

    'max_body_length' => 8000,

];
