#!/usr/bin/env bash
# Confere a configuração de e-mail SEM imprimir a senha (o repositório é público).
# Mostra só fatos que ajudam a achar o erro: usuário, host, porta, se a senha tem
# caracteres que costumam quebrar o .env e se o login funciona em 465 e em 587.
set -uo pipefail
cd "$HOME/domains/faro.marcusmoreira.com.br/faro-app" || exit 1
echo ".env atualizado em: $(date -r .env '+%Y-%m-%d %H:%M:%S')"
echo "Linhas MAIL_PASSWORD no .env: $(grep -c '^MAIL_PASSWORD=' .env)"
php -d display_errors=1 -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = config("mail.mailers.smtp");
$p = (string) ($c["password"] ?? "");
$yn = fn ($b) => $b ? "sim" : "não";
echo "Usuário ............ ".($c["username"] ?? "(vazio)").PHP_EOL;
echo "Remetente .......... ".config("mail.from.address").PHP_EOL;
echo "Host/porta/scheme .. ".$c["host"].":".$c["port"]." ".($c["scheme"] ?? "(padrão)").PHP_EOL;
echo "Senha vazia ........ ".$yn($p === "").PHP_EOL;
echo "Espaço nas pontas .. ".$yn($p !== trim($p)).PHP_EOL;
echo "Tem aspas .......... ".$yn(strpbrk($p, "\"\x27") !== false).PHP_EOL;
echo "Tem \$ # ou \\ ....... ".$yn(strpbrk($p, "\$#\\") !== false).PHP_EOL;
foreach ([["smtps", 465], ["smtp", 587]] as [$scheme, $port]) {
    try {
        $t = new Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport($c["host"], $port, $scheme === "smtps");
        $t->setUsername($c["username"]); $t->setPassword($p);
        $t->start(); $t->stop();
        echo "Login $scheme:$port ...... OK".PHP_EOL;
    } catch (Throwable $e) {
        preg_match("/\b(5\d\d|4\d\d)\b[^\"]{0,60}/", $e->getMessage(), $m);
        echo "Login $scheme:$port ...... FALHOU ".($m[0] ?? get_class($e)).PHP_EOL;
    }
}
' 2>&1 | sed -E 's/(password|senha)=\S+/\1=<oculto>/Ig'
true
