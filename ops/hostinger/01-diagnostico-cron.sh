#!/usr/bin/env bash
# Diagnóstico do cron/fila na Hostinger. Não imprime segredos.
set -uo pipefail
APP="$HOME/domains/faro.marcusmoreira.com.br/faro-app"
cd "$APP" || { echo "pasta do app não encontrada"; exit 1; }

echo "===== PHP ====="
echo "php no PATH do SSH: $(command -v php) -> $(php -r 'echo PHP_VERSION;')"
for p in /usr/bin/php /usr/local/bin/php /opt/alt/php82/usr/bin/php /opt/alt/php83/usr/bin/php /opt/alt/php84/usr/bin/php; do
  [ -x "$p" ] && echo "$p -> $($p -r 'echo PHP_VERSION;' 2>/dev/null)"
done
echo "PATH mínimo (como o cron costuma rodar):"
env -i HOME="$HOME" PATH=/usr/local/bin:/usr/bin:/bin sh -c 'echo "  php = $(command -v php) -> $(php -r "echo PHP_VERSION;" 2>&1 | head -1)"'

echo "===== CRONTAB ====="
crontab -l 2>&1 | sed -E 's/(token=)[^" &]+/\1***/g'

echo "===== AGENDADOR ====="
php artisan schedule:list 2>&1 | tail -8

echo "===== TESTE: schedule:run com PATH mínimo ====="
env -i HOME="$HOME" PATH=/usr/local/bin:/usr/bin:/bin sh -c "cd '$APP' && php artisan schedule:run" 2>&1 | tail -5

echo "===== ÚLTIMOS ERROS (resumo) ====="
grep -h -E "\.(ERROR|CRITICAL)" storage/logs/*.log 2>/dev/null | tail -5 | cut -c1-200 || echo "(sem erros)"

echo "===== PROCESSANDO A FILA AGORA ====="
php artisan queue:work --stop-when-empty --max-time=240 --tries=2 --timeout=280 2>&1 | tail -10

echo "===== DEPOIS ====="
php artisan faro:diagnostico 2>&1 | tail -3
