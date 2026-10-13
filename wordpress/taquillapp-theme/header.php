<?php
/**
 * Generado por tools/build-wp-theme.py desde index.html. No editar a mano.
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- ============ ICON SPRITE ============ -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <linearGradient id="g-brand" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#F07833"/><stop offset=".5" stop-color="#FF5C4D"/><stop offset="1" stop-color="#FF2D78"/>
    </linearGradient>
  </defs>
  <symbol id="i-ticket" viewBox="0 0 48 48"><path d="M6 14a4 4 0 0 1 4-4h28a4 4 0 0 1 4 4v5a5 5 0 0 0 0 10v5a4 4 0 0 1-4 4H10a4 4 0 0 1-4-4v-5a5 5 0 0 0 0-10z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="M30 12v24" stroke="currentColor" stroke-width="3" stroke-dasharray="3 4"/></symbol>
  <symbol id="i-glasses" viewBox="0 0 48 48"><path d="M4 18h40l-3 14a4 4 0 0 1-4 3h-6a4 4 0 0 1-4-3l-1-4h-4l-1 4a4 4 0 0 1-4 3h-6a4 4 0 0 1-4-3z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="M8 18l4-8M40 18l-4-8" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></symbol>
  <symbol id="i-star" viewBox="0 0 48 48"><path d="M24 5l5.6 12.2 13.4 1.5-10 9 2.8 13.2L24 34.3l-11.8 6.6L15 27.7l-10-9 13.4-1.5z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/></symbol>
  <symbol id="i-crown" viewBox="0 0 48 48"><path d="M6 15l9 8 9-13 9 13 9-8-4 22H10z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="M10 41h28" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></symbol>
  <symbol id="i-spark" viewBox="0 0 48 48"><path d="M24 4c2 11 9 18 20 20-11 2-18 9-20 20-2-11-9-18-20-20 11-2 18-9 20-20z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/></symbol>
  <symbol id="i-popcorn" viewBox="0 0 48 48"><path d="M10 18h28l-4 26H14z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="M19 18l1 26M29 18l-1 26" stroke="currentColor" stroke-width="3"/><path d="M11 18a5 5 0 0 1 5-8 6 6 0 0 1 11-3 6 6 0 0 1 10 4 5 5 0 0 1 1 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></symbol>
  <symbol id="i-hotdog" viewBox="0 0 48 48"><rect x="4" y="18" width="40" height="14" rx="7" fill="none" stroke="currentColor" stroke-width="3"/><path d="M8 25c4-3 6 3 10 0s6 3 10 0 6 3 10 0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path d="M8 32c0 5 4 7 8 7h16c4 0 8-2 8-7" fill="none" stroke="currentColor" stroke-width="3"/></symbol>
  <symbol id="i-nachos" viewBox="0 0 48 48"><path d="M6 30h36l-4 12H10z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="M12 30l6-20 6 20M22 30l8-16 6 16" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/></symbol>
  <symbol id="i-duo" viewBox="0 0 48 48"><path d="M4 16a3 3 0 0 1 3-3h20a3 3 0 0 1 3 3v3a4 4 0 0 0 0 8v3a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-3a4 4 0 0 0 0-8z" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="M34 13h7a3 3 0 0 1 3 3v3a4 4 0 0 0 0 8v3a3 3 0 0 1-3 3h-7" fill="none" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/></symbol>
  <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-cart" viewBox="0 0 24 24"><path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.5L21 8H6.2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4" fill="currentColor"/><circle cx="17" cy="20" r="1.4" fill="currentColor"/></symbol>
  <symbol id="i-close" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
  <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M20 20l-4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></symbol>
  <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
  <symbol id="i-minus" viewBox="0 0 24 24"><path d="M5 12h14" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-bolt" viewBox="0 0 24 24"><path d="M13 2L4 14h7l-1 8 9-12h-7z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8.5 12l2.5 2.5 4.5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-mail" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></symbol>
  <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></symbol>
</svg>

<!-- ============ NAV ============ -->
<header class="nav" id="nav">
  <div class="nav__inner">
    <a href="<?php echo esc_url( tq_anchor( 'top' ) ); ?>" class="nav__logo" aria-label="TaquillApp, inicio">
      <img src="<?php echo esc_url( tq_asset( 'img/' ) ); ?>logo-light.png" alt="TaquillApp" width="158" height="32">
    </a>
    <nav class="nav__links" aria-label="Principal">
      <a href="<?php echo esc_url( tq_anchor( 'catalogo' ) ); ?>">Catálogo</a>
      <a href="<?php echo esc_url( tq_anchor( 'como-funciona' ) ); ?>">Cómo funciona</a>
      <a href="<?php echo esc_url( tq_anchor( 'por-que' ) ); ?>">Por qué TaquillApp</a>
      <a href="<?php echo esc_url( tq_anchor( 'faq' ) ); ?>">Preguntas</a>
    </nav>
    <div class="nav__actions">
      <a href="<?php echo esc_url( tq_anchor( 'catalogo' ) ); ?>" class="btn btn--ghost btn--sm nav__cta">Ver catálogo</a>
      <button class="cart-btn" id="cartOpen" aria-label="Abrir carrito">
        <svg><use href="#i-cart"/></svg>
        <span class="cart-btn__count" id="cartCount">0</span>
      </button>
      <button class="nav__burger" id="menuBtn" aria-label="Menú"><svg><use href="#i-menu"/></svg></button>
    </div>
  </div>
  <div class="nav__mobile" id="mobileMenu">
    <a href="<?php echo esc_url( tq_anchor( 'catalogo' ) ); ?>">Catálogo</a>
    <a href="<?php echo esc_url( tq_anchor( 'como-funciona' ) ); ?>">Cómo funciona</a>
    <a href="<?php echo esc_url( tq_anchor( 'por-que' ) ); ?>">Por qué TaquillApp</a>
    <a href="<?php echo esc_url( tq_anchor( 'faq' ) ); ?>">Preguntas</a>
  </div>
</header>

