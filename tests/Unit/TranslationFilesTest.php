<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * As chaves de tradução são as próprias frases em português.
 * Este teste garante que pt_BR, en e es têm exatamente as mesmas chaves
 * e que toda frase usada com __('...') no código está traduzida.
 */
class TranslationFilesTest extends TestCase
{
    private const LOCALES = ['pt_BR', 'en', 'es'];

    private function load(string $locale): array
    {
        $path = dirname(__DIR__, 2)."/lang/{$locale}.json";
        $this->assertFileExists($path);
        $data = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return $data;
    }

    public function test_all_languages_have_the_same_keys(): void
    {
        $base = array_keys($this->load('pt_BR'));
        sort($base);

        foreach (['en', 'es'] as $locale) {
            $keys = array_keys($this->load($locale));
            sort($keys);
            $this->assertSame([], array_values(array_diff($base, $keys)), "Faltam em {$locale}.json");
            $this->assertSame([], array_values(array_diff($keys, $base)), "Sobram em {$locale}.json");
        }
    }

    public function test_placeholders_are_kept_in_every_translation(): void
    {
        $translations = array_map(fn ($l) => $this->load($l), array_combine(self::LOCALES, self::LOCALES));

        foreach ($translations['pt_BR'] as $key => $_) {
            preg_match_all('/:[a-z_]+/', $key, $expected);
            foreach (['en', 'es'] as $locale) {
                preg_match_all('/:[a-z_]+/', $translations[$locale][$key], $found);
                $this->assertEqualsCanonicalizing($expected[0], $found[0], "Placeholder diferente em {$locale}: {$key}");
                $this->assertNotSame('', trim($translations[$locale][$key]), "Tradução vazia em {$locale}: {$key}");
            }
        }
    }

    public function test_every_string_used_in_the_code_is_translated(): void
    {
        $known = $this->load('pt_BR');
        $root = dirname(__DIR__, 2);
        $missing = [];

        foreach (['app', 'resources/views', 'routes'] as $dir) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("{$root}/{$dir}"));
            foreach ($files as $file) {
                if (! str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                preg_match_all("/__\\(\\s*'((?:[^'\\\\]|\\\\.)*)'/", file_get_contents($file->getPathname()), $m);
                foreach ($m[1] as $key) {
                    $key = str_replace("\\'", "'", $key);
                    if (! isset($known[$key]) && ! preg_match('/^(auth|passwords|validation)\./', $key)) {
                        $missing[] = $key.'  ('.basename($file->getPathname()).')';
                    }
                }
            }
        }

        $this->assertSame([], array_values(array_unique($missing)), 'Frases sem tradução em lang/*.json');
    }
}
