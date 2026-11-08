#!/usr/bin/env bash
# Mostra o andamento da IA nas vagas, gera embeddings por até ~4 minutos (medindo a velocidade)
# e calcula os matches na hora. Só números e títulos de vagas públicas no log.
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
php artisan tinker --execute='
use App\Models\JobPosting;
$a = JobPosting::active();
echo "ativas: ".(clone $a)->count()." · lidas pela IA: ".(clone $a)->whereNotNull("extracted_at")->count()." · com embedding: ".(clone $a)->whereNotNull("embedded_at")->count().PHP_EOL;
$t = microtime(true); $before = (clone $a)->whereNotNull("embedded_at")->count();
while (microtime(true) - $t < 230) {
    $n = (clone $a)->whereNull("embedded_at")->where(fn ($q) => $q->whereNotNull("extracted_at")->orWhere("created_at", "<", now()->subHours(6)))->count();
    if ($n === 0) break;
    app()->call([new App\Jobs\EmbedPendingJobPostings, "handle"]);
    $done = (clone $a)->whereNotNull("embedded_at")->count();
    if ($done === $before) { echo "IA não gerou embeddings (indisponível?)".PHP_EOL; break; }
    $before = $done;
}
$secs = round(microtime(true) - $t);
echo "com embedding agora: ".(clone $a)->whereNotNull("embedded_at")->count()." (em {$secs}s)".PHP_EOL;
' 2>&1 | tail -5
php artisan faro:compute-matches --sync 2>&1 | sed -E 's/usuário ([0-9]+)/usuário #\1/' | tail -10
true
