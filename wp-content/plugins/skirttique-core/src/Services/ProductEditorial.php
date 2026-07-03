<?php
/**
 * Product editorial meta — fabric, care, story.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Stage 21: the per-piece editorial fields the PDP panels read —
 * fabric & composition, care, and the piece's story. Edited where
 * products are edited (Products → edit → Skirttique tab); the theme
 * reads the meta keys directly (mirroring the CollectionMeta pattern)
 * and falls back to shipped copy where safe.
 */
final class ProductEditorial implements ServiceInterface {

	public const FABRIC_KEY = '_st_fabric';
	public const CARE_KEY   = '_st_care';
	public const STORY_KEY  = '_st_story';

	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save' ) );
	}

	/**
	 * The "Skirttique" tab on the product-data box.
	 *
	 * @param array<string, array<string, mixed>> $tabs Product data tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public function tab( array $tabs ): array {
		$tabs['skirttique'] = array(
			'label'    => __( 'Skirttique', 'skirttique-core' ),
			'target'   => 'st_product_editorial',
			'class'    => array(),
			'priority' => 65,
		);

		return $tabs;
	}

	/**
	 * The tab's fields.
	 */
	public function panel(): void {
		echo '<div id="st_product_editorial" class="panel woocommerce_options_panel hidden">';

		woocommerce_wp_textarea_input(
			array(
				'id'          => self::FABRIC_KEY,
				'label'       => __( 'Fabric & composition', 'skirttique-core' ),
				'description' => __( 'e.g. “100% silk crepe, lined in cotton voile.” Blank hides the panel.', 'skirttique-core' ),
				'desc_tip'    => true,
			)
		);

		woocommerce_wp_textarea_input(
			array(
				'id'          => self::CARE_KEY,
				'label'       => __( 'Care', 'skirttique-core' ),
				'description' => __( 'Blank falls back to the house care note.', 'skirttique-core' ),
				'desc_tip'    => true,
			)
		);

		woocommerce_wp_textarea_input(
			array(
				'id'          => self::STORY_KEY,
				'label'       => __( 'The story', 'skirttique-core' ),
				'description' => __( 'Where the piece came from — shown as its own panel. Blank hides it.', 'skirttique-core' ),
				'desc_tip'    => true,
			)
		);

		echo '</div>';
	}

	/**
	 * Persist the three fields (sanitised, empty deletes).
	 *
	 * @param \WC_Product $product The product being saved.
	 */
	public function save( \WC_Product $product ): void {
		foreach ( array( self::FABRIC_KEY, self::CARE_KEY, self::STORY_KEY ) as $key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product-save nonce before this hook fires.
			$value = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';

			if ( '' === $value ) {
				$product->delete_meta_data( $key );
			} else {
				$product->update_meta_data( $key, $value );
			}
		}
	}
}
