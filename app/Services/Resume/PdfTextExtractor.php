<?php

namespace App\Services\Resume;

use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Extrai o texto de um PDF. PDFs escaneados (imagem) não têm texto e são recusados.
 */
class PdfTextExtractor
{
    private const MIN_CHARS = 80;

    public function extract(string $path): string
    {
        try {
            $text = (new Parser)->parseFile($path)->getText();
        } catch (Throwable $e) {
            throw new UnreadablePdfException('Não foi possível abrir o PDF.', previous: $e);
        }

        $text = str_replace(["\r\n", "\r", "\t", "\u{00A0}"], ["\n", "\n", ' ', ' '], $text);
        $text = preg_replace('/[ ]{2,}/u', ' ', $text);
        $text = preg_replace('/\n\s*\n\s*\n+/u', "\n\n", $text);
        $text = trim((string) $text);

        if (mb_strlen($text) < self::MIN_CHARS) {
            throw new UnreadablePdfException('O PDF não tem texto suficiente (pode ser uma imagem escaneada).');
        }

        return $text;
    }
}
