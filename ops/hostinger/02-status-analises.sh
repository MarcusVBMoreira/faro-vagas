#!/usr/bin/env bash
# Mostra o andamento das análises de currículo (sem dados pessoais).
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1

echo "===== ÚLTIMAS ANÁLISES ====="
php artisan tinker --execute='
foreach (\App\Models\Resume::latest()->take(5)->get() as $r) {
    $dur = $r->parsed_at ? $r->created_at->diffInSeconds($r->parsed_at) : null;
    echo "resume #{$r->id} | status={$r->status} | enviado={$r->created_at} | analisado={$r->parsed_at} | duração=".($dur !== null ? round($dur)."s" : "-")." | erro=".($r->error_message ?? "-").PHP_EOL;
}
echo PHP_EOL."===== CHAMADAS À IA =====".PHP_EOL;
foreach (\App\Models\AiUsageLog::latest("id")->take(5)->get() as $l) {
    echo "{$l->created_at} | {$l->operation} | {$l->model} | entrada={$l->input_tokens} tokens | saída={$l->output_tokens} tokens".PHP_EOL;
}
' 2>&1 | tail -15

echo "===== AVISOS E ERROS DO LOG ====="
grep -h -E "\.(WARNING|ERROR|CRITICAL)" storage/logs/*.log 2>/dev/null | tail -8 | cut -c1-220 || echo "(nada)"
