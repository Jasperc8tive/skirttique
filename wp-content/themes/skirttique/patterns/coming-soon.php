<?php
/**
 * Title: Coming Soon
 * Slug: skirttique/coming-soon
 * Categories: skirttique
 * Inserter: no
 *
 * The pre-launch splash (Stage 25), rendered by templates/coming-soon.html
 * whenever WooCommerce's native site-visibility mode is set to Coming
 * soon (WooCommerce → Settings → Site visibility). The switch stays in
 * Woo's screen — the theme only provides the dress: the logotype, one
 * line, the house list, and the social profiles.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

/** This filter is documented in patterns/footer.php. */
$st_social = apply_filters(
	'skirttique_social_links',
	array(
		'Instagram' => '#',
		'TikTok'    => '#',
		'Pinterest' => '#',
	)
);
$st_social = array_filter( $st_social, static fn ( string $st_url ): bool => '' !== $st_url && '#' !== $st_url );
?>

<div class="st-coming">
	<p class="st-coming__logotype"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
	<h1 class="st-coming__statement"><?php esc_html_e( 'The house is dressing.', 'skirttique' ); ?></h1>
	<p class="st-coming__sub"><?php esc_html_e( 'Midi and maxi skirts, cut in Lagos and worn everywhere — the doors open soon. Leave your address and be the first through them.', 'skirttique' ); ?></p>

	<?php
	echo Skirttique\Components\newsletter( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped within the component.
		array(
			'context' => 'footer',
			'eyebrow' => __( 'The house list', 'skirttique' ),
			'title'   => __( 'First through the doors', 'skirttique' ),
			'promise' => __( 'One letter when we open, then a few a season — nothing more.', 'skirttique' ),
		)
	);
	?>

	<?php if ( $st_social ) : ?>
		<ul class="st-coming__social">
			<?php foreach ( $st_social as $st_label => $st_url ) : ?>
				<li><a class="st-hemline" href="<?php echo esc_url( $st_url ); ?>" rel="noopener"><?php echo esc_html( $st_label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
