<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/** The dependency direction between modules: a lower one never reaches up, so there are no cycles. */
final class ModuleBoundariesTest extends TestCase
{
    private const ALLOWED = [
        'Language' => [],
        'Ai' => ['Language'],
        'Speech' => ['Ai'],
        'Account' => ['Language'],
        'Progress' => ['Account'],
        'Translator' => ['Ai', 'Language'],
        'Vocabulary' => ['Account', 'Ai', 'Language', 'Progress'],
        'Conversation' => ['Account', 'Ai', 'Language', 'Progress', 'Speech'],
        'Certificate' => ['Account', 'Conversation', 'Language'],
        'Notification' => ['Account', 'Language', 'Progress', 'Vocabulary'],
        'Bootstrap' => ['Account', 'Conversation', 'Language'],
        'Admin' => ['Account', 'Ai', 'Conversation', 'Language', 'Notification', 'Vocabulary'],
    ];

    /** `User` is the root everything hangs off, so its relations are the one place Account points upward. */
    private const USER_MODEL = 'Account/Models/User.php';

    /** @return array<string, array{string}> */
    public static function modules(): array
    {
        $modules = array_map('basename', glob(dirname(__DIR__, 2).'/app/Modules/*', GLOB_ONLYDIR));

        return array_combine($modules, array_map(fn (string $module) => [$module], $modules));
    }

    #[DataProvider('modules')]
    public function test_a_module_uses_only_the_modules_below_it(string $module): void
    {
        $this->assertArrayHasKey($module, self::ALLOWED, "Declare what [{$module}] may depend on.");

        $this->assertSame([], $this->violations($module, [$module, ...self::ALLOWED[$module]]));
    }

    public function test_shared_code_knows_no_module(): void
    {
        $this->assertSame([], $this->referencesIn(dirname(__DIR__, 2).'/app/Shared'));
    }

    /**
     * @param  list<string>  $allowed
     * @return list<string>
     */
    private function violations(string $module, array $allowed): array
    {
        $references = $this->referencesIn(dirname(__DIR__, 2)."/app/Modules/{$module}");

        return array_values(array_filter($references, function (string $reference) use ($allowed): bool {
            [$file, $target] = explode(' -> ', $reference);

            return ! in_array($target, $allowed, true) && ! str_ends_with($file, self::USER_MODEL);
        }));
    }

    /** @return list<string> "file -> module" for every import of a module class */
    private function referencesIn(string $directory): array
    {
        $references = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            preg_match_all('/^use App\\\\Modules\\\\(\w+)\\\\/m', (string) file_get_contents($file->getPathname()), $matches);

            foreach (array_unique($matches[1]) as $target) {
                $references[] = str_replace('\\', '/', $file->getPathname())." -> {$target}";
            }
        }

        return $references;
    }
}
