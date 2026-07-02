<?php
/**
 * Wishlist service.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Wishlist — guests keep theirs in localStorage (client-side only, no
 * endpoints touched); logged-in customers persist to user meta through
 * nonce-verified wc-ajax endpoints. On login the client merges its
 * local list up and clears localStorage.
 *
 * The nonce action is shared with the theme, which prints it into
 * `window.stConfig.wishlistNonce` (see the theme's inc/assets.php).
 */
final class Wishlist implements ServiceInterface {

	public const META_KEY     = 'skirttique_wishlist';
	public const NONCE_ACTION = 'skirttique_wishlist';
	private const MAX_ITEMS   = 100;

	public function register(): void {
		add_action( 'wc_ajax_skirttique_wishlist_get', array( $this, 'get' ) );
		add_action( 'wc_ajax_skirttique_wishlist_toggle', array( $this, 'toggle' ) );
		add_action( 'wc_ajax_skirttique_wishlist_merge', array( $this, 'merge' ) );
	}

	/**
	 * Current user's saved product ids (empty for guests — theirs live
	 * client-side).
	 */
	public function get(): void {
		wp_send_json(
			array(
				'logged_in' => is_user_logged_in(),
				'ids'       => self::ids(),
			)
		);
	}

	/**
	 * Toggle one product in the logged-in user's list.
	 */
	public function toggle(): void {
		$this->guard();

		$id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		if ( ! $id || ! wc_get_product( $id ) ) {
			wp_send_json( array( 'error' => true ), 400 );
		}

		$ids   = self::ids();
		$index = array_search( $id, $ids, true );
		$saved = false === $index;

		if ( $saved ) {
			array_unshift( $ids, $id );
			$ids = array_slice( $ids, 0, self::MAX_ITEMS );
		} else {
			unset( $ids[ $index ] );
		}

		update_user_meta( get_current_user_id(), self::META_KEY, array_values( $ids ) );

		wp_send_json(
			array(
				'saved' => $saved,
				'ids'   => array_values( $ids ),
			)
		);
	}

	/**
	 * Merge a guest localStorage list into the account list on login.
	 */
	public function merge(): void {
		$this->guard();

		$incoming = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- absint per item.
		$incoming = array_filter( $incoming, static fn ( int $id ): bool => (bool) wc_get_product( $id ) );

		$ids = array_values( array_unique( array_merge( self::ids(), $incoming ) ) );
		$ids = array_slice( $ids, 0, self::MAX_ITEMS );

		update_user_meta( get_current_user_id(), self::META_KEY, $ids );

		wp_send_json( array( 'ids' => $ids ) );
	}

	/**
	 * The stored list for the current user.
	 *
	 * @return list<int>
	 */
	public static function ids(): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$meta = get_user_meta( get_current_user_id(), self::META_KEY, true );

		return is_array( $meta ) ? array_values( array_map( 'absint', $meta ) ) : array();
	}

	/**
	 * Writes require a logged-in user and a valid nonce.
	 */
	private function guard(): void {
		if ( ! is_user_logged_in() ) {
			wp_send_json( array( 'error' => true ), 401 );
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}
}
