# Roadmap

Regra do projeto: **toda etapa termina no ar**. Cada push na `main` roda os testes e, se passarem, publica em https://faro.marcusmoreira.com.br.

## Etapa 0 · Fundação ✅
- [x] Nome, logo, paleta e tipografia (docs/BRAND.md)
- [x] Projeto Laravel 12 em português (locale `pt_BR`, fuso de São Paulo)
- [x] Modelagem completa do banco: migrations, models, enums e seeders (docs/DATABASE.md)
- [x] Página inicial com a identidade visual
- [x] Testes automáticos (PHPUnit) rodando em MySQL 8 no GitHub Actions
- [x] Deploy automático na Hostinger via SSH (docs/DEPLOY.md)
- [ ] Configurar os secrets e fazer o primeiro deploy

## Semana 1 · Contas e perfil com IA
- [ ] Cadastro, login, recuperação de senha (Laravel Breeze, telas com a marca)
- [ ] E-mails de verificação pelo SMTP da Hostinger
- [ ] Upload do currículo em PDF (até 5 MB) e extração do texto (`smalot/pdfparser`)
- [ ] Serviço de IA (`app/Services/Ai`) com o primeiro provedor (Gemini) e registro em `ai_usage_logs`
- [ ] A IA lê o currículo e devolve um JSON validado com o perfil
- [ ] Tela "Meu perfil": revisar habilidades, nível, cidade e modalidade
- [ ] Excluir conta e todos os dados (LGPD)
- **Entregável:** envio meu currículo e vejo meu perfil montado.

## Semana 2 · Coleta de vagas
- [ ] Interface `JobSourceConnector` e o conector de issues do GitHub
- [ ] Comando `faro:fetch-jobs` agendado 1x por dia
- [ ] Cron da Hostinger rodando o agendador do Laravel
- [ ] A IA estrutura cada vaga (nível, modalidade, cidade, tecnologias) em fila
- [ ] Página de vagas com busca e filtros
- [ ] Adicionar vaga manualmente (colar link ou texto)
- **Entregável:** o banco se enche de vagas sozinho.

## Semana 3 · Match com IA
- [ ] Embeddings do perfil e das vagas
- [ ] `MatchService`: filtros SQL, similaridade em PHP, top 20
- [ ] A IA avalia as 20 melhores: nota, pontos fortes, lacunas e explicação
- [ ] Tela "Vagas para você" com os cards de match
- [ ] Feedback "boa vaga / não tem a ver"
- **Entregável:** o coração do projeto funcionando.

## Semana 4 · Produto e acabamento
- [ ] Kanban de candidaturas (SortableJS)
- [ ] Resumo diário por e-mail com as melhores vagas novas
- [ ] Limite de uso de IA por usuário e tratamento de falhas da IA
- [ ] Dashboard: candidaturas por etapa, tecnologias mais pedidas
- [ ] README final com GIF e diagrama do funil de match
- **Entregável:** projeto pronto para o portfólio.

## Depois do primeiro mês
- Plano de estudos a partir das lacunas mais frequentes
- Carta de apresentação gerada para cada vaga
- Tema escuro
- Migrar para a VPS (fila sempre ativa, busca vetorial nativa) e documentar a migração
