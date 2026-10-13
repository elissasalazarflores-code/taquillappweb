<?php
/**
 * Generado por tools/build-wp-theme.py desde index.html. No editar a mano.
 */
?>
<?php get_header(); ?>

<main id="top">

<!-- ============ HERO ============ -->
<section class="hero">
  <div class="hero__glow hero__glow--a"></div>
  <div class="hero__glow hero__glow--b"></div>
  <div class="hero__grid" aria-hidden="true"></div>

  <div class="container hero__inner">
    <div class="hero__copy">
      <span class="eyebrow reveal"><span class="eyebrow__dot"></span>Cinépolis · Cinemex · Código al instante</span>
      <h1 class="hero__title reveal">
        <span class="line">Paga menos.</span><br>
        <span class="grad-text">Ve más cine.</span>
      </h1>
      <p class="hero__lead reveal">
        <strong>Más de 50% de descuento</strong> en las mejores salas,
        para que vivas mejor tu experiencia en el cine. Eliges, pagas y tu código llega en segundos.
      </p>
      <div class="hero__ctas reveal">
        <a href="#catalogo" class="btn btn--grad">Comprar ahora <svg><use href="#i-arrow"/></svg></a>
        <a href="#como-funciona" class="btn btn--ghost">Cómo funciona</a>
      </div>
      <ul class="hero__stats reveal">
        <li><b>+<span data-count="50">0</span>%</b><span>de descuento</span></li>
        <li><b data-count="20" id="statProducts">0</b><span>productos activos</span></li>
        <li><b>3</b><span>pasos y listo</span></li>
      </ul>
    </div>

    <div class="hero__stage" id="stage" aria-hidden="true">
      <div class="stage__ring"></div>
      <div class="tk tk--back">
        <div class="tk__top"><span>CINEMEX</span><span>SALA 7</span></div>
        <div class="tk__title">Combo Grande</div>
        <div class="tk__foot"><span>Dulcería</span><b>$110</b></div>
      </div>
      <div class="tk tk--mid">
        <div class="tk__top"><span>CINÉPOLIS</span><span>VIP</span></div>
        <div class="tk__title">Boleto VIP</div>
        <div class="tk__foot"><span>Lun – Vie</span><b>$132</b></div>
      </div>
      <div class="tk tk--front">
        <div class="tk__top"><span>TAQUILLAPP</span><span>ADMIT ONE</span></div>
        <img class="tk__icon" src="<?php echo esc_url( tq_asset( 'img/' ) ); ?>icon.png" alt="">
        <div class="tk__title">Tu función<br>de hoy</div>
        <div class="tk__code">
          <span>Código</span>
          <b id="heroCode">TQ-7K2M-9XA</b>
        </div>
        <div class="tk__barcode"></div>
      </div>
      <div class="chip chip--off">+50% OFF</div>
      <div class="chip chip--ok"><svg><use href="#i-check"/></svg> Código enviado</div>
      <div class="chip chip--time"><svg><use href="#i-bolt"/></svg> en 8 segundos</div>
    </div>
  </div>
</section>

<!-- ============ MARQUEE ============ -->
<div class="marquee" aria-hidden="true">
  <div class="marquee__track">
    <span>Boletos 2D</span><i></i><span>Combos de dulcería</span><i></i><span>Salas VIP</span><i></i><span>Platino</span><i></i><span>3D</span><i></i><span>IMAX · 4DX</span><i></i><span>Paquetes para dos</span><i></i><span>Más de 50% OFF</span><i></i>
    <span>Boletos 2D</span><i></i><span>Combos de dulcería</span><i></i><span>Salas VIP</span><i></i><span>Platino</span><i></i><span>3D</span><i></i><span>IMAX · 4DX</span><i></i><span>Paquetes para dos</span><i></i><span>Más de 50% OFF</span><i></i>
  </div>
</div>

<!-- ============ VALUE / BENTO (light) ============ -->
<section class="light" id="por-que">
  <div class="container">
    <div class="sec-head sec-head--split">
      <h2 class="display reveal">
        El cine que te gusta,
        <span class="pill-word">al precio</span>
        que <span class="grad-text">sí te late.</span>
      </h2>
      <p class="sec-head__aside reveal">No son cupones genéricos: son experiencias de cine seleccionadas para que vayas más seguido y gastes menos cada vez.</p>
    </div>

    <div class="bento">
      <article class="bento__card bento__card--feature reveal">
        <span class="tag">Ahorro real</span>
        <div class="bento__big"><span class="grad-text">+50%</span></div>
        <h3>Más de 50% de descuento en las mejores salas</h3>
        <p>VIP, Platino, IMAX, 4DX y más, a un precio que te deja vivir mejor tu experiencia en el cine.</p>
        <a href="#catalogo" class="round-link" aria-label="Ver catálogo"><svg><use href="#i-arrow"/></svg></a>
      </article>

      <article class="bento__card bento__card--grad reveal">
        <span class="tag tag--light">Rapidez</span>
        <h3>3 pasos.<br>Listo.</h3>
        <p>Elige, paga y recibe tu código por correo en segundos. Sin filas, sin vueltas.</p>
        <div class="bento__steps"><span>Elige</span><span>Paga</span><span>Canjea</span></div>
      </article>

      <article class="bento__card bento__card--dark reveal">
        <span class="tag tag--dark">Variedad</span>
        <h3>Todo el plan, en un solo lugar</h3>
        <div class="mini-chips">
          <span>Tradicional</span><span>3D</span><span>VIP</span><span>Platino</span><span>Premium</span><span>IMAX</span><span>4DX</span><span>Palomitas</span><span>Nachos</span><span>Hot dog</span>
        </div>
      </article>

      <article class="bento__card bento__card--outline reveal">
        <svg class="bento__icon"><use href="#i-shield"/></svg>
        <div><h3>Pago seguro</h3>
        <p>Tarjeta de crédito, débito o PayPal. Procesado por OpenPay y PayPal.</p></div>
      </article>

      <article class="bento__card bento__card--outline reveal">
        <svg class="bento__icon"><use href="#i-mail"/></svg>
        <div><h3>Código al instante</h3>
        <p>Llega a tu correo en segundos, listo para canjear en tu cine.</p></div>
      </article>
    </div>
  </div>
</section>

<!-- ============ CATALOG ============ -->
<section class="catalog" id="catalogo">
  <div class="container">
    <div class="sec-head">
      <span class="eyebrow"><span class="eyebrow__dot"></span>Catálogo</span>
      <h2 class="display reveal">Elige tu cine.<br><span class="grad-text">Arma tu función.</span></h2>
    </div>

    <div class="filters" role="toolbar" aria-label="Filtros del catálogo">
      <div class="seg" id="typeFilter">
        <button class="is-active" data-type="all">Todo</button>
        <button data-type="boleto">Boletos</button>
        <button data-type="combo">Combos</button>
        <button data-type="paquete">Paquetes</button>
      </div>
      <div class="seg seg--chain" id="chainFilter">
        <button class="is-active" data-chain="all">Ambas cadenas</button>
        <button data-chain="cinepolis">Cinépolis</button>
        <button data-chain="cinemex">Cinemex</button>
      </div>
      <div class="filters__right">
        <label class="search">
          <svg><use href="#i-search"/></svg>
          <input id="searchInput" type="search" placeholder="Buscar VIP, nachos, 3D…" aria-label="Buscar en el catálogo">
        </label>
        <select id="sortSelect" class="select" aria-label="Ordenar">
          <option value="featured">Destacados</option>
          <option value="price-asc">Precio: menor a mayor</option>
          <option value="price-desc">Precio: mayor a menor</option>
        </select>
      </div>
    </div>

    <p class="catalog__count" id="catalogCount"></p>
    <div class="grid" id="productGrid"></div>
    <p class="catalog__note">Precios “desde” en MXN, IVA incluido. Pueden variar según complejo y disponibilidad.</p>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="how" id="como-funciona">
  <div class="container">
    <div class="sec-head sec-head--center">
      <span class="eyebrow"><span class="eyebrow__dot"></span>Cómo funciona</span>
      <h2 class="display reveal">De tu celular <span class="pill-word pill-word--dark">a la sala</span><br>en <span class="grad-text">3 pasos.</span></h2>
    </div>

    <ol class="steps">
      <li class="step reveal">
        <span class="step__num">01</span>
        <div class="step__visual step__visual--pick">
          <span>Cinépolis</span><span class="is-on">Cinemex</span>
        </div>
        <h3>Elige</h3>
        <p>Escoge cadena, tipo de sala y si quieres combo. Ves el precio final desde el inicio.</p>
      </li>
      <li class="step reveal">
        <span class="step__num">02</span>
        <div class="step__visual step__visual--pay">
          <div class="paybar"><i></i></div>
          <small>Pago confirmado</small>
        </div>
        <h3>Paga</h3>
        <p>Con tarjeta de crédito, débito o PayPal. Pago protegido de principio a fin.</p>
      </li>
      <li class="step reveal">
        <span class="step__num">03</span>
        <div class="step__visual step__visual--code">
          <b>TQ-4HF2-8PL</b>
        </div>
        <h3>Canjea</h3>
        <p>Recibe tu código oficial del cine y úsalo en su web o app, en taquilla o en dulcería.</p>
      </li>
    </ol>
  </div>
</section>

<!-- ============ ARC CTA ============ -->
<section class="arc">
  <div class="arc__cards" aria-hidden="true">
    <div class="arc__card"><svg><use href="#i-popcorn"/></svg></div>
    <div class="arc__card arc__card--g"><svg><use href="#i-ticket"/></svg></div>
    <div class="arc__card"><svg><use href="#i-glasses"/></svg></div>
    <div class="arc__card arc__card--g"><img src="<?php echo esc_url( tq_asset( 'img/' ) ); ?>icon.png" alt=""></div>
    <div class="arc__card"><svg><use href="#i-crown"/></svg></div>
    <div class="arc__card arc__card--g"><svg><use href="#i-nachos"/></svg></div>
    <div class="arc__card"><svg><use href="#i-duo"/></svg></div>
  </div>
  <div class="container arc__copy">
    <h2 class="display reveal">Tu próxima función<br>no tiene que <span class="pill-word">costar</span> <span class="grad-text">de más.</span></h2>
    <p class="reveal">Más estrenos, más palomitas, más planes con quien quieras. El ahorro se nota desde la primera compra.</p>
    <a href="#catalogo" class="btn btn--grad reveal">Ver catálogo completo <svg><use href="#i-arrow"/></svg></a>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="faq" id="faq">
  <div class="container faq__inner">
    <div class="faq__head">
      <span class="eyebrow"><span class="eyebrow__dot"></span>Preguntas frecuentes</span>
      <h2 class="display reveal">Todo claro<br><span class="grad-text">antes de la función.</span></h2>
      <p>¿Algo más? Escríbenos a <a href="mailto:<?php echo esc_attr( tq_contact_email() ); ?>"><?php echo esc_html( tq_contact_email() ); ?></a> y te respondemos rápido.</p>
    </div>
    <div class="faq__list" id="faqList">
      <details class="qa" open>
        <summary>¿Cómo recibo mi código?</summary>
        <p>En cuanto se confirma tu pago, te enviamos el código oficial del cine al correo que registraste y también lo ves en pantalla.</p>
      </details>
      <details class="qa">
        <summary>¿Dónde lo canjeo?</summary>
        <p>Los boletos se canjean en la web o app oficial de tu cine, o directamente en taquilla. Los combos se canjean en dulcería, presentando tu código.</p>
      </details>
      <details class="qa">
        <summary>¿Cuánto tiempo es válido?</summary>
        <p>Tienes 3 meses desde la fecha de compra. La vigencia exacta aparece en el detalle de cada producto y en tu correo de confirmación.</p>
      </details>
      <details class="qa">
        <summary>¿Aplica en IMAX, 4DX o salas VIP?</summary>
        <p>Depende del producto. Cada boleto indica en qué salas y formatos es válido; para IMAX, 4DX y Macro XE, elige “Formatos Diferenciados”.</p>
      </details>
      <details class="qa">
        <summary>¿Qué métodos de pago aceptan?</summary>
        <p>Tarjeta de crédito o débito y PayPal.</p>
      </details>
      <details class="qa">
        <summary>¿TaquillApp es parte de los cines?</summary>
        <p>No. Somos una plataforma independiente que te ofrece precios preferenciales en cadenas participantes. Los cines operan las salas y el canje.</p>
      </details>
    </div>
  </div>
</section>

<!-- ============ NEWSLETTER ============ -->
<section class="news">
  <div class="container">
    <div class="news__card reveal">
      <img class="news__icon" src="<?php echo esc_url( tq_asset( 'img/' ) ); ?>icon.png" alt="" aria-hidden="true">
      <div class="news__copy">
        <h2>Los descuentos de la semana, directo a tu correo.</h2>
        <p>Estrenos, combos nuevos y precios especiales antes que nadie.</p>
      </div>
      <form class="news__form" id="newsForm">
        <input type="email" required placeholder="tu@correo.com" aria-label="Correo electrónico">
        <button class="btn btn--dark" type="submit">Suscribirme</button>
      </form>
    </div>
  </div>
</section>

</main>

<?php get_footer(); ?>
