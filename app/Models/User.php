<?php

namespace App\Models;

use App\Services\Auth\EmailCodeService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
        'two_factor_email',
    ];

    public const LOCALES = ['pt_BR' => 'Português', 'en' => 'English', 'es' => 'Español'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_email' => 'boolean',
        ];
    }

    /** E-mails e notificações saem no idioma escolhido pela pessoa. */
    public function preferredLocale(): string
    {
        return $this->locale ?: config('app.locale');
    }

    /**
     * A confirmação do cadastro é por código de 6 dígitos (não por link).
     * O Laravel chama este método ao disparar o evento Registered.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(EmailCodeService::class)->send($this, EmailCodeService::VERIFY_EMAIL);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/u', trim($this->name)) ?: [];
        $first = mb_substr($parts[0] ?? '?', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function currentResume(): HasOne
    {
        return $this->hasOne(Resume::class)->where('is_current', true)->latestOfMany();
    }

    public function jobMatches(): HasMany
    {
        return $this->hasMany(JobMatch::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }
}
