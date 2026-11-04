<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idioma do site: escolha feita no seletor (sessão) > preferência salva na conta >
 * idioma do navegador > português.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(User::LOCALES);

        $locale = $request->session()->get('locale')
            ?? $request->user()?->locale
            ?? $this->fromBrowser($request, $available);

        App::setLocale(in_array($locale, $available, true) ? $locale : config('app.locale'));

        return $next($request);
    }

    private function fromBrowser(Request $request, array $available): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $language = str_replace('-', '_', $language);
            if (str_starts_with($language, 'pt')) {
                return 'pt_BR';
            }
            if (str_starts_with($language, 'es')) {
                return 'es';
            }
            if (str_starts_with($language, 'en')) {
                return 'en';
            }
        }

        return null;
    }
}
