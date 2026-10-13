<?php
/**
 * Páginas internas (términos, aviso de privacidad, reembolsos, etc.).
 */

get_header();
?>
<main id="top" class="tq-page">
	<div class="container tq-page__inner">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<span class="eyebrow"><span class="eyebrow__dot"></span>TaquillApp</span>
			<h1 class="display tq-page__title"><?php the_title(); ?></h1>
			<div class="tq-page__content"><?php the_content(); ?></div>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
