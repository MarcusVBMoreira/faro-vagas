<?php

namespace App\Services\Matching;

/** Similaridade de cosseno entre dois embeddings (a hospedagem não tem banco vetorial). */
final class Vector
{
    /**
     * @param  list<float>|null  $a
     * @param  list<float>|null  $b
     */
    public static function cosine(?array $a, ?array $b): ?float
    {
        if (! $a || ! $b || count($a) !== count($b)) {
            return null;
        }

        $dot = $na = $nb = 0.0;
        foreach ($a as $i => $x) {
            $y = $b[$i];
            $dot += $x * $y;
            $na += $x * $x;
            $nb += $y * $y;
        }

        if ($na == 0.0 || $nb == 0.0) {
            return null;
        }

        return max(-1.0, min(1.0, $dot / (sqrt($na) * sqrt($nb))));
    }
}
