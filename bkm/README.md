# BKM · Business Key Medical — website

Dark-luxury corporate site for BKM – Business Key Medical (English). Static, no build step.

```bash
python3 -m http.server 8000   # then open http://localhost:8000/bkm/
```

## Structure

- `index.html` — Hero (office video) · Who we are · Problem we solve · Platform (Trade, Engine Capital, Operating Model) · Brand integration models · Mexico City hub · Contact · Footer. No key figures are published (internal information).
- `css/styles.css` — tokens from the BKM ID manual: gold `#A89613` (Pantone 104 C), charcoal `#1C1C22` (Pantone 4575 C), silver `#B9BFC5`.
- `js/main.js` — sticky header, mobile menu, scroll reveals, contact form (opens a pre-filled email to contact@bkm.com.mx).
- `assets/` — official BKM logo from the Logo Book SVG, unmodified: `bkm-logo.svg` (symbol + BUSINESS KEY MEDICAL) and `bkm-symbol.svg` (symbol only). Only the canvas background was removed and the viewBox cropped to the artwork; always size them by height with `width: auto`. Office video and stills from `BKM - Our offices - 2026.mp4`.

## Pending

- Align every section's copy with bkmcorp.com.mx (the site could not be reached from the build environment).
- Replace the footer LinkedIn search link with the company page URL.
- Connect the contact form to a form service or CRM if needed.
