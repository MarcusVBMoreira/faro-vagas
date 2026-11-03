# ops/vps

Scripts de manutenção do VPS (`srv1936230.hstgr.cloud`). Eles **não** vão para o site: rodam no VPS pelo workflow **"Operações no VPS"** (`.github/workflows/vps-ops.yml`), que entra por SSH com a chave guardada nos secrets do GitHub.

## Como rodar um script
- **Automático:** coloque o nome do script em `ops/vps/run.txt` e dê push. O workflow roda esse script no VPS e mostra a saída no log.
- **Manual:** GitHub → Actions → Operações no VPS → Run workflow → informe o nome do script.

## Scripts
| Script | O que faz |
|---|---|
| `01-ollama-bench.sh` | Instala o Ollama (só escuta em `127.0.0.1`), baixa um modelo de embeddings e dois modelos de texto pequenos, e mede velocidade e qualidade nas tarefas do Faro. |

## Secrets necessários
| Nome | Valor |
|---|---|
| `VPS_HOST` | `179.199.134.108` |
| `VPS_SSH_KEY` | chave privada cuja pública está em hPanel → VPS → Configurações → Chaves SSH |
