# Roadmap

Regra do projeto: **toda etapa termina no ar**. Cada push na `main` roda os testes e, se passarem, publica em https://faro.marcusmoreira.com.br.

## Etapa 0 · Fundação ✅
- [x] Nome, logo, paleta e tipografia (docs/BRAND.md)
- [x] Projeto Laravel 12 em português (locale `pt_BR`, fuso de São Paulo)
- [x] Modelagem completa do banco: migrations, models, enums e seeders (docs/DATABASE.md)
- [x] Página inicial com a identidade visual
- [x] Testes automáticos (PHPUnit) rodando em MySQL 8 no GitHub Actions
- [x] Deploy automático na Hostinger via SSH (docs/DEPLOY.md)
- [x] Configurar os secrets e fazer o primeiro deploy
- [x] Benchmark de IA local no VPS e publicação em `ia.marcusmoreira.com.br` (HTTPS + token)

## Semana 1 · Contas e perfil com IA ✅
- [x] Cadastro, login (com limite de tentativas), logout e redefinição de senha, tudo em pt-BR
- [x] E-mails de redefinição pelo SMTP da Hostinger (`no-reply@marcusmoreira.com.br`)
- [x] Upload do currículo em PDF (até 5 MB, 5 por dia) e extração do texto (`smalot/pdfparser`)
- [x] IA própria no VPS (Ollama + `qwen2.5:3b`) com cliente no Laravel, token, log de uso e limite diário
- [x] A IA lê o currículo e devolve JSON (com JSON Schema), corrigido pelo `ProfileNormalizer`
- [x] Tela "Meu perfil": revisar habilidades, nível, cidade e modalidade; status da análise ao vivo
- [x] Política de privacidade e exclusão de conta com todos os dados (LGPD)
- **Entregável:** envio meu currículo e vejo meu perfil montado.

## Semana 2 · Coleta de vagas
- [ ] Interface `JobSourceConnector` e o conector de issues do GitHub
- [ ] Comando `faro:fetch-jobs` agendado 1x por dia
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
