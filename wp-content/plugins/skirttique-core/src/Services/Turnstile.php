<?php
/**
 * Cloudflare Turnstile — invisible spam protection for the public forms.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

/**
 * A privacy-friendly, no-friction CAPTCHA for the newsletter, contact and
 * bespoke forms. Token-gated exactly like the payment gateways and the
 * Instagram feed: with no keys the field renders nothing and verification
 * passes through, so the forms behave exactly as before (nonce + honeypot)
 * until the owner connects Cloudflare — the store never dead-ends on a
 * half-configured integration.
 *
 * Keys live in House Settings → Integrations; the secret may instead be
 * defined as the SKIRTTIQUE_TURNSTILE_SECRET constant (out of the
 * database, e.g. in wp-config.php) for the stricter deployments.
 *
 * A static utility, not a registered service — the forms call field() and
 * the handlers call verify(); there are no hooks of its own to wire.
 */
final class Turnstile {

	private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	private const API_JS     = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

	/**
	 * The public site key (safe to print).
	 */
	public static function site_key(): string {
		return (string) apply_filters( 'skirttique_turnstile_site_key', HouseContent::value( 'turnstile_site_key' ) );
	}

	/**
	 * The secret key — constant first (kept out of the database), then the
	 * House Settings value.
	 */
	public static function secret_key(): string {
		$key = defined( 'SKIRTTIQUE_TURNSTILE_SECRET' ) && '' !== (string) SKIRTTIQUE_TURNSTILE_SECRET
			? (string) SKIRTTIQUE_TURNSTILE_SECRET
			: HouseContent::value( 'turnstile_secret_key' );

		return (string) apply_filters( 'skirttique_turnstile_secret_key', $key );
	}

	/**
	 * Whether both keys are present. When false the integration is inert.
	 */
	public static function is_configured(): bool {
		return '' !== self::site_key() && '' !== self::secret_key();
	}

	/**
	 * The widget markup (empty when unconfigured). Enqueues the Cloudflare
	 * script the first time a form asks for it; WordPress dedupes the
	 * handle, so several forms on a page still load it once.
	 */
	public static function field(): string {
		if ( ! self::is_configured() ) {
			return '';
		}

		wp_enqueue_script( 'cloudflare-turnstile', self::API_JS, array(), null, array( 'strategy' => 'defer', 'in_footer' => true ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- third-party endpoint, unversioned by design.

		return '<div class="st-turnstile cf-turnstile" data-sitekey="' . esc_attr( self::site_key() ) . '" data-theme="light"></div>';
	}

	/**
	 * Validate the submitted token against Cloudflare's siteverify.
	 *
	 * Soft by design: passes through when unconfigured. Fails closed on a
	 * missing or rejected token (a bot), but fails OPEN on a network/API
	 * error — a Cloudflare outage must never lock real clients out of the
	 * contact or bespoke forms.
	 */
	public static function verify(): bool {
		if ( ! self::is_configured() ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the caller verifies the form nonce before reaching here.
		$token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		if ( '' === $token ) {
			return false;
		}

		$response = wp_remote_post(
			self::VERIFY_URL,
			array(
				'timeout' => 8,
				'body'    => array(
					'secret'   => self::secret_key(),
					'response' => $token,
					'remoteip' => self::remote_ip(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return true;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) && ! empty( $body['success'] );
	}

	/**
	 * The client IP, when it is a valid address (optional siteverify hint).
	 */
	private static function remote_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}
}
