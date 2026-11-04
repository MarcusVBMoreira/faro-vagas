# Deploying to Hostinger

How it works: on every push to `main`, GitHub Actions runs the tests. If they pass, it builds the production version (PHP dependencies + compiled front end), sends it over SSH to Hostinger, runs the migrations and points `public_html` at Laravel's `public` folder.

```
~/domains/faro.marcusmoreira.com.br/
├── faro-app/            ← the Laravel project (sent by rsync)
│   ├── .env             ← written from the ENV_FILE secret
│   ├── public/
│   │   └── storage  →  ../storage/app/public   (profile photos)
│   └── storage/         ← never touched by rsync (logs, résumés, photos)
└── public_html  →  faro-app/public   (symlink created by the deploy)
```

> **Why not use hPanel's "GIT" integration?** It only copies the repository files into `public_html`. Laravel needs `composer install`, the compiled front end, the migrations, and the site must serve only the `public/` folder (otherwise code and configuration are exposed). That's why the deploy runs on GitHub Actions.

## One-time setup

### 1. Turn off hPanel's automatic deploy
hPanel → Websites → faro.marcusmoreira.com.br → Advanced → **GIT** → ⋮ menu → remove/disconnect the repository. Otherwise it overwrites `public_html` on every push.

### 2. PHP 8.3
hPanel → Advanced → **PHP Configuration** → version **8.3** (or newer). The `gd` extension must be enabled (it is by default); it processes profile photos. `php artisan faro:diagnostico` reports it.

### 3. Database
Already created: database `u727297856_faro`, user `u727297856_root_faro`. The host is `localhost`.

### 4. SSH access
hPanel → Advanced → **SSH Access** → enable. Note the **IP/host**, the **port** (usually `65002`) and the **user** (`u727297856`).

### 5. SSH key for GitHub
On your computer (PowerShell or Git Bash):

```bash
ssh-keygen -t ed25519 -C "github-deploy-faro" -f faro_deploy -N ""
```

This creates two files:
- `faro_deploy.pub` (**public** key): hPanel → SSH Access → **SSH Keys** → Add → paste its content.
- `faro_deploy` (**private** key): goes into the `SSH_KEY` secret below. Never send it to anyone, never commit it and never screenshot it.

### 6. GitHub secrets and variables
Repository → **Settings → Secrets and variables → Actions**.

**Secrets** (Secrets tab → New repository secret):

| Name | Value |
|---|---|
| `SSH_HOST` | IP/host from step 4 |
| `SSH_PORT` | port from step 4 (e.g. `65002`) |
| `SSH_USER` | `u727297856` |
| `SSH_KEY` | the whole content of `faro_deploy` (from `-----BEGIN` to `END-----`) |
| `DEPLOY_PATH` | `domains/faro.marcusmoreira.com.br` |
| `ENV_FILE` | the whole production `.env` (template below) |

**Variables** (Variables tab → New repository variable):

| Name | Value |
|---|---|
| `DEPLOY_ENABLED` | `true` (create it only after the secrets: it's what turns the deploy on) |
| `APP_URL` | `https://faro.marcusmoreira.com.br` |
| `PHP_BIN` | optional. If `php -v` over SSH doesn't show 8.3, use `/opt/alt/php83/usr/bin/php` |

### 7. `ENV_FILE` template

```dotenv
APP_NAME=Faro
APP_ENV=production
APP_KEY=base64:GENERATE_A_KEY
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
DB_PASSWORD='DATABASE_PASSWORD'

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

# Email (Hostinger). Sign-up codes and 2FA depend on it, so keep MAIL_MAILER=smtp
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@marcusmoreira.com.br
MAIL_PASSWORD='EMAIL_PASSWORD'
MAIL_FROM_ADDRESS=no-reply@marcusmoreira.com.br
MAIL_FROM_NAME="Faro"

FARO_REPO_URL=https://github.com/MarcusVBMoreira/faro-vagas
AI_BASE_URL=https://ia.marcusmoreira.com.br
AI_API_KEY=SAME_VALUE_AS_THE_FARO_IA_TOKEN_SECRET
AI_MODEL=qwen2.5:3b
AI_EMBEDDING_MODEL=bge-m3
AI_DAILY_LIMIT_PER_USER=50
```

- Passwords containing `$`, `#` or spaces go in **single quotes**.
- `APP_FALLBACK_LOCALE` must stay `en`: translation keys are the Portuguese sentences themselves, and `lang/pt_BR.json` maps each one to itself.
- To generate `APP_KEY`, run over Hostinger SSH: `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"`
- To change something in `.env` later: edit the `ENV_FILE` secret and run the workflow again (Actions → CI e Deploy → Run workflow).

### 8. First deploy
Actions → **CI e Deploy** → **Run workflow** (or push to `main`). At the end, the "Conferir se o site respondeu" step requests `https://faro.marcusmoreira.com.br/up`, and the diagnostic step prints the status of the database, AI, email, `gd` and cron (no secrets are printed).

## Cron (required since week 1)
Résumé analysis runs in a queue, and on shared hosting the queue is processed by Laravel's scheduler.
hPanel → Advanced → **Cron Jobs** → custom command, every minute:

```
/bin/bash /home/u727297856/domains/faro.marcusmoreira.com.br/faro-app/cron/schedule.sh
```

The `cron/schedule.sh` script does the `cd`, runs `php artisan schedule:run` and writes the output to `storage/logs/cron.log`. The hPanel field didn't handle redirections (`>> ... 2>&1`) well when typed directly in the command.

## VPS (AI endpoint)
The AI server is managed by versioned scripts in `ops/vps/`, run by the `vps-ops.yml` workflow when they (or `ops/vps/run.txt`) change on `main`. `04-ia-https-token.sh` configures nginx, HTTPS, the bearer token and the 600 s timeouts that long résumé analyses need.

## Common problems
- **`Permission denied (publickey)`**: the public key wasn't added in hPanel, or `SSH_KEY` doesn't hold the whole private key.
- **`rsync: command not found` on the server**: the upload can be switched to `tar` over SSH.
- **HTTP 500**: check `faro-app/storage/logs/` in the File Manager. It's almost always an empty `APP_KEY` or a wrong database password in `ENV_FILE`.
- **Hostinger's default page shows up**: the `public_html → faro-app/public` link wasn't created. Check the log of the "Finalizar no servidor" step.
- **Sign-up code never arrives**: `MAIL_MAILER` is still `log`, or the SMTP password is wrong. The diagnostic shows "SMTP alcançável" and "Senha do e-mail definida".
- **Profile photo doesn't show**: the `public/storage` link is missing (the deploy recreates it) or `gd` is disabled.
