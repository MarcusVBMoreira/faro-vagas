#!/usr/bin/env bash
# SOMENTE LEITURA: lista o que o VPS já serve hoje. Não altera nada.
set -uo pipefail

echo "===== NGINX ====="
nginx -v 2>&1
echo "--- sites habilitados ---"
ls -la /etc/nginx/sites-enabled/ 2>/dev/null
ls -la /etc/nginx/conf.d/ 2>/dev/null

echo "--- domínios, pastas e proxies configurados ---"
nginx -T 2>/dev/null | grep -E "^# configuration file|server_name|listen|root |proxy_pass|return 30" | grep -v "^\s*#\s*[a-z]" | sed 's/^\s*//'

echo "===== CERTIFICADOS HTTPS ====="
if command -v certbot >/dev/null 2>&1; then
  certbot certificates 2>/dev/null | grep -E "Certificate Name|Domains|Expiry"
else
  echo "(certbot não instalado)"
fi
ls /etc/letsencrypt/live/ 2>/dev/null

echo "===== PASTAS DE SITES ====="
for d in /var/www /var/www/* /srv /home/*; do
  [ -d "$d" ] && echo "$d  ($(du -sh "$d" 2>/dev/null | cut -f1))"
done

echo "===== SERVIÇOS ATIVOS ====="
systemctl list-units --type=service --state=running --no-pager --no-legend | awk '{print $1}' \
  | grep -v -E "^(systemd|dbus|getty|serial|cron|rsyslog|polkit|ssh|unattended|multipathd|networkd|udisks|ModemManager|snapd|irqbalance|qemu|user@)" || true

echo "===== PORTAS ABERTAS ====="
ss -tlnp | awk 'NR>1 {print $4, $6}' | sed 's/users://'

echo "===== PROCESSOS NODE/PHP/PYTHON ====="
ps -eo pid,user,%mem,cmd --sort=-%mem | grep -E "node|php|python|pm2|gunicorn|uvicorn" | grep -v grep | head -15 || true

echo "===== CRON DO ROOT ====="
crontab -l 2>/dev/null || echo "(sem cron)"

echo "===== FIREWALL ====="
ufw status 2>/dev/null || echo "(ufw não disponível)"
