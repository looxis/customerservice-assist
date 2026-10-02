<?php

use App\Knowledge\KnowledgeIssue;
use App\Knowledge\KnowledgeLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
