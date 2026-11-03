#!/usr/bin/env bash
# Publica o Ollama em https://ia.marcusmoreira.com.br, protegido por token.
# - Arquivo de site separado (faro-ia): não mexe no pdf-api nem no site padrão.
# - O token chega do secret FARO_IA_TOKEN em /root/faro-ops/.ia_token e NUNCA é impresso
#   (o repositório é público, então os logs também são).
set -euo pipefail

DOMAIN="ia.marcusmoreira.com.br"
VPS_IP="179.199.134.108"
TOKEN_FILE="/root/faro-ops/.ia_token"
SITE="/etc/nginx/sites-available/faro-ia"

[ -s "$TOKEN_FILE" ] || { echo "ERRO: token não recebido (crie o secret FARO_IA_TOKEN)."; exit 1; }
TOKEN="$(tr -d '[:space:]' < "$TOKEN_FILE")"
[ "${#TOKEN}" -ge 32 ] || { echo "ERRO: token curto demais (mínimo 32 caracteres)."; exit 1; }

echo "===== DNS ====="
for i in $(seq 1 30); do
  IP="$(python3 -c "import socket;print(socket.gethostbyname('$DOMAIN'))" 2>/dev/null || true)"
  [ "$IP" = "$VPS_IP" ] && { echo "$DOMAIN -> $IP OK"; break; }
  echo "aguardando DNS ($IP)..."; sleep 10
done
[ "$IP" = "$VPS_IP" ] || { echo "ERRO: DNS ainda não aponta para o VPS. Rode de novo mais tarde."; exit 1; }

echo "===== NGINX: site faro-ia ====="
umask 077
cat > "$SITE" <<EOF
# Faro: acesso ao Ollama pela internet, só com token. Gerado por ops/vps/04-ia-https-token.sh
map \$http_authorization \$faro_ia_ok {
    default 0;
    "Bearer $TOKEN" 1;
}
limit_req_zone \$binary_remote_addr zone=faro_ia:10m rate=60r/m;

server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;

    location = /health {
        default_type text/plain;
        return 200 "ok\n";
    }

    location / {
        if (\$faro_ia_ok = 0) { return 401; }
        limit_req zone=faro_ia burst=20 nodelay;

        proxy_pass http://127.0.0.1:11434;
        proxy_set_header Host localhost;
        proxy_set_header Authorization "";
        proxy_http_version 1.1;
        proxy_buffering off;
        proxy_read_timeout 300s;
        proxy_send_timeout 300s;
        client_max_body_size 2m;
    }
}
EOF
chmod 600 "$SITE"
ln -sfn "$SITE" /etc/nginx/sites-enabled/faro-ia

if ! nginx -t 2>/dev/null; then
  echo "ERRO na configuração: desfazendo para não afetar o pdf-api."
  rm -f /etc/nginx/sites-enabled/faro-ia
  nginx -t
  exit 1
fi
systemctl reload nginx
echo "nginx recarregado"

echo "===== HTTPS (Certbot) ====="
if ! certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --redirect --keep-until-expiring \
     --register-unsafely-without-email > /root/faro-ops/certbot.log 2>&1; then
  echo "ERRO no Certbot:"; tail -20 /root/faro-ops/certbot.log; exit 1
fi
tail -5 /root/faro-ops/certbot.log
certbot certificates 2>/dev/null | grep -E "Certificate Name|Expiry" | grep -A1 "$DOMAIN" || true

echo "===== TESTES (só códigos HTTP, nada sensível) ====="
sleep 2
code() { curl -s -o /dev/null -w '%{http_code}' --max-time 30 "$@"; }
echo "health (sem token, esperado 200):            $(code https://$DOMAIN/health)"
echo "api sem token (esperado 401):                $(code https://$DOMAIN/api/tags)"
echo "api com token errado (esperado 401):         $(code -H 'Authorization: Bearer errado' https://$DOMAIN/api/tags)"
echo "api com token certo (esperado 200):          $(code -H "Authorization: Bearer $TOKEN" https://$DOMAIN/api/tags)"
echo "http redireciona para https (esperado 301):  $(code http://$DOMAIN/health)"
echo "pdf-api continua no ar (esperado != 000):    $(code https://pdf-api.marcusmoreira.com.br/)"

echo "===== MODELOS DISPONÍVEIS PELA URL ====="
curl -s --max-time 30 -H "Authorization: Bearer $TOKEN" "https://$DOMAIN/api/tags" \
  | python3 -c "import sys,json;[print(' -',m['name']) for m in json.load(sys.stdin)['models']]"
