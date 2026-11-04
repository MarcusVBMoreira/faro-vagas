#!/usr/bin/env bash
# Confere se o caminho usado no Cron Job do hPanel existe e mostra o batimento do agendador.
set -uo pipefail
echo "HOME do usuário: $HOME"
CRON_DIR="/home/u727297856/domains/faro.marcusmoreira.com.br/faro-app"
if [ -f "$CRON_DIR/artisan" ]; then echo "Caminho do cron existe: OK"; else echo "Caminho do cron NÃO existe: $CRON_DIR"; fi
echo "Caminho real do app: $(cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" && pwd -P)"
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
echo "--- log do cron (se existir)"
tail -5 storage/logs/cron.log 2>/dev/null || echo "(sem storage/logs/cron.log)"
echo "--- diagnóstico"
php artisan faro:diagnostico 2>&1 | tail -4
# 2026-11-04T01:17:46
# 2026-11-04T01:25:49
