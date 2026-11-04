<?php

namespace App\Support;

/**
 * Converte o HTML das descrições de vaga em texto simples, mantendo parágrafos e listas.
 */
final class Html
{
    public static function toText(?string $html, int $max = 20000): string
    {
        $html = (string) $html;
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<\s*li\b[^>]*>#i', "\n• ", $html);
        $html = preg_replace('#<\s*(br|/p|/div|/li|/h[1-6]|/ul|/ol|/tr)\b[^>]*>#i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t\x{00A0}]+/u", ' ', $text);
        $text = preg_replace("/ *\n */", "\n", $text);
        $text = trim(preg_replace("/\n{3,}/", "\n\n", $text));

        return mb_substr($text, 0, $max);
    }
}
