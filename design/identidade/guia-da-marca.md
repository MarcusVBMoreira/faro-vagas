# Faro — identidade visual

**Faro** é uma plataforma que usa IA para encontrar as vagas de estágio e júnior mais compatíveis com o currículo de cada pessoa.

O nome tem dois sentidos: *faro* é o instinto de farejar a oportunidade certa, e lembra *farol*, a luz que mostra o caminho no escuro. Toda a identidade sai dessa imagem: um **azul-noite** (o mar à noite, a incerteza de quem procura emprego) e um **âmbar** (a luz do farol, a vaga certa aparecendo).

**Tagline:** A IA que encontra as vagas certas para você.

## Voz e tom

O Faro fala como um veterano da faculdade que já passou por processos seletivos: direto, encorajador, sem jargão de RH.

- **Faça:** frases curtas, verbos no imperativo ("Envie seu currículo", "Veja por que essa vaga combina"), números concretos ("82% compatível", "faltam 2 habilidades").
- **Evite:** promessas ("garanta sua vaga!"), excesso de exclamação, emoji em interface, "candidato" no lugar de "você".
- **Explique a IA:** toda nota de match vem com o motivo. A IA sugere, quem decide é a pessoa.

## Logo

- **Símbolo:** um ponto âmbar (a lâmpada do farol) emitindo dois feixes de sinal, sobre um quadrado azul-noite com cantos `radius-lg` (16 em 64). O feixe externo tem 55% de opacidade: a luz se espalhando.
- **Logotipo:** símbolo + "faro" em minúsculas, Space Grotesk Bold, convertido em curvas.
- **Versões:** `faro-logo.svg` (fundo claro), `faro-logo-dark.svg` (fundo escuro, texto branco), `faro-mark.svg` (ícone do app e favicon), `faro-mark-amber.svg` (inverso, para fundos azul-noite), `faro-symbol.svg` (só a luz, sem fundo).
- **Área de respiro:** no mínimo metade da altura do símbolo em volta do logo. Tamanho mínimo do símbolo: 16px (favicon).
- **Não faça:** mudar as cores, esticar, colocar o símbolo âmbar sobre fundo claro, adicionar sombra ou gradiente.

## Cores

A paleta é **azul-noite + âmbar**, com um verde-azulado (`signal`) reservado para tudo o que é IA e match.

- `brand-night` e `brand-amber` são as cores da marca. O âmbar aparece **pouco e com intenção**: o botão principal de cada tela (um por tela) e a luz do logo.
- Texto sobre `brand-amber` é sempre `on-amber` (azul-noite), nunca branco.
- Âmbar **não é cor de texto** em fundo claro (contraste baixo). Para links e destaques em texto, use `accent-text`.
- `signal` significa "a IA analisou isto": nota de compatibilidade, selo de análise. Não use para decoração.
- `success` e `danger` são só para estado.
- Tema escuro: os mesmos tokens com outros valores. O fundo vira o próprio azul-noite.

## Tipografia

- **Space Grotesk** (títulos, `display`): geométrica e técnica, com personalidade. Pesos 600 e 700.
- **Inter** (texto, `sans`): legível em telas densas de lista de vagas. Pesos 400, 500 e 600.
- Ambas gratuitas no Google Fonts.
- Títulos em frase normal ("Vagas para você"), nunca TUDO MAIÚSCULO.

## Formas e espaçamento

- Grade de 4px (`space-1` a `space-12`).
- Cards de vaga: `surface-raised`, borda 1px `line`, raio `radius-lg`, padding `space-4`, sem sombra pesada (no máximo uma sombra sutil no hover).
- Nota de match: pílula (`radius-full`) com fundo `signal` a 12% e texto `signal`.
- Botões: `radius-md`, altura 40px. Primário: `brand-amber` com texto `on-amber`. Secundário: `surface-raised` com borda `line`.
- Foco do teclado: anel `focus-ring` de 2px com 2px de respiro.

## Iconografia

Ícones de traço (Lucide, 1.75px), em `currentColor`, tamanho 20px na interface. Nada de ícones preenchidos coloridos.
