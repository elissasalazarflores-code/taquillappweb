#!/usr/bin/env python3
"""Genera las plantillas del tema de WordPress a partir del sitio estático.

El sitio estático (index.html, css/, js/, assets/) es la fuente única del diseño.
Este script produce en wordpress/taquillapp-theme/:
  header.php, front-page.php, footer.php  (partidos desde index.html)
  assets/css, assets/js, assets/img        (copiados)
y empaqueta dist/taquillapp-theme.zip y dist/taquillapp-commerce.zip.

Uso:  python3 tools/build-wp-theme.py
"""
import re
import shutil
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
THEME = ROOT / "wordpress" / "taquillapp-theme"
PLUGIN = ROOT / "wordpress" / "taquillapp-commerce"
DIST = ROOT / "dist"

GENERATED = "<?php\n/**\n * Generado por tools/build-wp-theme.py desde index.html. No editar a mano.\n */\n?>\n"


def cut(html, start, end):
    i = html.index(start)
    j = html.index(end, i)
    return html[i:j]


def to_php(chunk, front):
    out = chunk
    out = out.replace('src="assets/', 'src="<?php echo esc_url( tq_asset( \'img/\' ) ); ?>')
    # Anclas de la portada: en páginas internas apuntan a la portada.
    if not front:
        # Solo enlaces <a>: los íconos (<use href="#i-…">) deben seguir apuntando al sprite de la misma página.
        out = re.sub(r'(<a\b[^>]*?)href="#([a-z0-9-]+)"', lambda m: f'{m.group(1)}href="<?php echo esc_url( tq_anchor( \'{m.group(2)}\' ) ); ?>"', out)
    out = out.replace('href="#top"', 'href="<?php echo esc_url( home_url( \'/\' ) ); ?>"')
    out = out.replace(
        'mailto:hola@taquillapp.com.mx',
        "mailto:<?php echo esc_attr( tq_contact_email() ); ?>",
    ).replace('>hola@taquillapp.com.mx<', "><?php echo esc_html( tq_contact_email() ); ?><")
    legal = {
        "Términos y condiciones": "terminos-y-condiciones",
        "Aviso de privacidad": "aviso-de-privacidad",
        "Política de reembolsos": "politica-de-reembolsos",
    }
    for label, slug in legal.items():
        out = out.replace(f'<a href="#">{label}</a>', f"<a href=\"<?php echo esc_url( tq_page_url( '{slug}' ) ); ?>\">{label}</a>")
    out = out.replace("© 2026 TaquillApp", "© <?php echo esc_html( wp_date( 'Y' ) ); ?> TaquillApp")
    if "<?" in chunk:
        raise SystemExit("index.html contiene '<?': revisa el contenido antes de generar PHP.")
    return out


def main():
    html = (ROOT / "index.html").read_text(encoding="utf-8")

    body_open = cut(html, "<!-- ============ ICON SPRITE ============ -->", '<main id="top">')
    main_html = cut(html, '<main id="top">', "</main>") + "</main>\n"
    footer = cut(html, "<!-- ============ FOOTER ============ -->", '<script src="js/data.js"></script>')

    header_php = (
        GENERATED
        + "<!doctype html>\n<html <?php language_attributes(); ?>>\n<head>\n"
        + '  <meta charset="<?php bloginfo( \'charset\' ); ?>">\n'
        + '  <meta name="viewport" content="width=device-width, initial-scale=1">\n'
        + "  <?php wp_head(); ?>\n</head>\n<body <?php body_class(); ?>>\n<?php wp_body_open(); ?>\n\n"
        + to_php(body_open, front=False)
    )
    # El logo del nav lleva a la portada; la versión dinámica de las anclas resuelve el resto.
    front_php = GENERATED + "<?php get_header(); ?>\n\n" + to_php(main_html, front=True) + "\n<?php get_footer(); ?>\n"
    footer_php = GENERATED + to_php(footer, front=False) + "<?php wp_footer(); ?>\n</body>\n</html>\n"

    (THEME / "header.php").write_text(header_php, encoding="utf-8")
    (THEME / "front-page.php").write_text(front_php, encoding="utf-8")
    (THEME / "footer.php").write_text(footer_php, encoding="utf-8")

    assets = THEME / "assets"
    if assets.exists():
        shutil.rmtree(assets)
    shutil.copytree(ROOT / "css", assets / "css")
    shutil.copytree(ROOT / "js", assets / "js")
    shutil.copytree(ROOT / "assets", assets / "img")

    DIST.mkdir(exist_ok=True)
    for src in (THEME, PLUGIN):
        target = DIST / f"{src.name}.zip"
        with zipfile.ZipFile(target, "w", zipfile.ZIP_DEFLATED) as z:
            for f in sorted(src.rglob("*")):
                if f.is_file():
                    z.write(f, Path(src.name) / f.relative_to(src))
        print("Listo:", target.relative_to(ROOT))


if __name__ == "__main__":
    main()
