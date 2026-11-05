#!/usr/bin/env bash
# Testa variações de busca na API do Jooble. Mostra só status e contagens (a chave fica fora do log).
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
php artisan tinker --execute='
$key = config("faro.jobs.jooble.api_key");
echo "chave definida: ".(filled($key) ? "sim (".strlen($key)." caracteres)" : "não").PHP_EOL;
foreach ([["", "São Paulo"], ["auxiliar", "São Paulo"], ["vendedor", "São Paulo, SP"], ["home office", ""], ["auxiliar", ""]] as [$k, $l]) {
    try {
        $r = Illuminate\Support\Facades\Http::timeout(30)->acceptJson()->post("https://br.jooble.org/api/".$key, ["keywords" => $k, "location" => $l, "page" => "1"]);
        $j = $r->json() ?? [];
        $first = $j["jobs"][0] ?? null;
        echo str_pad("[$k | $l]", 34)." HTTP ".$r->status()." totalCount=".($j["totalCount"] ?? "-")." jobs=".count($j["jobs"] ?? [])
            .($first ? " ex.: ".mb_substr($first["title"] ?? "", 0, 40)." @ ".mb_substr($first["location"] ?? "", 0, 30) : "")
            .(! $r->successful() ? " corpo: ".mb_substr(preg_replace("/\s+/", " ", $r->body()), 0, 120) : "").PHP_EOL;
    } catch (Throwable $e) { echo "[$k | $l] erro ".class_basename($e).PHP_EOL; }
    sleep(1);
}' 2>&1 | sed -E 's#jooble.org/api/<chave>#jooble.org/api/<chave>#g'
true
