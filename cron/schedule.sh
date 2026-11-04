#!/bin/bash
# Chamado pelo Cron Job do hPanel a cada minuto:
#   /bin/bash /home/u727297856/domains/faro.marcusmoreira.com.br/faro-app/cron/schedule.sh
# Roda o agendador do Laravel (que processa a fila) e registra a saída em storage/logs/cron.log.
APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$APP_DIR" || exit 1
{
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] schedule:run"
  /usr/bin/php artisan schedule:run
} >> "$APP_DIR/storage/logs/cron.log" 2>&1
