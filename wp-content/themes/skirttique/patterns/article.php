<?php
/**
 * Title: Article
 * Slug: skirttique/article
 * Categories: skirttique
 * Inserter: no
 *
 * A journal article (Stage 23): kicker, serif title, date, wide cover,
 * measured prose (the st-page voice), related letters from the same
 * category, and the way back to the journal.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_post = get_post();
if ( ! $st_post ) {
	return;
}

$st_cats        = get_the_category( $st_post->ID );
$st_journal_url = (string) get_permalink( (int) get_option( 'page_for_posts' ) );

// Related: newest from the same category, never this article.
$st_related = $st_cats
	? get_posts(
		array(
			'category'    => $st_cats[0]->term_id,
			'numberposts' => 2,
			'exclude'     => array( $st_post->ID ),
			'post_status' => 'publish',
		)
	)
	: array();
?>

<article class="st-article">
	<header class="st-article__head">
		<?php if ( $st_cats ) : ?>
			<p class="st-article__kicker">
				<a href="<?php echo esc_url( (string) get_term_link( $st_cats[0] ) ); ?>"><?php echo esc_html( $st_cats[0]->name ); ?></a>
			</p>
		<?php endif; ?>
		<h1 class="st-article__title"><?php echo esc_html( get_the_title( $st_post ) ); ?></h1>
		<time class="st-article__date" datetime="<?php echo esc_attr( (string) get_the_date( 'c', $st_post ) ); ?>"><?php echo esc_html( (string) get_the_date( '', $st_post ) ); ?></time>
	</header>

	<?php if ( has_post_thumbnail( $st_post ) ) : ?>
		<figure class="st-article__cover st-drape"><div>
			<?php echo get_the_post_thumbnail( $st_post, 'woocommerce_single', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
		</div></figure>
	<?php endif; ?>

	<div class="st-article__body">
		<?php echo apply_filters( 'the_content', $st_post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core content pipeline. ?>
	</div>

	<footer class="st-article__foot">
		<a class="st-hemline" href="<?php echo esc_url( $st_journal_url ); ?>"><?php esc_html_e( 'Back to the journal', 'skirttique' ); ?></a>
	</footer>
</article>

<?php if ( $st_related ) : ?>
	<section class="st-article__related" aria-labelledby="st-related-title">
		<div class="st-section__head">
			<p class="st-section__eyebrow"><?php esc_html_e( 'Keep reading', 'skirttique' ); ?></p>
			<h2 class="st-section__title st-article__related-title" id="st-related-title"><?php esc_html_e( 'More like this', 'skirttique' ); ?></h2>
		</div>
		<div class="st-journal__grid st-journal__grid--related">
			<?php foreach ( $st_related as $st_rel ) : ?>
				<article class="st-jcard">
					<a class="st-jcard__media" href="<?php echo esc_url( (string) get_permalink( $st_rel ) ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo get_the_post_thumbnail( $st_rel, 'large', array( 'loading' => 'lazy' ) ); ?>
					</a>
					<div class="st-jcard__meta">
						<h3 class="st-jcard__name"><a href="<?php echo esc_url( (string) get_permalink( $st_rel ) ); ?>"><?php echo esc_html( get_the_title( $st_rel ) ); ?></a></h3>
						<time class="st-jcard__date" datetime="<?php echo esc_attr( (string) get_the_date( 'c', $st_rel ) ); ?>"><?php echo esc_html( (string) get_the_date( '', $st_rel ) ); ?></time>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
<?php endif; ?>
