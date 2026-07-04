<?php
/**
 * Title: Lookbooks
 * Slug: skirttique/lookbooks
 * Categories: skirttique
 * Inserter: no
 *
 * The lookbook archive (Stage 23) — immersive: a quiet head, then each
 * lookbook as a full-width cover with the title resting on it.
 *
 * @package Skirttique
 */

declare( strict_types=1 );
?>

<div class="st-lookbooks">
	<header class="st-journal__head">
		<p class="st-section__eyebrow"><?php esc_html_e( 'The lookbooks', 'skirttique' ); ?></p>
		<h1 class="st-journal__title"><?php esc_html_e( 'Photographed where it moves', 'skirttique' ); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="st-lookbooks__list">
			<?php
			$st_i = 0;
			while ( have_posts() ) :
				the_post();
				?>
				<div class="st-drape <?php echo esc_attr( Skirttique\Components\drape_delay( $st_i ) ); ?>">
					<a class="st-lookcover" href="<?php the_permalink(); ?>">
						<span class="st-lookcover__media">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'woocommerce_single', array( 'loading' => $st_i < 1 ? 'eager' : 'lazy' ) ); ?>
							<?php endif; ?>
						</span>
						<span class="st-lookcover__content">
							<?php if ( has_excerpt() ) : ?>
								<span class="st-lookcover__eyebrow"><?php echo esc_html( get_the_excerpt() ); ?></span>
							<?php endif; ?>
							<span class="st-lookcover__title"><?php the_title(); ?></span>
						</span>
					</a>
				</div>
				<?php
				++$st_i;
			endwhile;
			?>
		</div>

		<?php
		$st_pages = paginate_links(
			array(
				'type'      => 'list',
				'prev_text' => __( 'Previous', 'skirttique' ),
				'next_text' => __( 'Next', 'skirttique' ),
			)
		);
		if ( $st_pages ) :
			?>
			<nav class="st-journal__pages" aria-label="<?php esc_attr_e( 'Lookbook pages', 'skirttique' ); ?>">
				<?php echo $st_pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core paginate_links(). ?>
			</nav>
		<?php endif; ?>

	<?php else : ?>
		<p class="st-journal__empty"><?php esc_html_e( 'The first story is being photographed.', 'skirttique' ); ?></p>
	<?php endif; ?>
</div>
