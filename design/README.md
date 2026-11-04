# design/

Faro's brand and design material. **This folder lives only on GitHub:** the deploy (`.github/workflows/ci-deploy.yml`) skips `design/` and `docs/`, so nothing here goes to Hostinger.

| Folder | Content |
|---|---|
| `logo/` | SVG logos (vector, source of truth) and `png/` exports for social media, LinkedIn and presentations |
| `identidade/` | `guia-da-marca.md` (brand guide in Portuguese: voice, logo, colors, typography, shapes), `tokens.json` (every color, font, spacing and radius, light and dark themes) and the identity cover |
| `telas/` | Screenshots and mockups of the site, version by version |
| `ferramentas/` | Scripts used to generate the logo and check color contrast |

## Logos

| File | Use |
|---|---|
| `faro-logo.svg` | Horizontal logotype on light backgrounds |
| `faro-logo-dark.svg` | Horizontal logotype on dark backgrounds |
| `faro-mark.svg` | Icon (app, favicon, profile picture) |
| `faro-mark-amber.svg` | Inverted icon, for night-blue backgrounds |
| `faro-symbol.svg` | The light only, no background |

The copies used by the site are in `public/images/`. If the logo changes, update both folders.

## Regenerate the logo

```bash
cd design/ferramentas
npm install
node gerar-logo.js ../logo
```

## Check color contrast

```bash
node design/ferramentas/contraste.js "#0E1630:#FAFAF7" "#9A5B00:#FFFFFF"
```

Text needs at least **4.5:1** (WCAG AA); large headings, 3:1.
