<?php
/**
 * Instagram feed — the house, worn.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Stage 26: the live Instagram strip. Custom-light by decision (Phase 2
 * kickoff) — a server-side cached fetch of the official Instagram Graph
 * API, NOT a third-party widget: no client token exposure, no render-
 * blocking script, no layout shift (the tiles are server-rendered into
 * the existing `instagram()` component through its
 * `skirttique_instagram_media` filter).
 *
 * Token-gated exactly like the payment gateways: with no token the
 * filter returns nothing and the component keeps its shipped placeholder
 * tiles, so the section always renders. The owner pastes a long-lived
 * Instagram access token into House Settings → Integrations; the store
 * caches the result for an hour and keeps the last good response so an
 * expired token degrades to the most recent feed rather than a blank.
 */
final class InstagramFeed implements ServiceInterface {

	/** Transient holding the current cached feed. */
	private const CACHE = 'skirttique_ig_feed';

	/** Option holding the last successful fetch (survives an expired token). */
	private const LAST_GOOD = 'skirttique_ig_feed_last';

	private const TTL   = HOUR_IN_SECONDS;
	private const LIMIT = 6;

	/** Graph API media endpoint (Instagram Login / Graph API). */
	private const ENDPOINT = 'https://graph.instagram.com/me/media';

	public function register(): void {
		add_filter( 'skirttique_instagram_media', array( $this, 'media' ), 10, 2 );

		// A House Settings save may change the token — drop the cache so the
		// next view re-fetches rather than serving another hour of stale.
		add_action( 'update_option_' . HouseContent::OPTION, array( $this, 'flush' ) );
	}

	/**
	 * Supply cached live media to the Instagram component.
	 *
	 * @param array<int, array{url: string, permalink: string}> $media Existing (empty) media.
	 * @return array<int, array{url: string, permalink: string}>
	 */
	public function media( array $media, array $args = array() ): array {
		$token = HouseContent::value( 'instagram_token' );
		if ( '' === $token ) {
			return $media;
		}

		$cached = get_transient( self::CACHE );
		if ( is_array( $cached ) ) {
			return $cached ?: $media; // Cached empty = a known-bad token; fall through to placeholders.
		}

		$fetched = $this->fetch( $token );

		if ( null === $fetched ) {
			// The request failed (network, rate limit, expired token). Serve
			// the last good feed if we have one; never hard-fail the section.
			$last = get_option( self::LAST_GOOD, array() );

			return is_array( $last ) && $last ? $last : $media;
		}

		set_transient( self::CACHE, $fetched, self::TTL );
		if ( $fetched ) {
			update_option( self::LAST_GOOD, $fetched, false );
		}

		return $fetched ?: $media;
	}

	/**
	 * Fetch and normalise the feed. Returns a tile list on success (which
	 * may be empty if the account has no shareable images), or null when
	 * the request itself failed.
	 *
	 * @return array<int, array{url: string, permalink: string}>|null
	 */
	private function fetch( string $token ): ?array {
		$url = add_query_arg(
			array(
				'fields'       => 'id,media_type,media_url,thumbnail_url,permalink',
				'access_token' => $token,
				'limit'        => self::LIMIT,
			),
			self::ENDPOINT
		);

		$response = wp_remote_get( $url, array( 'timeout' => 8 ) );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			return null;
		}

		$tiles = array();
		foreach ( $body['data'] as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			// Videos have no still to show without a thumbnail; images and
			// album covers use media_url.
			$type = (string) ( $item['media_type'] ?? '' );
			$src  = 'VIDEO' === $type
				? (string) ( $item['thumbnail_url'] ?? '' )
				: (string) ( $item['media_url'] ?? '' );

			if ( '' === $src ) {
				continue;
			}

			$tiles[] = array(
				'url'       => esc_url_raw( $src ),
				'permalink' => esc_url_raw( (string) ( $item['permalink'] ?? '' ) ),
			);

			if ( count( $tiles ) >= self::LIMIT ) {
				break;
			}
		}

		return $tiles;
	}

	/**
	 * Drop the cached feed (token changed, or a manual refresh).
	 */
	public function flush(): void {
		delete_transient( self::CACHE );
	}
}
