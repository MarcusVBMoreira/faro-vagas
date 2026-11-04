<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Recorta a foto no centro, reduz para 400×400 e salva em JPEG no disco público.
 * Usa só a extensão GD (disponível na hospedagem), sem bibliotecas extras.
 */
class AvatarProcessor
{
    public const SIZE = 400;

    public function store(string $sourcePath, int $userId): string
    {
        $data = @file_get_contents($sourcePath);
        $source = $data !== false ? @imagecreatefromstring($data) : false;

        if (! $source) {
            throw new RuntimeException('Imagem inválida.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $target = imagecreatetruecolor(self::SIZE, self::SIZE);
        // Fundo branco para PNG/WebP com transparência
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled(
            $target, $source,
            0, 0,
            (int) (($width - $side) / 2), (int) (($height - $side) / 2),
            self::SIZE, self::SIZE,
            $side, $side,
        );

        ob_start();
        imagejpeg($target, null, 85);
        $jpeg = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        $path = 'avatars/'.$userId.'-'.Str::random(16).'.jpg';
        Storage::disk('public')->put($path, $jpeg);

        return $path;
    }
}
