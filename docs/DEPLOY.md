# Deploy na Hostinger

Como funciona: a cada push na `main`, o GitHub Actions roda os testes. Se passarem, ele monta a versão de produção (dependências PHP + front-end compilado), envia por SSH para a Hostinger, roda as migrations e aponta o `public_html` para a pasta `public` do Laravel.

```
~/domains/faro.marcusmoreira.com.br/
├── faro-app/            ← o projeto Laravel (enviado pelo rsync)
│   ├── .env             ← gravado a partir do secret ENV_FILE
│   ├── public/
│   └── storage/
└── public_html  →  faro-app/public   (link simbólico criado pelo deploy)
```

> **Por que não usar a integração "GIT" do hPanel?** Ela só copia os arquivos do repositório para o `public_html`. O Laravel precisa de `composer install`, do front-end compilado, das migrations e de que o site sirva só a pasta `public/` (senão código e configurações ficam expostos). Por isso o deploy é feito pelo GitHub Actions.

## Configuração (uma vez só)

### 1. Desligar o deploy automático do hPanel
hPanel → Sites → faro.marcusmoreira.com.br → Avançado → **GIT** → menu ⋮ → remover/desconectar o repositório. Senão ele sobrescreve o `public_html` a cada push.

### 2. PHP 8.3
hPanel → Avançado → **Configuração de PHP** → versão **8.3** (ou mais nova).

### 3. Banco de dados
Já criado: banco `u727297856_faro`, usuário `u727297856_root_faro`. O host é `localhost`.

### 4. Acesso SSH
hPanel → Avançado → **Acesso SSH** → ativar. Anote o **IP/host**, a **porta** (normalmente `65002`) e o **usuário** (`u727297856`).

### 5. Chave SSH para o GitHub
No seu computador (PowerShell ou Git Bash):

```bash
ssh-keygen -t ed25519 -C "github-deploy-faro" -f faro_deploy -N ""
```

Isso cria dois arquivos:
- `faro_deploy.pub` (chave **pública**): hPanel → Acesso SSH → **Chaves SSH** → Adicionar → cole o conteúdo.
- `faro_deploy` (chave **privada**): vai no secret `SSH_KEY` abaixo. Não envie para ninguém e não coloque no repositório.

### 6. Secrets e variáveis do GitHub
Repositório → **Settings → Secrets and variables → Actions**.

**Secrets** (aba Secrets → New repository secret):

| Nome | Valor |
|---|---|
| `SSH_HOST` | IP/host do passo 4 |
| `SSH_PORT` | porta do passo 4 (ex.: `65002`) |
| `SSH_USER` | `u727297856` |
| `SSH_KEY` | conteúdo inteiro do arquivo `faro_deploy` (de `-----BEGIN` até `END-----`) |
| `DEPLOY_PATH` | `domains/faro.marcusmoreira.com.br` |
| `ENV_FILE` | conteúdo inteiro do `.env.production` (modelo abaixo) |

**Variables** (aba Variables → New repository variable):

| Nome | Valor |
|---|---|
| `DEPLOY_ENABLED` | `true` (só crie depois dos secrets: é o que liga o deploy) |
| `APP_URL` | `https://faro.marcusmoreira.com.br` |
| `PHP_BIN` | opcional. Se `php -v` no SSH não mostrar 8.3, use `/opt/alt/php83/usr/bin/php` |

### 7. Modelo do `ENV_FILE`

```dotenv
APP_NAME=Faro
APP_ENV=production
APP_KEY=base64:GERE_UMA_CHAVE
APP_DEBUG=false
APP_URL=https://faro.marcusmoreira.com.br
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u727297856_faro
DB_USERNAME=u727297856_root_faro
DB_PASSWORD='SENHA_DO_BANCO'

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

# E-mail (Hostinger). Troque MAIL_MAILER=log por smtp quando colocar a senha do no-reply
MAIL_MAILER=log
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@marcusmoreira.com.br
MAIL_PASSWORD='SENHA_DO_EMAIL'
MAIL_FROM_ADDRESS=no-reply@marcusmoreira.com.br
MAIL_FROM_NAME="Faro"

FARO_REPO_URL=https://github.com/MarcusVBMoreira/faro-vagas
AI_BASE_URL=https://ia.marcusmoreira.com.br
AI_API_KEY=O_MESMO_VALOR_DO_SECRET_FARO_IA_TOKEN
AI_MODEL=qwen2.5:3b
AI_EMBEDDING_MODEL=bge-m3
AI_DAILY_LIMIT_PER_USER=50
```

- Senhas com `$`, `#` ou espaço ficam entre **aspas simples**.
- Para gerar o `APP_KEY`, rode no SSH da Hostinger: `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`
- Para mudar algo do `.env` depois: edite o secret `ENV_FILE` e rode o workflow de novo (Actions → CI e Deploy → Run workflow).

### 8. Primeiro deploy
Actions → **CI e Deploy** → **Run workflow** (ou faça um push na `main`). No fim, o passo "Conferir se o site respondeu" acessa `https://faro.marcusmoreira.com.br/up`.

## Cron (obrigatório desde a semana 1)
A análise do currículo roda numa fila, e na hospedagem compartilhada quem processa a fila é o agendador do Laravel.
hPanel → Avançado → **Cron Jobs** → comando personalizado, a cada minuto:

```
cd /home/u727297856/domains/faro.marcusmoreira.com.br/faro-app && php artisan schedule:run >> /dev/null 2>&1
```

## Problemas comuns
- **`Permission denied (publickey)`**: a chave pública não foi adicionada no hPanel, ou o `SSH_KEY` não tem o conteúdo inteiro da chave privada.
- **`rsync: command not found` no servidor**: avise, dá para trocar o envio por `tar` via SSH.
- **Erro 500 no site**: veja `faro-app/storage/logs/` pelo Gerenciador de Arquivos. Quase sempre é `APP_KEY` vazio ou senha do banco errada no `ENV_FILE`.
- **Página padrão da Hostinger aparecendo**: o link `public_html → faro-app/public` não foi criado. Confira o log do passo "Finalizar no servidor".
