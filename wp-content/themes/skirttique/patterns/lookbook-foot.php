<?php
/**
 * Title: Lookbook foot
 * Slug: skirttique/lookbook-foot
 * Categories: skirttique
 * Inserter: no
 *
 * The way back from a lookbook to the archive.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_archive = get_post_type_archive_link( 'lookbook' );
if ( ! $st_archive ) {
	return;
}
?>

<nav class="st-lookfoot" aria-label="<?php esc_attr_e( 'Lookbooks', 'skirttique' ); ?>">
	<a class="st-hemline" href="<?php echo esc_url( $st_archive ); ?>"><?php esc_html_e( 'All lookbooks', 'skirttique' ); ?></a>
</nav>
