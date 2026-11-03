# design/

Material de identidade visual e design do Faro. **Esta pasta fica só no GitHub:** o deploy (`.github/workflows/ci-deploy.yml`) ignora `design/` e `docs/`, então nada daqui vai para a Hostinger.

| Pasta | Conteúdo |
|---|---|
| `logo/` | Logos em SVG (vetor, fonte da verdade) e `png/` exportados para redes sociais, LinkedIn e apresentações |
| `identidade/` | `guia-da-marca.md` (voz, logo, cores, tipografia, formas), `tokens.json` (todas as cores, fontes, espaçamentos e raios, com tema claro e escuro) e a capa da identidade |
| `telas/` | Capturas e modelos das telas do site, versão por versão |
| `ferramentas/` | Scripts usados para gerar o logo e conferir contraste das cores |

## Logos

| Arquivo | Uso |
|---|---|
| `faro-logo.svg` | Logotipo horizontal em fundo claro |
| `faro-logo-dark.svg` | Logotipo horizontal em fundo escuro |
| `faro-mark.svg` | Ícone (app, favicon, foto de perfil) |
| `faro-mark-amber.svg` | Ícone inverso, para fundo azul-noite |
| `faro-symbol.svg` | Só a luz, sem fundo |

As cópias usadas pelo site ficam em `public/images/`. Se o logo mudar, atualize as duas pastas.

## Regerar o logo

```bash
cd design/ferramentas
npm install
node gerar-logo.js ../logo
```

## Conferir contraste de cores

```bash
node design/ferramentas/contraste.js "#0E1630:#FAFAF7" "#9A5B00:#FFFFFF"
```

Texto precisa de no mínimo **4.5:1** (WCAG AA); títulos grandes, 3:1.
