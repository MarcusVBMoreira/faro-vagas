# ops/vps

Scripts de manutenção do VPS (`srv1936230.hstgr.cloud`). Eles **não** vão para o site: rodam no VPS pelo workflow **"Operações no VPS"** (`.github/workflows/vps-ops.yml`), que entra por SSH com a chave guardada nos secrets do GitHub.

## Como rodar um script
- **Automático:** coloque o nome do script em `ops/vps/run.txt` e dê push. O workflow roda esse script no VPS e mostra a saída no log.
- **Manual:** GitHub → Actions → Operações no VPS → Run workflow → informe o nome do script.

## Scripts
| Script | O que faz |
|---|---|
| `01-ollama-bench.sh` | Instala o Ollama (só escuta em `127.0.0.1`), baixa um modelo de embeddings e dois modelos de texto pequenos, e mede velocidade e qualidade nas tarefas do Faro. |
| `02-swap-e-teste-3b.sh` | Cria 2 GB de swap, limita o Ollama a 1 modelo carregado por vez e repete o teste com o `qwen2.5:3b`. |
| `03-inspecionar-nginx.sh` | Somente leitura: lista os sites do nginx, certificados, serviços e portas do VPS. |
| `04-ia-https-token.sh` | Publica o Ollama em `https://ia.marcusmoreira.com.br` com HTTPS (Certbot), token obrigatório e limite de requisições, num site separado do nginx. |

## Secrets necessários
| Nome | Valor |
|---|---|
| `VPS_HOST` | `179.199.134.108` |
| `VPS_SSH_KEY` | chave privada cuja pública está em hPanel → VPS → Configurações → Chaves SSH |
| `FARO_IA_TOKEN` | token aleatório (64 caracteres hex) exigido em `Authorization: Bearer ...` |

> O repositório é público, então os logs do Actions também são: os scripts nunca imprimem tokens ou senhas.
