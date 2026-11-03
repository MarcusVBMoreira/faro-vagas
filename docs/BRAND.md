# Identidade visual

**Faro**: *faro* é o instinto de farejar a oportunidade certa, e lembra *farol*, a luz que mostra o caminho.
**Tagline:** A IA que encontra as vagas certas para você.

## Logo
Arquivos em `public/images/`:

| Arquivo | Uso |
|---|---|
| `faro-logo.svg` | Logotipo para fundo claro |
| `faro-logo-dark.svg` | Logotipo para fundo escuro |
| `faro-mark.svg` | Ícone do app e favicon (`public/favicon.svg`) |
| `faro-mark-amber.svg` | Ícone inverso, para fundo azul-noite |
| `faro-symbol.svg` | Só a luz, sem fundo |

O símbolo é a lâmpada do farol (ponto âmbar) emitindo dois feixes, sobre um quadrado azul-noite. O texto "faro" é Space Grotesk Bold convertido em curvas.

## Cores

| Token | Hex | Uso |
|---|---|---|
| `noite` | `#0E1630` | Cor da marca, texto principal, fundos escuros |
| `farol` | `#F5A524` | Botão principal (um por tela) e a luz do logo. Texto sobre ele: `noite` |
| `farol-soft` | `#FCE9C4` | Destaque suave (badge "nova") |
| `accent-text` | `#9A5B00` | Links e destaques em texto sobre fundo claro |
| `sinal` | `#0F766E` | Tudo que é IA e match (nota de compatibilidade) |
| `surface` | `#FAFAF7` | Fundo da página |
| `raised` | `#FFFFFF` | Cards |
| `sunken` | `#F1F0EA` | Áreas recuadas |
| `line` | `#E2E0D8` | Bordas |
| `ink-muted` | `#4B5571` | Texto secundário |
| `success` / `danger` | `#15803D` / `#B91C1C` | Estados |

Todos os pares de texto passam no contraste WCAG AA (4.5:1). O âmbar **não** é cor de texto em fundo claro.

## Tipografia
- **Space Grotesk** 600/700 para títulos (`font-display`)
- **Inter** 400/500/600 para texto (`font-sans`)

## Formas
Grade de 4px. Raios: 6px (badges), 10px (botões), 16px (cards). Ícones de traço 1.75px.

Os tokens do site estão em `resources/css/app.css`. O material completo (logos em SVG e PNG, `tokens.json`, guia da marca e capturas das telas) fica em [`design/`](../design/), pasta que não vai para a hospedagem.
