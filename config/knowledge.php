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
        'policy' => ['folder' => 'policies', 'prefix' => 'POLICY', 'label' => 'Policies',
            'description' => 'Verbindliche Regeln des Unternehmens: was gilt bei Reklamationen, Ersatz, Rückgabe, Kulanz und Lieferung.',
        ],
        'permission' => ['folder' => 'permissions', 'prefix' => 'PERMISSION', 'label' => 'Permissions',
            'description' => 'Befugnisse: was der Kundenservice selbst zusagen darf und wofür eine Freigabe nötig ist.',
        ],
        'product' => ['folder' => 'products', 'prefix' => 'PRODUCT', 'label' => 'Produkte',
            'description' => 'Regeln und Wissen konkret zu Produkten: Eigenschaften, Produktion und typische Besonderheiten.',
        ],
        'process' => ['folder' => 'processes', 'prefix' => 'PROCESS', 'label' => 'Prozesse',
            'description' => 'Interne Abläufe des Unternehmens, die bei einem Fall eingehalten werden müssen, zum Beispiel Prüfung und Freigabe.',
        ],
        'procedure' => ['folder' => 'procedures', 'prefix' => 'PROCEDURE', 'label' => 'Arbeitsabläufe',
            'description' => 'Schritt-für-Schritt-Anleitungen für Mitarbeiter, die nach der Analyse als Arbeitsablauf zum Fall vorgeschlagen werden.',
        ],
        'playbook' => ['folder' => 'playbooks', 'prefix' => 'PLAYBOOK', 'label' => 'Playbooks',
            'description' => 'Vorgehen für wiederkehrende Fallmuster: woran man den Fall erkennt, wie man ihn prüft und was man dem Kunden anbietet.',
        ],
        'tone' => ['folder' => 'tone', 'prefix' => 'TONE', 'label' => 'Ton',
            'description' => 'Anweisungen, in welchem Ton und mit welchen Formulierungen Antworten an Kunden formuliert werden.',
        ],
        'glossary' => ['folder' => 'glossary', 'prefix' => 'GLOSSARY', 'label' => 'Glossar',
            'description' => 'Fachbegriffe und ihre Bedeutung, damit Begriffe von Kunden und intern einheitlich verstanden werden.',
        ],
        'example-good' => ['folder' => 'examples/good', 'prefix' => 'EXAMPLE-GOOD', 'label' => 'Gute Beispiele',
            'description' => 'Vorbildliche Beispiele für Bewertung und Antworten, an denen sich Vorschläge orientieren.',
        ],
        'example-bad' => ['folder' => 'examples/bad', 'prefix' => 'EXAMPLE-BAD', 'label' => 'Schlechte Beispiele',
            'description' => 'Schlechte Beispiele, die zeigen, was in Bewertung und Antwort vermieden werden soll.',
        ],
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

    'statuses_labels' => ['draft' => 'Entwurf', 'active' => 'Gültig', 'deprecated' => 'Veraltet'],

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
        'goodwill-reproduction' => 'Kulanz-Neuanfertigung',
        'refund' => 'Erstattung',
        'partial-refund' => 'Teilerstattung / Preisnachlass',
        'cancellation' => 'Storno',
        'address-change' => 'Adressänderung',
        'photo-request' => 'Foto anfordern',
        'escalation' => 'Eskalation / Vier-Augen-Prüfung',
        'invoice-send' => 'Rechnung versenden',
        'quote' => 'Angebot erstellen',
        'order-confirmation' => 'Auftrag bestätigen',
        'pickup' => 'Selbstabholung',
        'account-closure' => 'Kundenkonto schließen',
    ],

    /*
    |--------------------------------------------------------------------------
    | Links to internal tools in procedures (PROJ-58)
    |--------------------------------------------------------------------------
    |
    | A procedure writes `[text](link:key)`. These keys have a fixed address;
    | `eocs-auftrag` and `zammad-ticket` depend on the case (App\Knowledge\CaseLinks).
    | The addresses can be overridden with LINK_EOCS_CUSTOMERS, LINK_VANILO_SHOPPERS
    | and LINK_MAILCOACH_LISTS. Only https addresses are used.
    |
    */

    'tool_links' => [
        'eocs-kunden' => env('LINK_EOCS_CUSTOMERS', 'https://eocs.loox.is/customers'),
        'vanilo-kunden' => env('LINK_VANILO_SHOPPERS', 'https://looxis.de/manage/shopper'),
        'mailcoach-listen' => env('LINK_MAILCOACH_LISTS', 'https://looxis.mailcoach.app/email-lists'),
    ],

    /*
    | Links to one order in an external tool: the address with `{number}` for the order number
    | (Amazon order numbers only, checked by format). Override with LINK_AMAZON_ORDER.
    */

    'order_links' => [
        'amazon-bestellung' => env('LINK_AMAZON_ORDER', 'https://sellercentral.amazon.de/orders-v3/order/{number}'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Action hints
    |--------------------------------------------------------------------------
    |
    | One sentence per action that tells the model when to name it. Sent with the
    | list of actions; the labels above are for people. Keeps similar actions apart
    | (a free replacement is not a paid goodwill reproduction).
    |
    */

    'action_hints' => [
        'reproduction' => 'kostenfreie Neuproduktion oder Ersatz bei einer berechtigten Reklamation (Mangel, eigener Fehler)',
        'goodwill-reproduction' => 'kostenpflichtige oder vergünstigte Neuanfertigung aus Kulanz, weil der Fehler beim Kunden lag (Kundenfehler, Dateifehler, Tippfehler) oder die Reklamation unberechtigt ist und der Kunde eine andere Ausführung wünscht; nie bei berechtigter Reklamation',
        'partial-refund' => 'Teilerstattung oder Preisnachlass ohne Neuanfertigung',
        'account-closure' => 'Kundenkonto eines Fachhändlers schließen, nachdem er die Geschäftsbeziehung schriftlich beendet hat (Mailcoach, Vanilo, EOCS)',
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

        // Whether the analysis uses the targeted retrieval at all. Off until the documents are
        // migrated and `knowledge:check` reports readiness; then the analysis behaves as before.
        'active' => (bool) env('KNOWLEDGE_RETRIEVAL_ACTIVE', false),

        // Version of the retrieval rules; raise it whenever the logic changes. Stored with every
        // analysis together with a short hash of the settings that were in force.
        'version' => '2026-10-13.1',

        // Provisional values; the final ones are set after the migration (PROJ-36).
        'budget' => [
            // Characters of knowledge per analysis the retrieval aims at.
            'target' => 40000,
            // Mandatory knowledge (core and binding without trigger) not bound to a product.
            'core' => 15000,
            // Mandatory knowledge bound to one product.
            // Raised from 6.000 because PRODUCT-003 (3D glass photos) has 8.643 characters; lower it again once the file is split.
            'core_per_product' => 9000,
        ],

        'min_trigger_length' => 4,

        // A summary shorter than this gets a soft warning: the router chooses documents by ID, title and summary (PROJ-37).
        'summary_min_length' => (int) env('KNOWLEDGE_SUMMARY_MIN_LENGTH', 60),

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
    | Router (PROJ-37)
    |--------------------------------------------------------------------------
    |
    | A cheap model call before the analysis adds documents the triggers missed.
    | It only ever adds, from a pool of documents the retrieval did not take, and
    | only works when the targeted retrieval is on. Values marked provisional are
    | calibrated with the live check set before the router is switched on.
    |
    */

    'router' => [
        'active' => (bool) env('KNOWLEDGE_ROUTER_ACTIVE', false),

        // Raise when the router logic or its settings change; stored with every analysis.
        'version' => '2026-10-13.2',

        // When true, only proposals the model rates as clearly fitting are taken over and the others are logged as rejected.
        // Off by default: the first QA runs showed that the filter dropped a relevant document in 3 of 15 Eisberg runs.
        // The `fit` rating is still stored for diagnosis.
        'clear_only' => (bool) env('KNOWLEDGE_ROUTER_CLEAR_ONLY', false),

        // Own model setting so the router can be changed independently of the analysis.
        // First live check set (2026-10-13): gpt-5.4 19 of 20 and 5 of 5 on the Eisberg case in about 4 s; gpt-5.4-mini 15 of 20 and 2 of 5; gpt-5.5 18 of 20 and 5 of 5 but about 10 s.
        'model' => env('ROUTER_MODEL', 'gpt-5.4'),

        // Seconds; no automatic retry.
        'timeout' => (int) env('ROUTER_TIMEOUT', 15),

        // Provisional: the router sees at most this many catalogue lines / characters.
        'pool' => [
            'max_lines' => 60,
            'max_characters' => 15000,
            // Share of the places reserved for documents with a specific scope (product or customer group).
            'reserved_share' => 0.34,
        ],

        // Provisional: the most documents one analysis takes over from the router.
        'max_additions' => 6,

        // Provisional: characters of customer text sent to the router.
        'text_characters' => 12000,

        // A message made only of these words (and no attachment, no question) has no content of its own.
        'courtesy_words' => ['danke', 'dankeschön', 'vielen', 'dank', 'ok', 'okay', 'perfekt', 'super', 'gut', 'alles', 'klar', 'prima', 'passt', 'schön', 'und', 'für', 'ihre', 'ihr', 'eure', 'die', 'der', 'das', 'schnelle', 'hilfe', 'antwort', 'bemühungen', 'mühe', 'bis', 'bald', 'liebe', 'lieber', 'grüße', 'grüsse', 'gruß', 'gruss', 'viele', 'freundliche', 'mit', 'freundlichen', 'mfg', 'lg', 'hallo', 'guten', 'tag', 'morgen', 'abend', 'ihnen', 'euch', 'ebenfalls', 'schönen', 'tolle', 'wochenende', 'thanks', 'thank', 'you', 'merci', 'grazie', 'ciao'],

        // Optional price per million tokens by model; without an entry no cost is shown.
        'prices' => [
            // 'gpt-5.4-mini' => ['input' => 0.0, 'output' => 0.0],
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

    /*
    |--------------------------------------------------------------------------
    | Reports "please check" (PROJ-56)
    |--------------------------------------------------------------------------
    |
    | A document counts as "reported several times" with this many open reports
    | from at least this many different staff members. Reports never switch a
    | document off.
    |
    */

    'review' => ['many_flags' => 3, 'many_staff' => 2],

    /*
    |--------------------------------------------------------------------------
    | Trial period (PROJ-56)
    |--------------------------------------------------------------------------
    |
    | After the last day of a trial a document stays in use with a warning for
    | this many days. Without a decision by then it is no longer retrieved
    | until an admin decides. An extension may reach at most this far ahead.
    |
    */

    'trial' => ['grace_days' => 30, 'max_extension_days' => 366],

];
