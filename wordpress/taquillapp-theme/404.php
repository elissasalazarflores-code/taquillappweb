<?php
get_header();
?>
<main id="top" class="tq-page">
	<div class="container tq-page__inner">
		<span class="eyebrow"><span class="eyebrow__dot"></span>Error 404</span>
		<h1 class="display tq-page__title">Esta función <span class="grad-text">no existe.</span></h1>
		<p class="tq-page__content">La página que buscas no está disponible.</p>
		<a class="btn btn--grad" href="<?php echo esc_url( home_url( '/#catalogo' ) ); ?>">Ir al catálogo</a>
	</div>
</main>
<?php
get_footer();
