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

    'customer_types' => ['b2c', 'b2b'],

    'sales_channels' => ['shop', 'amazon'],

    'categories' => ['complaint', 'product-question', 'order-process-question'],

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
