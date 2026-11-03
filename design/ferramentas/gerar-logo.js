// Gera os arquivos SVG do logo do Faro (marca + wordmark convertido em paths)
const opentype = require('opentype.js');
const fs = require('fs');
const path = require('path');

const NAVY = '#0E1630';
const AMBER = '#F5A524';
const OUT = process.argv[2] || './out';
fs.mkdirSync(OUT, { recursive: true });

const font = opentype.loadSync(
  path.join(__dirname, 'node_modules/@fontsource/space-grotesk/files/space-grotesk-latin-700-normal.woff')
);

// Arco centrado em (cx, cy), raio r, de -a a +a graus (abrindo para a direita)
function arc(cx, cy, r, a) {
  const rad = (d) => (d * Math.PI) / 180;
  const x1 = cx + r * Math.cos(rad(-a)), y1 = cy + r * Math.sin(rad(-a));
  const x2 = cx + r * Math.cos(rad(a)), y2 = cy + r * Math.sin(rad(a));
  return `M${x1.toFixed(2)} ${y1.toFixed(2)} A${r} ${r} 0 0 1 ${x2.toFixed(2)} ${y2.toFixed(2)}`;
}

// Símbolo: lâmpada do farol (ponto) emitindo dois feixes de sinal
function symbol(fg, { x = 0, y = 0, s = 1 } = {}) {
  const t = (v) => v; // coordenadas em grade 64x64
  return `<g transform="translate(${x} ${y}) scale(${s})">
    <circle cx="${t(19)}" cy="${t(32)}" r="7" fill="${fg}"/>
    <path d="${arc(19, 32, 17, 38)}" fill="none" stroke="${fg}" stroke-width="5" stroke-linecap="round"/>
    <path d="${arc(19, 32, 29, 38)}" fill="none" stroke="${fg}" stroke-width="5" stroke-linecap="round" opacity="0.55"/>
  </g>`;
}

function mark({ bg = NAVY, fg = AMBER, size = 64 } = {}) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 64 64" role="img" aria-label="Faro">
  <rect width="64" height="64" rx="16" fill="${bg}"/>
  ${symbol(fg)}
</svg>
`;
}

function wordPath(text, x, baseline, fontSize, fill) {
  const p = font.getPath(text, x, baseline, fontSize, { kerning: true });
  return { d: p.toPathData(2), width: font.getAdvanceWidth(text, fontSize, { kerning: true }), fill };
}

function horizontal({ bg = NAVY, fg = AMBER, word = NAVY } = {}) {
  const fontSize = 46;
  const w = wordPath('faro', 0, 0, fontSize, word);
  const gap = 14;
  const width = Math.ceil(64 + gap + w.width + 2);
  const wp = font.getPath('faro', 64 + gap, 47, fontSize, { kerning: true });
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="64" viewBox="0 0 ${width} 64" role="img" aria-label="Faro">
  <rect width="64" height="64" rx="16" fill="${bg}"/>
  ${symbol(fg)}
  <path d="${wp.toPathData(2)}" fill="${word}"/>
</svg>
`;
}

function symbolOnly(fg) {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64" role="img" aria-label="Faro">
  ${symbol(fg)}
</svg>
`;
}

const files = {
  'faro-mark.svg': mark(),
  'faro-mark-amber.svg': mark({ bg: AMBER, fg: NAVY }),
  'faro-logo.svg': horizontal(),
  'faro-logo-dark.svg': horizontal({ word: '#FFFFFF' }),
  'faro-symbol.svg': symbolOnly(AMBER),
  'favicon.svg': mark(),
};
for (const [name, svg] of Object.entries(files)) fs.writeFileSync(path.join(OUT, name), svg);
console.log('ok', Object.keys(files).join(', '));
