<?php

namespace App\Staff;

use Illuminate\Http\Request;

/**
 * The fixed list of staff names and the name chosen in the current browser.
 * Not an authentication: it only attributes analyses and feedback to a person.
 */
class StaffDirectory
{
    /**
     * @param  array{names: list<string>, cookie: string, cookie_minutes: int, admins?: list<string>, test_mode_cookie?: string}  $config
     */
    public function __construct(private readonly array $config) {}

    /**
     * Names in alphabetical order, each once.
     *
     * @return list<string>
     */
    public function names(): array
    {
        $names = array_values(array_unique(array_filter(
            array_map(fn (mixed $name): string => trim((string) $name), $this->config['names']),
            fn (string $name): bool => $name !== '',
        )));

        usort($names, fn (string $a, string $b): int => strcmp($this->sortKey($a), $this->sortKey($b)));

        return $names;
    }

    public function isListed(?string $name): bool
    {
        return $name !== null && in_array($name, $this->names(), true);
    }

    /**
     * The name chosen in this browser, or null when none is chosen or it is no longer listed.
     */
    public function current(Request $request): ?string
    {
        $name = $request->cookie($this->config['cookie']);

        return is_string($name) && $this->isListed($name) ? $name : null;
    }

    /**
     * Whether a listed name may use admin tools (PROJ-32 test mode).
     */
    public function isAdmin(?string $name): bool
    {
        return $this->isListed($name) && in_array($name, $this->config['admins'] ?? [], true);
    }

    public function testModeCookieName(): string
    {
        return $this->config['test_mode_cookie'] ?? 'test_mode';
    }

    public function cookieName(): string
    {
        return $this->config['cookie'];
    }

    public function cookieMinutes(): int
    {
        return $this->config['cookie_minutes'];
    }

    private function sortKey(string $name): string
    {
        return strtr(mb_strtolower($name), ['ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss']);
    }
}
