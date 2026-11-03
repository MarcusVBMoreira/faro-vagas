// Confere contraste WCAG dos pares de cor da paleta
function lum(hex) {
  const c = hex.replace('#', '').match(/../g).map((h) => parseInt(h, 16) / 255)
    .map((v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4));
  return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
}
function ratio(a, b) {
  const [x, y] = [lum(a), lum(b)].sort((m, n) => n - m);
  return ((x + 0.05) / (y + 0.05)).toFixed(2);
}
const pairs = process.argv.slice(2).map((p) => p.split(':'));
for (const [fg, bg] of pairs) console.log(`${fg} on ${bg}: ${ratio(fg, bg)}`);
