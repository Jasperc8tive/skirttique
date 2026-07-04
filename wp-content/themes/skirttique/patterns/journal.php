<?php
/**
 * Title: Journal
 * Slug: skirttique/journal
 * Categories: skirttique
 * Inserter: no
 *
 * The journal index (Stage 23) — serves the posts page (home.html) and
 * category archives (category.html) from the main query: editorial
 * head, category filter row, article cards, pagination.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

global $wp_query;

$st_heading = __( 'Notes from the house', 'skirttique' );
$st_lede    = __( 'Styling, care, and the thinking behind the pieces — a few letters a season.', 'skirttique' );
$st_current = 0;

if ( is_category() ) {
	$st_term    = get_queried_object();
	$st_heading = $st_term->name;
	$st_lede    = trim( (string) $st_term->description );
	$st_current = (int) $st_term->term_id;
}

$st_journal_url = (string) get_permalink( (int) get_option( 'page_for_posts' ) );

$st_categories = get_terms(
	array(
		'taxonomy'   => 'category',
		'hide_empty' => true,
		'orderby'    => 'name',
	)
);
if ( is_wp_error( $st_categories ) ) {
	$st_categories = array();
}
?>

<div class="st-journal">
	<header class="st-journal__head">
		<p class="st-section__eyebrow"><?php esc_html_e( 'The journal', 'skirttique' ); ?></p>
		<h1 class="st-journal__title"><?php echo esc_html( $st_heading ); ?></h1>
		<?php if ( '' !== $st_lede ) : ?>
			<p class="st-journal__lede"><?php echo esc_html( $st_lede ); ?></p>
		<?php endif; ?>

		<?php if ( count( $st_categories ) > 1 || $st_current ) : ?>
			<nav class="st-journal__cats" aria-label="<?php esc_attr_e( 'Journal categories', 'skirttique' ); ?>">
				<ul>
					<li><a href="<?php echo esc_url( $st_journal_url ); ?>" <?php echo $st_current ? '' : 'aria-current="page"'; ?>><?php esc_html_e( 'Everything', 'skirttique' ); ?></a></li>
					<?php foreach ( $st_categories as $st_cat ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) get_term_link( $st_cat ) ); ?>" <?php echo $st_cat->term_id === $st_current ? 'aria-current="page"' : ''; ?>>
								<?php echo esc_html( $st_cat->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="st-journal__grid">
			<?php
			$st_i = 0;
			while ( have_posts() ) :
				the_post();
				$st_post_cats = get_the_category();
				?>
				<article class="st-drape <?php echo esc_attr( Skirttique\Components\drape_delay( $st_i ) ); ?>">
					<div class="st-jcard">
						<a class="st-jcard__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'large', array( 'loading' => $st_i < 2 ? 'eager' : 'lazy' ) ); ?>
							<?php endif; ?>
						</a>
						<div class="st-jcard__meta">
							<?php if ( $st_post_cats ) : ?>
								<p class="st-jcard__kicker"><?php echo esc_html( $st_post_cats[0]->name ); ?></p>
							<?php endif; ?>
							<h2 class="st-jcard__name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p class="st-jcard__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
							<time class="st-jcard__date" datetime="<?php echo esc_attr( (string) get_the_date( 'c' ) ); ?>"><?php echo esc_html( (string) get_the_date() ); ?></time>
						</div>
					</div>
				</article>
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
			<nav class="st-journal__pages" aria-label="<?php esc_attr_e( 'Journal pages', 'skirttique' ); ?>">
				<?php echo $st_pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core paginate_links(). ?>
			</nav>
		<?php endif; ?>

	<?php else : ?>
		<p class="st-journal__empty"><?php esc_html_e( 'The first letters are being written.', 'skirttique' ); ?></p>
	<?php endif; ?>
</div>
