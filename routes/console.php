<?php

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

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('auth:clear-resets')->daily();
