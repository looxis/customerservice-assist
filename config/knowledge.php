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

    'categories' => ['complaint', 'product-question', 'order-process-question'],

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
        'max_good_examples' => 3,
        'max_bad_examples' => 2,
        'max_characters' => 60000,
    ],

    'min_order_keyword_length' => 3,

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
