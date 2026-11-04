<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Agendador. Na Hostinger, um Cron Job roda "php artisan schedule:run" a cada minuto
 * (ver docs/DEPLOY.md), e é ele que processa a fila: não há worker rodando o tempo todo
 * na hospedagem compartilhada.
 */
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=2 --timeout=280')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->name('fila');

// Batimento do cron: o faro:diagnostico mostra há quanto tempo o agendador rodou pela última vez.
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever('cron:heartbeat', now()->toIso8601String()))
    ->everyMinute()
    ->name('heartbeat');

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('auth:clear-resets')->daily();

/*
 * Diagnóstico pós-deploy (roda no GitHub Actions). Não imprime segredos: só sim/não e códigos HTTP.
 */
Artisan::command('faro:diagnostico', function () {
    $ok = fn (bool $b) => $b ? '<info>OK</info>' : '<error>FALHOU</error>';

    try {
        \Illuminate\Support\Facades\DB::select('select 1');
        $db = true;
    } catch (\Throwable) {
        $db = false;
    }
    $this->line('Banco de dados ............. '.$ok($db));

    $this->line('APP_KEY definida ........... '.$ok(filled(config('app.key'))));
    $this->line('Token da IA definido ....... '.$ok(filled(config('faro.ai.api_key'))));

    try {
        $res = \Illuminate\Support\Facades\Http::baseUrl(config('faro.ai.base_url'))
            ->withToken((string) config('faro.ai.api_key'))
            ->timeout(20)->get('/api/tags');
        $models = collect($res->json('models', []))->pluck('name');
        $this->line('IA responde com o token .... '.$ok($res->successful()).' (HTTP '.$res->status().')');
        $this->line('Modelo de texto instalado .. '.$ok($models->contains(config('faro.ai.model'))));
        $this->line('Modelo de embeddings ....... '.$ok($models->contains(fn ($m) => str_starts_with($m, config('faro.ai.embedding_model')))));
    } catch (\Throwable $e) {
        $this->line('IA responde com o token .... '.$ok(false).' (sem conexão)');
    }

    $mailer = config('mail.default');
    $this->line("E-mail ..................... mailer={$mailer}");
    if ($mailer === 'smtp') {
        $host = config('mail.mailers.smtp.host');
        $port = (int) config('mail.mailers.smtp.port');
        $socket = @fsockopen(($port === 465 ? 'ssl://' : '').$host, $port, $errno, $errstr, 10);
        $this->line('SMTP alcançável ............ '.$ok((bool) $socket));
        $this->line('Senha do e-mail definida ... '.$ok(filled(config('mail.mailers.smtp.password'))));
        if ($socket) {
            fclose($socket);
        }
    }

    $beat = \Illuminate\Support\Facades\Cache::get('cron:heartbeat');
    $age = $beat ? (int) \Illuminate\Support\Carbon::parse($beat)->diffInSeconds(now()) : null;
    $this->line('Cron (último batimento) .... '.($age === null ? '<error>nunca rodou</error>' : $ok($age < 180)." há {$age}s"));

    $this->line('Fila (jobs pendentes) ...... '.\Illuminate\Support\Facades\DB::table('jobs')->count());
    $this->line('Fila (jobs com falha) ...... '.\Illuminate\Support\Facades\DB::table('failed_jobs')->count());
})->purpose('Confere banco, IA e e-mail sem mostrar segredos');
