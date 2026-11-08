#!/usr/bin/env bash
# Resumo dos jobs da fila que falharam (classe, quando e a primeira linha do erro, sem stack trace).
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
php artisan tinker --execute='
foreach (DB::table("failed_jobs")->orderByDesc("id")->limit(5)->get() as $f) {
  $p = json_decode($f->payload, true);
  echo $f->failed_at." · ".($p["displayName"] ?? "?")." · ".mb_substr(strtok($f->exception, "\n"), 0, 200).PHP_EOL;
}' 2>&1 | sed -E 's/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/<email>/g' | tail -5
true
