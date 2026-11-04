#!/usr/bin/env bash
# Sonda endpoints públicos da busca de vagas da Gupy (sem login) para achar o que responde.
set -uo pipefail
UA="Mozilla/5.0 (compatible; FaroBot/1.0; +https://faro.marcusmoreira.com.br)"
for url in \
  "https://portal.api.gupy.io/api/v1/jobs?jobName=auxiliar&limit=2" \
  "https://portal.api.gupy.io/api/job?name=auxiliar&limit=2" \
  "https://employability-portal.gupy.io/api/v1/jobs?jobName=auxiliar&limit=2" \
  "https://employability-portal.gupy.io/api/v1/jobs?jobName=auxiliar&limit=2&state=S%C3%A3o%20Paulo" \
  "https://portal.gupy.io/api/v1/jobs?jobName=auxiliar&limit=2"; do
  echo "===== $url"
  code=$(curl -s -m 20 -A "$UA" -H 'Accept: application/json' -o /tmp/g.json -w '%{http_code}' "$url")
  echo "HTTP $code · $(head -c 200 /tmp/g.json | tr '\n' ' ')"
  if [ "$code" = "200" ]; then
    php -r '
      $j = json_decode(file_get_contents("/tmp/g.json"), true);
      if (!is_array($j)) exit;
      echo "chaves raiz: ".implode(", ", array_keys($j))."\n";
      echo "pagination: ".json_encode($j["pagination"] ?? null)."\n";
      foreach (array_slice($j["data"] ?? [], 0, 1) as $job) foreach ($job as $k => $v) {
        $s = is_scalar($v) || $v === null ? var_export($v, true) : json_encode($v, JSON_UNESCAPED_UNICODE);
        echo "  $k = ".mb_substr($s, 0, 160)."\n";
      }'
  fi
  sleep 1
done
true
