<?php

use App\Knowledge\KnowledgeIssue;
use App\Knowledge\KnowledgeLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Build a throwaway knowledge base and return the library reading it.
 *
 * @param  array<string, string>  $files  Relative path => file content.
 */
function knowledgeBase(array $files): KnowledgeLibrary
{
    $root = sys_get_temp_dir().'/knowledge-test-'.bin2hex(random_bytes(6));

    File::ensureDirectoryExists($root);

    foreach ($files as $path => $content) {
        File::ensureDirectoryExists(dirname("{$root}/{$path}"));
        File::put("{$root}/{$path}", $content);
    }

    config(['knowledge.path' => $root]);
    app()->forgetScopedInstances();

    return app(KnowledgeLibrary::class);
}

/**
 * A valid knowledge file; overrides change or remove (null) frontmatter fields.
 *
 * @param  array<string, mixed>  $overrides
 */
function knowledgeDoc(array $overrides = [], string $body = "Gilt für alle Kundenarten.\n\n# Regel\n\nEin Satz."): string
{
    $frontmatter = array_merge(['id' => 'POLICY-001', 'title' => 'Titel', 'type' => 'policy', 'status' => 'draft'], $overrides);

    return "---\n".Yaml::dump($frontmatter)."---\n\n".$body."\n";
}

function messagesOf(KnowledgeLibrary $library, string $severity): string
{
    return ($severity === 'error' ? $library->errors() : $library->warnings())
        ->map(fn (KnowledgeIssue $issue): string => "{$issue->path}: {$issue->message}")
        ->implode("\n");
}

/**
 * Remove the throwaway knowledge bases created by knowledgeBase().
 */
function cleanUpKnowledgeBases(): void
{
    foreach (File::glob(sys_get_temp_dir().'/knowledge-test-*') as $directory) {
        File::deleteDirectory($directory);
    }
}

/**
 * @return array<string, mixed>
 */
function zammadArticle(array $overrides = []): array
{
    return array_merge([
        'id' => 1,
        'sender' => 'Customer',
        'type' => 'email',
        'internal' => false,
        'from' => 'Erika Beispiel <erika@example.org>',
        'content_type' => 'text/html',
        'body' => '<p>Das Motiv erscheint nicht.</p>',
        'created_at' => '2026-10-01T07:12:00.000Z',
        'attachments' => [],
    ], $overrides);
}

/**
 * Fake Zammad with one ticket.
 *
 * @param  list<array<string, mixed>>  $articles
 */
function fakeZammad(array $articles = [], array $ticket = [], array $extra = []): void
{
    $ticket = array_merge([
        'id' => 51234,
        'number' => '2137942',
        'title' => 'Zaubertasse – Motiv erscheint nicht',
        'state' => 'open',
        'group' => 'Kundenservice',
        'customer_id' => 77,
        'created_at' => '2026-10-01T07:12:00.000Z',
    ], $ticket);

    Http::preventStrayRequests();
    Http::fake(array_merge([
        'zammad.test/api/v1/tickets/search*' => Http::response([$ticket]),
        'zammad.test/api/v1/ticket_articles/by_ticket/*' => Http::response($articles === [] ? [zammadArticle()] : $articles),
        'zammad.test/api/v1/users/77' => Http::response(['firstname' => 'Erika', 'lastname' => 'Beispiel', 'email' => 'erika@example.org']),
    ], $extra));
}

/**
 * A fictitious Amazon buyer-message notice in Amazon's table layout.
 */
function amazonNotice(string $message, string $order = '402-0000000-0000001', array $products = [['B000TEST01', 'Zaubertasse schwarz']]): string
{
    $rows = implode('', array_map(fn (array $product, int $index): string => '<tr><td> '.($index + 1).' </td><td> '.$product[0].' </td><td> '.$product[1].' </td></tr>', $products, array_keys($products)));

    return '<center><table><tr><td><table><tr><td>'
        .'<p>Du hast eine Nachricht erhalten.</p>'
        .'<p>Bestellnummer '.$order.':</p>'
        .'<table><tbody><tr><td># </td><td>ASIN </td><td>Produktname </td></tr>'.$rows.'</tbody></table>'
        .'<h4><strong>Nachricht:</strong></h4>'
        .'<table><tr><th><pre>'.$message.'</pre></th></tr></table>'
        .'<table><tr><td><a href="https://sellercentral.amazon.it/nms/redirect/x">Fall lösen</a></td></tr></table>'
        .'<p>Dieser Service wird ausschließlich für die Kommunikation mit Käufern angeboten.</p>'
        .'</td></tr></table></td></tr></table></center>';
}
