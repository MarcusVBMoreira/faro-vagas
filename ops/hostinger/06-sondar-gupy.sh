#!/usr/bin/env bash
# Sonda a busca pública do portal da Gupy (sem login) para conferir o formato da resposta.
# Só imprime nomes de campos e valores de vagas públicas (nada de dados pessoais).
set -uo pipefail
UA="FaroBot/1.0 (+https://faro.marcusmoreira.com.br)"
for q in "jobName=auxiliar&limit=3&state=S%C3%A3o%20Paulo" "jobName=assistente&limit=2&workplaceType=remote" "jobName=estagio&limit=2&offset=10"; do
  echo "===== $q"
  curl -s -m 20 -A "$UA" -w '\nHTTP %{http_code}\n' "https://portal.api.gupy.io/api/v1/jobs?$q" > /tmp/gupy.json
  tail -1 /tmp/gupy.json
  php -r '
    $raw = file_get_contents("/tmp/gupy.json");
    $j = json_decode(substr($raw, 0, strrpos($raw, "\nHTTP")), true);
    if (!is_array($j)) { echo "não é JSON: ".substr($raw, 0, 300)."\n"; exit; }
    echo "chaves raiz: ".implode(", ", array_keys($j))."\n";
    echo "pagination: ".json_encode($j["pagination"] ?? null)."\n";
    foreach (array_slice($j["data"] ?? [], 0, 2) as $job) {
      foreach ($job as $k => $v) {
        $s = is_scalar($v) || $v === null ? var_export($v, true) : json_encode($v, JSON_UNESCAPED_UNICODE);
        echo "  $k = ".mb_substr($s, 0, 160)."\n";
      }
      echo "  ---\n";
    }
  '
  sleep 1
done
true
