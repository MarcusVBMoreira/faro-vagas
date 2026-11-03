#!/usr/bin/env bash
# 1) Confirma se o teste anterior caiu por falta de memória
# 2) Cria 2 GB de swap e limita o Ollama a 1 modelo carregado por vez
# 3) Repete o teste só com o qwen2.5:3b
set -euo pipefail

echo "===== O QUE DERRUBOU O TESTE ANTERIOR? ====="
dmesg 2>/dev/null | grep -i -E "out of memory|killed process" | tail -5 || true
journalctl -u ollama --since "2 hours ago" --no-pager 2>/dev/null | grep -i -E "error|killed|signal|memory" | tail -10 || true

echo "===== SWAP ====="
if ! swapon --show | grep -q '/swapfile'; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile >/dev/null
  swapon /swapfile
  grep -q '^/swapfile ' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi
# Usa o swap só como reserva de emergência
sysctl -q vm.swappiness=10
echo 'vm.swappiness=10' > /etc/sysctl.d/99-faro-swap.conf
swapon --show

echo "===== OLLAMA: 1 MODELO POR VEZ ====="
mkdir -p /etc/systemd/system/ollama.service.d
cat > /etc/systemd/system/ollama.service.d/faro.conf <<'EOF'
[Service]
Environment="OLLAMA_MAX_LOADED_MODELS=1"
Environment="OLLAMA_NUM_PARALLEL=1"
EOF
systemctl daemon-reload
systemctl restart ollama
sleep 5
free -h

echo "===== TESTE qwen2.5:3b ====="
python3 /root/faro-bench/bench.py qwen2.5:3b

echo "===== MEMÓRIA DEPOIS ====="
free -h
ollama ps
