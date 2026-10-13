<?php
/**
 * Plantilla de respaldo (blog, archivos, búsquedas y 404).
 */

get_header();
?>
<main id="top" class="tq-page">
	<div class="container tq-page__inner">
		<?php if ( have_posts() ) : ?>
			<?php
			while ( have_posts() ) :
				the_post();
				?>
				<article class="tq-page__entry">
					<h2 class="tq-page__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<div class="tq-page__content"><?php is_singular() ? the_content() : the_excerpt(); ?></div>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<h1 class="display tq-page__title">No encontramos esta página.</h1>
			<p class="tq-page__content">Vuelve al <a href="<?php echo esc_url( home_url( '/#catalogo' ) ); ?>">catálogo</a>.</p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
