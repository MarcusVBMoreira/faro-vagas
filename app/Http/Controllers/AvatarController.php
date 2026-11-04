<?php

namespace App\Http\Controllers;

use App\Services\AvatarProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Foto de perfil opcional. A imagem é recortada em quadrado, reduzida para 400×400
 * e regravada em JPEG (o que também remove dados de localização do arquivo original).
 */
class AvatarController extends Controller
{
    public function store(Request $request, AvatarProcessor $processor): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072', 'dimensions:min_width=100,min_height=100'],
        ]);

        $user = $request->user();

        try {
            $path = $processor->store($request->file('avatar')->getRealPath(), $user->id);
        } catch (\RuntimeException) {
            throw ValidationException::withMessages(['avatar' => __('Não conseguimos processar essa imagem. Tente outra foto.')]);
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        $user->forceFill(['avatar_path' => $path])->save();

        return redirect()->route('profile.show')->with('status', __('Foto atualizada.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return redirect()->route('profile.show')->with('status', __('Foto removida.'));
    }
}
