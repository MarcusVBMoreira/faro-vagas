#!/usr/bin/env bash
# Teste de desempenho de IA local no VPS (Ollama).
# Roda pelo GitHub Actions (workflow "Operações no VPS"). Não muda nada do site.
set -euo pipefail

echo "===== MÁQUINA ====="
echo "CPUs: $(nproc)"
free -h
df -h /
echo "--- containers ---"
docker ps --format '{{.Names}}  {{.Image}}' 2>/dev/null || echo "(docker não instalado)"
echo "--- portas em uso ---"
ss -tlnp | grep -E ':(80|443|5678|11434)\b' || echo "(nenhuma das portas 80/443/5678/11434)"

echo "===== OLLAMA ====="
if ! command -v ollama >/dev/null 2>&1; then
  curl -fsSL https://ollama.com/install.sh | sh
fi
ollama --version
# Por padrão o Ollama só escuta em 127.0.0.1 (não fica exposto na internet).
for m in bge-m3 qwen2.5:1.5b qwen2.5:3b; do
  echo "baixando $m..."
  ollama pull "$m" >/dev/null 2>&1
done
ollama list

mkdir -p /root/faro-bench
cat > /root/faro-bench/bench.py <<'EOF'
import json, time, urllib.request, sys

URL = "http://127.0.0.1:11434"

VAGA = """Estágio em Desenvolvimento Web - Empresa Exemplo Tecnologia
Local: Santo André - SP | Modelo híbrido (3x por semana no escritório)
Bolsa: R$ 1.800 + VT + VR
Requisitos: cursando Engenharia de Software, Ciência da Computação ou áreas afins
a partir do 3º semestre; conhecimento em PHP e MySQL; HTML, CSS e JavaScript; Git.
Diferenciais: Laravel, Docker, inglês intermediário.
Atividades: apoiar o time no desenvolvimento de sistemas internos, corrigir bugs e
criar telas e APIs."""

CURRICULO = """Ana Souza - Mauá/SP
Formação: Engenharia de Software, 3º semestre (previsão de conclusão: 2029)
Conhecimentos: PHP, JavaScript, HTML, CSS, MySQL, Git, noções de Python.
Projetos: sistema de agendamento para barbearia (PHP + MySQL); site pessoal (HTML/CSS/JS).
Idiomas: inglês intermediário.
Objetivo: estágio em desenvolvimento web, preferência por híbrido ou remoto."""

P_VAGA = """Extraia os dados da vaga abaixo e responda SOMENTE com um JSON com as chaves:
titulo, empresa, nivel (estagio|junior|pleno|senior), modalidade (remoto|hibrido|presencial),
cidade, estado (sigla), tecnologias_obrigatorias (lista), tecnologias_diferenciais (lista),
semestre_minimo (número ou null), resumo (1 frase em português).

VAGA:
""" + VAGA

P_CV = """Leia o currículo abaixo e responda SOMENTE com um JSON com as chaves:
nome, cidade, estado, nivel_desejado (estagio|junior), area, semestre_atual (número),
habilidades (lista), ingles (basico|intermediario|avancado), modalidades_desejadas (lista).

CURRÍCULO:
""" + CURRICULO

P_MATCH = """Você avalia a compatibilidade entre um candidato e uma vaga de TI.
Responda SOMENTE com um JSON com as chaves: nota (0 a 100), pontos_fortes (lista),
lacunas (lista), explicacao (até 2 frases em português, falando diretamente com o candidato).

CANDIDATO:
""" + CURRICULO + "\n\nVAGA:\n" + VAGA


def post(path, payload, timeout=1800):
    req = urllib.request.Request(URL + path, data=json.dumps(payload).encode(),
                                 headers={"Content-Type": "application/json"})
    t = time.time()
    r = json.load(urllib.request.urlopen(req, timeout=timeout))
    return r, time.time() - t


def ns(v):
    return (v or 0) / 1e9


print("=" * 60)
print("EMBEDDINGS (bge-m3)")
for i in (1, 2):
    r, dt = post("/api/embed", {"model": "bge-m3", "input": VAGA})
    print(f"  chamada {i}: {dt:.1f}s | dimensões: {len(r['embeddings'][0])}"
          + ("  (inclui carregar o modelo)" if i == 1 else ""), flush=True)
r, dt = post("/api/embed", {"model": "bge-m3", "input": [VAGA] * 10})
print(f"  10 vagas de uma vez: {dt:.1f}s ({dt/10:.1f}s por vaga)", flush=True)

for model in sys.argv[1:]:
    for nome, prompt in (("VAGA", P_VAGA), ("CURRICULO", P_CV), ("MATCH", P_MATCH)):
        r, dt = post("/api/generate", {
            "model": model, "prompt": prompt, "format": "json", "stream": False,
            "options": {"temperature": 0, "num_ctx": 4096},
        })
        try:
            json.loads(r["response"])
            valido = "JSON valido"
        except Exception:
            valido = "JSON INVALIDO"
        tok_s = r.get("eval_count", 0) / max(ns(r.get("eval_duration")), 1e-9)
        print("=" * 60)
        print(f"{model} | {nome} | total {dt:.1f}s (carregar {ns(r.get('load_duration')):.1f}s, "
              f"ler prompt {ns(r.get('prompt_eval_duration')):.1f}s, gerar {ns(r.get('eval_duration')):.1f}s "
              f"a {tok_s:.1f} tokens/s) | {valido}")
        print(r["response"][:900], flush=True)

print("=" * 60)
EOF

echo "===== TESTE ====="
python3 /root/faro-bench/bench.py qwen2.5:1.5b qwen2.5:3b

echo "===== MEMÓRIA DEPOIS DO TESTE ====="
free -h
ollama ps
