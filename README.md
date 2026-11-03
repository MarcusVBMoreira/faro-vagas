<p align="center">
  <img src="public/images/faro-logo.svg" alt="Faro" height="64">
</p>

<p align="center"><strong>A IA que encontra as vagas certas para você.</strong></p>

<p align="center">
  <a href="https://faro.marcusmoreira.com.br">faro.marcusmoreira.com.br</a> ·
  <a href="docs/ROADMAP.md">Roadmap</a> ·
  <a href="docs/DATABASE.md">Banco de dados</a> ·
  <a href="docs/DEPLOY.md">Deploy</a>
</p>

---

O **Faro** é uma plataforma para estudantes e pessoas em início de carreira em tecnologia. Você envia o currículo, a IA monta seu perfil e o sistema mostra as vagas de estágio e júnior mais compatíveis com você, cada uma com uma **nota de compatibilidade**, seus **pontos fortes** e o que **falta aprender**.

> 🚧 Projeto de portfólio em construção, desenvolvido em público. Acompanhe pelo [roadmap](docs/ROADMAP.md).

## Como o match funciona

Mandar todas as vagas para a IA seria caro e lento. O Faro usa um funil em três etapas:

1. **Filtros SQL**: nível, modalidade, cidade e semestre eliminam a maior parte das vagas sem custo.
2. **Similaridade semântica**: o perfil e cada vaga viram vetores (embeddings) e o sistema separa as ~20 vagas mais parecidas.
3. **Avaliação da IA**: só essas 20 vão para o modelo de linguagem, que devolve um JSON validado com nota, pontos fortes, lacunas e explicação.

### IA própria, sem API paga

A IA roda num VPS próprio com **Ollama**, publicada em `ia.marcusmoreira.com.br` atrás de nginx com HTTPS, token e limite de requisições:

| Parte | Como | Por quê |
|---|---|---|
| Embeddings | `bge-m3` no VPS (~1 s por vaga) | Rápido em CPU e entende português |
| Estruturar vagas e currículos | `qwen2.5:3b` no VPS, em fila | Acertou todos os campos no teste; o modelo 1.5b errava nível e tipos |
| Nota, pontos fortes e lacunas | Código PHP (comparação das listas de habilidades + similaridade) | Resposta exata: no teste, os modelos pequenos inventavam lacunas |

Resultado: custo zero por uso e **currículos que nunca saem dos servidores do projeto** (LGPD). Os testes de desempenho estão em `ops/vps/`.

## Tecnologias

- **Laravel 12** (PHP 8.3), Blade, Tailwind CSS 4, Vite
- **MySQL** em produção, filas e cache no próprio banco
- **IA**: Ollama no VPS (`bge-m3` + `qwen2.5:3b`), acessado pelo Laravel via HTTPS
- **PHPUnit** com testes rodando em MySQL 8 no **GitHub Actions**
- **Deploy contínuo** na Hostinger via SSH a cada push na `main`

## Rodando localmente

Requisitos: PHP 8.2+, Composer, Node 20+ (no Windows, o XAMPP já traz o PHP).

```bash
git clone https://github.com/MarcusVBMoreira/faro-vagas.git
cd faro-vagas
composer install
copy .env.example .env        # no Linux/Mac: cp .env.example .env
php artisan key:generate
php artisan migrate --seed    # usa SQLite em database/database.sqlite
npm install
composer run dev              # servidor em http://localhost:8000
```

Para usar o MySQL do XAMPP, troque no `.env`: `DB_CONNECTION=mysql`, `DB_DATABASE=faro`, `DB_USERNAME=root`, `DB_PASSWORD=` (vazio).

Testes: `php artisan test`

## Estrutura

```
app/Enums/        níveis, modalidades e status do Kanban
app/Models/       Profile, Skill, Resume, JobSource, JobPosting, JobMatch, Application...
config/faro.php   configurações do projeto (IA, limites, match)
database/         migrations, seeders e factories
docs/             roadmap, banco de dados, deploy e identidade visual
```

## Autor

**Marcus Moreira**, estudante de Engenharia de Software na Fundação Santo André.
