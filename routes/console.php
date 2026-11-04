<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Agendador. Na Hostinger, um Cron Job roda "php artisan schedule:run" a cada minuto
 * (ver docs/DEPLOY.md), e é ele que processa a fila: não há worker rodando o tempo todo
 * na hospedagem compartilhada.
 */
Schedule::command('queue:work --queue=default,jobs --stop-when-empty --max-time=50 --tries=2 --timeout=600')
    ->everyMinute()
    ->withoutOverlapping(15)
    ->name('fila');

// Batimento do cron: o faro:diagnostico mostra há quanto tempo o agendador rodou pela última vez.
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever('cron:heartbeat', now()->toIso8601String()))
    ->everyMinute()
    ->name('heartbeat');

Schedule::command('queue:prune-failed --hours=168')->daily();

// Coleta de vagas: 1x por dia, de madrugada (a IA estrutura as novas ao longo da manhã)
Schedule::command('faro:fetch-jobs')->dailyAt('05:40')->withoutOverlapping(120);
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
    $this->line('Extensão GD (fotos) ........ '.$ok(extension_loaded('gd')));
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
        // Login de verdade no SMTP (sem enviar e-mail). Só OK/FALHOU: a mensagem do servidor não é impressa.
        try {
            $transport = app('mailer')->getSymfonyTransport();
            if (method_exists($transport, 'start')) {
                $transport->start();
                $transport->stop();
            }
            $this->line('SMTP aceita a senha ........ '.$ok(true));
        } catch (\Throwable $e) {
            $this->line('SMTP aceita a senha ........ '.$ok(false).' (confira MAIL_USERNAME/MAIL_PASSWORD no ENV_FILE)');
        }
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

/*
 * Coleta vagas de todas as fontes ativas (ou só das indicadas em --source).
 * Fontes sem chave configurada (Adzuna, Jooble) são puladas com aviso.
 */
Artisan::command('faro:fetch-jobs {--source=* : slug da fonte (ex.: adzuna-br)}', function () {
    $collector = app(\App\Services\Jobs\JobCollector::class);
    $connectors = app(\App\Services\Jobs\JobConnectors::class);

    $sources = \App\Models\JobSource::where('is_active', true)
        ->where('type', '!=', \App\Enums\JobSourceType::Manual->value)
        ->when($this->option('source'), fn ($q, $slugs) => $q->whereIn('slug', $slugs))
        ->orderBy('id')->get();

    foreach ($sources as $source) {
        $connector = $connectors->for($source->type);
        if (! $connector?->isConfigured()) {
            $this->line(str_pad($source->slug, 18, '.').' <comment>pulada (sem chave/conector)</comment>');

            continue;
        }

        try {
            $s = $collector->collect($source);
            $this->line(str_pad($source->slug, 18, '.')." lidas {$s['fetched']} · novas {$s['created']} · renovadas {$s['updated']}"
                ." · fora da região {$s['outside_region']} · repetidas {$s['duplicated']} · fechadas {$s['closed']}");
        } catch (\Throwable $e) {
            report($e);
            // Mensagem curta, sem URL (a URL do Jooble contém a chave)
            $this->line(str_pad($source->slug, 18, '.').' <error>falhou</error> '.class_basename($e).' '.preg_replace('#https?://\S+#', '[url]', mb_substr($e->getMessage(), 0, 160)));
        }
    }

    $this->line('Vagas ativas: '.\App\Models\JobPosting::active()->count()
        .' · aguardando IA: '.\App\Models\JobPosting::active()->whereNull('extracted_at')->count());
})->purpose('Coleta vagas das fontes (Grande SP + remotas)');
