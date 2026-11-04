#!/usr/bin/env bash
# Mostra só o resumo dos últimos erros do Laravel (classe, mensagem, arquivo e linha).
# O repositório é público: e-mails e qualquer coisa parecida com senha/token são mascarados,
# e o stack trace e o contexto não são impressos.
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
LOG="$(ls -t storage/logs/laravel*.log 2>/dev/null | head -1)"
[ -z "$LOG" ] && { echo "Nenhum log do Laravel."; exit 0; }
echo "Arquivo: $(basename "$LOG")"
grep -E '^\[[0-9-]+ [0-9:]+\] [a-z]+\.(ERROR|CRITICAL|EMERGENCY)' "$LOG" | tail -5 \
  | sed -E 's/\{"(userId|exception)".*$//' \
  | sed -E 's/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/<email>/g' \
  | sed -E 's/((password|senha|token|key|secret)[^ ]*[=:] *)[^ ]+/\1<oculto>/Ig' \
  | cut -c1-500
echo "--- origem (arquivo:linha) dos mesmos erros"
grep -oE '\(code: [0-9]+\): .{0,300} at /[^ ]+:[0-9]+\)' "$LOG" | tail -5 \
  | sed -E 's/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/<email>/g' \
  | sed -E 's#/home/[^/]+/domains/[^/]+/faro-app/##g' | cut -c1-500
true
