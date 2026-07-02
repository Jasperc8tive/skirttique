<?php
/**
 * Newsletter service — the house list.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * The house list — footer newsletter capture (Stage 5).
 *
 * Joins are stored locally in an option so no address is lost before the
 * marketing platform (Mailchimp/Klaviyo) is connected in the integrations
 * stage; `skirttique_newsletter_joined` is that integration's hand-off
 * point. Progressive: plain POST + redirect, no JavaScript required.
 */
final class Newsletter implements ServiceInterface {

	public const ACTION = 'skirttique_join';
	public const OPTION = 'skirttique_house_list';

	/**
	 * Local storage is a safety net, not a CRM — cap it.
	 */
	private const MAX_STORED = 5000;

	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Validate the join and bounce back to the form with a state flag.
	 */
	public function handle(): void {
		// Path-only redirect target: survives reverse proxies (dev proxy,
		// CDN) where the browser origin differs from the site host —
		// wp_safe_redirect would reject the absolute foreign-origin URL.
		$back = '/';
		$ref  = wp_get_referer();
		if ( $ref ) {
			$parts = wp_parse_url( $ref );
			$back  = ( $parts['path'] ?? '/' ) . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
			$back  = remove_query_arg( array( 'st-joined', 'st-join-error' ), $back );
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->back_to_form( add_query_arg( 'st-join-error', 'expired', $back ) );
		}

		// Honeypot — visitors never see this field; pretend success to bots.
		if ( '' !== trim( isset( $_POST['st_website'] ) ? (string) wp_unslash( $_POST['st_website'] ) : '' ) ) {
			$this->back_to_form( add_query_arg( 'st-joined', '1', $back ) );
		}

		$email = sanitize_email( isset( $_POST['st_email'] ) ? wp_unslash( $_POST['st_email'] ) : '' );
		if ( ! is_email( $email ) ) {
			$this->back_to_form( add_query_arg( 'st-join-error', 'email', $back ) );
		}

		$list = (array) get_option( self::OPTION, array() );
		if ( ! isset( $list[ $email ] ) && count( $list ) < self::MAX_STORED ) {
			$list[ $email ] = time();
			update_option( self::OPTION, $list, false );
		}

		/**
		 * Fires after an address joins the house list.
		 *
		 * The marketing platform integration subscribes here — local
		 * storage above is only the pre-integration safety net.
		 *
		 * @param string $email The joined address.
		 */
		do_action( 'skirttique_newsletter_joined', $email );

		$this->back_to_form( add_query_arg( 'st-joined', '1', $back ) );
	}

	/**
	 * Redirect to the newsletter section of the referring page.
	 */
	private function back_to_form( string $url ): never {
		wp_safe_redirect( $url . '#st-house-list' );
		exit;
	}
}
