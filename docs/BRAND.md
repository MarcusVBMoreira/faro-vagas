# Visual identity

**Faro**: in Portuguese, *faro* is the instinct for sniffing out the right opportunity, and it echoes *farol* (lighthouse), the light that shows the way.
**Tagline:** A IA que encontra as vagas certas para você. *(The AI that finds the right jobs for you.)*

## Logo
Files in `public/images/`:

| File | Use |
|---|---|
| `faro-logo.svg` | Logotype on light backgrounds |
| `faro-logo-dark.svg` | Logotype on dark backgrounds |
| `faro-mark.svg` | App icon and favicon (`public/favicon.svg`) |
| `faro-mark-amber.svg` | Inverted icon, for night-blue backgrounds |
| `faro-symbol.svg` | The light only, no background |

The symbol is the lighthouse lamp (an amber dot) casting two beams, on a night-blue square. The word "faro" is Space Grotesk Bold converted to outlines.

## Colors

| Token | Hex | Use |
|---|---|---|
| `noite` | `#0E1630` | Brand color, main text, dark backgrounds |
| `farol` | `#F5A524` | Primary button (one per screen) and the logo light. Text on it: `noite` |
| `farol-soft` | `#FCE9C4` | Soft highlight ("new" badge) |
| `accent-text` | `#9A5B00` | Links and text highlights on light backgrounds |
| `sinal` | `#0F766E` | Everything AI and match (compatibility score) |
| `surface` | `#FAFAF7` | Page background |
| `raised` | `#FFFFFF` | Cards |
| `sunken` | `#F1F0EA` | Recessed areas |
| `line` | `#E2E0D8` | Borders |
| `ink-muted` | `#4B5571` | Secondary text |
| `success` / `danger` | `#15803D` / `#B91C1C` | States |

Every text pair passes WCAG AA contrast (4.5:1). Amber is **never** a text color on light backgrounds.

## Typography
- **Space Grotesk** 600/700 for headings (`font-display`)
- **Inter** 400/500/600 for body text (`font-sans`)

## Shapes
4px grid. Radii: 6px (badges), 10px (buttons), 16px (cards). Stroke icons at 1.75px.

## Voice
Plain, warm and direct, for people of every profession and education level: no jargon, short sentences, and always say *why* (why a job fits, why a field is asked for). The site speaks Portuguese, English and Spanish.

The site tokens live in `resources/css/app.css`. The full material (SVG and PNG logos, `tokens.json`, brand guide and screenshots) is in [`design/`](../design/), a folder that is not deployed.
