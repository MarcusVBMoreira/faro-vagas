<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\VerificationCode;
use App\Notifications\EmailCodeNotification;

/**
 * Códigos de 6 dígitos por e-mail: confirmação do cadastro e login em duas etapas.
 *
 * - O código nunca é salvo: só o HMAC dele (com a APP_KEY).
 * - Vale 15 minutos, aceita no máximo 5 tentativas e só pode ser usado uma vez.
 * - Pedir um código novo invalida os anteriores do mesmo tipo.
 */
class EmailCodeService
{
    public const VERIFY_EMAIL = 'verify_email';

    public const LOGIN = 'login';

    public const TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public function send(User $user, string $purpose): void
    {
        $code = (string) random_int(100000, 999999);

        $user->verificationCodes()->where('purpose', $purpose)->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $user->verificationCodes()->create([
            'purpose' => $purpose,
            'code_hash' => $this->hash($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $user->notify(new EmailCodeNotification($code, $purpose));
    }

    /** Existe um código ainda válido (não usado, não expirado) para esse fim? */
    public function hasActiveCode(User $user, string $purpose): bool
    {
        return $user->verificationCodes()
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function verify(User $user, string $purpose, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);

        $record = $user->verificationCodes()
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || $record->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! hash_equals($record->code_hash, $this->hash($code))) {
            $record->increment('attempts');

            return false;
        }

        $record->update(['consumed_at' => now()]);

        return true;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
