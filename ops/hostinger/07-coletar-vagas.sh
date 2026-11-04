#!/usr/bin/env bash
# Roda a coleta de vagas agora (a mesma do agendador diário) e mostra o resumo por fonte.
# A saída não tem chaves: o comando tira URLs das mensagens de erro.
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
php artisan faro:fetch-jobs 2>&1 | tail -15
echo "--- vagas por área (ativas)"
php artisan tinker --execute='foreach (App\Models\JobPosting::active()->selectRaw("coalesce(area, \"(sem área)\") a, count(*) n")->groupBy("a")->orderByDesc("n")->get() as $r) echo str_pad($r->a, 16)." ".$r->n.PHP_EOL;' 2>&1 | head -30
true
