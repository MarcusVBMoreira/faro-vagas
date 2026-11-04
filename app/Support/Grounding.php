<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Confere se um texto gerado pela IA está apoiado no texto original (currículo ou vaga).
 * Modelos pequenos às vezes copiam exemplos do prompt ou inventam habilidades.
 */
final class Grounding
{
    private const STOP = ['com', 'para', 'por', 'dos', 'das', 'nos', 'nas', 'uma', 'que', 'and', 'the', 'with', 'em', 'de', 'do', 'da', 'e', 'ou', 'of', 'in', 'to', 'a', 'o'];

    public static function plain(string $text): string
    {
        return ' '.Str::lower(Str::ascii($text)).' ';
    }

    /** Pelo menos $ratio das palavras relevantes de $phrase aparecem em $plainSource (já passado por plain()). */
    public static function isGrounded(string $phrase, string $plainSource, float $ratio = 0.5): bool
    {
        $words = array_filter(
            preg_split('/[^a-z0-9#+]+/', Str::lower(Str::ascii($phrase))) ?: [],
            fn ($w) => mb_strlen($w) >= 2 && ! in_array($w, self::STOP, true),
        );

        if ($words === []) {
            return true;
        }

        // Prefixo de 5 letras tolera plural e flexão ("faturamento" x "faturar")
        $found = array_filter($words, fn ($w) => str_contains($plainSource, mb_strlen($w) > 5 ? mb_substr($w, 0, 5) : $w));

        return count($found) / count($words) >= $ratio;
    }
}
