<?php
/**
 * Collection (product_cat) editorial meta.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * The fields that turn a product category into an editorial collection
 * landing page (Stage 20 renders them): a wide hero image (distinct
 * from the card thumbnail WooCommerce already provides) and the
 * collection story. Edited where collections are edited — Products →
 * Categories — reusing the House Settings media-picker script.
 */
final class CollectionMeta implements ServiceInterface {

	public const STORY_KEY = 'st_collection_story';
	public const HERO_KEY  = 'st_collection_hero_id';

	public function register(): void {
		add_action( 'product_cat_edit_form_fields', array( $this, 'fields' ) );
		add_action( 'edited_product_cat', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * The media picker, on the term edit screen only.
	 */
	public function assets( string $hook ): void {
		if ( 'term.php' !== $hook || 'product_cat' !== ( $_GET['taxonomy'] ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen detection only.
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'skirttique-house-settings',
			plugins_url( 'assets/house-settings.js', SKIRTTIQUE_CORE_FILE ),
			array(),
			SKIRTTIQUE_CORE_VERSION,
			array( 'in_footer' => true )
		);
	}

	/**
	 * Render the fields on the Edit Collection screen.
	 *
	 * @param \WP_Term $term The term being edited.
	 */
	public function fields( \WP_Term $term ): void {
		$story   = (string) get_term_meta( $term->term_id, self::STORY_KEY, true );
		$hero_id = absint( get_term_meta( $term->term_id, self::HERO_KEY, true ) );
		$src     = $hero_id ? (string) wp_get_attachment_image_url( $hero_id, 'medium' ) : '';

		wp_nonce_field( 'st_collection_meta', 'st_collection_nonce' );
		?>
		<tr class="form-field">
			<th scope="row"><label for="st-collection-story"><?php esc_html_e( 'Collection story', 'skirttique-core' ); ?></label></th>
			<td>
				<textarea name="<?php echo esc_attr( self::STORY_KEY ); ?>" id="st-collection-story" rows="5" class="large-text"><?php echo esc_textarea( $story ); ?></textarea>
				<p class="description"><?php esc_html_e( 'The editorial introduction on the collection landing page. Blank falls back to the collection description.', 'skirttique-core' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Landing hero image', 'skirttique-core' ); ?></th>
			<td>
				<div class="st-media-field">
					<input type="hidden" name="<?php echo esc_attr( self::HERO_KEY ); ?>" value="<?php echo esc_attr( $hero_id ? (string) $hero_id : '' ); ?>" data-st-media-input>
					<img src="<?php echo esc_url( $src ); ?>" alt="" style="max-width:140px;height:auto;<?php echo $src ? '' : 'display:none;'; ?>" data-st-media-preview>
					<p>
						<button type="button" class="button" data-st-media-pick><?php esc_html_e( 'Choose image', 'skirttique-core' ); ?></button>
						<button type="button" class="button" data-st-media-clear style="<?php echo $hero_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'skirttique-core' ); ?></button>
					</p>
				</div>
				<p class="description"><?php esc_html_e( 'Wide editorial hero for the landing page (the Thumbnail above stays the card image).', 'skirttique-core' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Persist on term save (nonce + capability checked).
	 *
	 * @param int $term_id The saved term.
	 */
	public function save( int $term_id ): void {
		if (
			! isset( $_POST['st_collection_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['st_collection_nonce'] ) ), 'st_collection_meta' )
			|| ! current_user_can( 'manage_product_terms' )
		) {
			return;
		}

		if ( isset( $_POST[ self::STORY_KEY ] ) ) {
			update_term_meta( $term_id, self::STORY_KEY, sanitize_textarea_field( wp_unslash( $_POST[ self::STORY_KEY ] ) ) );
		}

		if ( isset( $_POST[ self::HERO_KEY ] ) ) {
			$id = absint( $_POST[ self::HERO_KEY ] );
			if ( $id && wp_attachment_is_image( $id ) ) {
				update_term_meta( $term_id, self::HERO_KEY, $id );
			} else {
				delete_term_meta( $term_id, self::HERO_KEY );
			}
		}
	}
}
