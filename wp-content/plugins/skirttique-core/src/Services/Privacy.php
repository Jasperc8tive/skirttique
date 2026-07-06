<?php
/**
 * Privacy — GDPR data-subject tooling and storage limitation.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * The three public forms persist personal data in options — client-care
 * messages (ContactMessages), bespoke requests (BespokeRequests) and the
 * house list (Newsletter). This service makes that data answerable to the
 * law:
 *
 *  - It registers WordPress's personal-data exporter and eraser, so a
 *    subject-access or erasure request run from Tools → Export / Erase
 *    Personal Data reaches these stores like any core data.
 *  - It prunes the transactional inboxes on a daily schedule (storage
 *    limitation). The newsletter list is consent-based standing data, so
 *    it is exportable and erasable but never age-pruned.
 *  - It exposes a nonce-guarded "Clear all" action the inbox screens
 *    render, so the house can empty a store by hand (and relieve the
 *    per-store cap).
 *
 * The cron is scheduled on init (self-healing) and cleared on deactivation
 * (skirttique-core.php) and uninstall (uninstall.php).
 */
final class Privacy implements ServiceInterface {

	public const CLEAR_ACTION = 'skirttique_privacy_clear';
	public const CRON         = 'skirttique_privacy_prune';

	/** Transactional inboxes: safe to clear by hand and to age-prune. */
	private const INBOXES = array(
		ContactMessages::OPTION,
		BespokeRequests::OPTION,
	);

	public function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );

		add_action( 'admin_post_' . self::CLEAR_ACTION, array( $this, 'handle_clear' ) );

		add_action( self::CRON, array( $this, 'prune' ) );
		add_action( 'init', array( $this, 'schedule' ) );
	}

	/**
	 * Retention window for the transactional inboxes, in seconds (default
	 * one year; filter to taste). The house list is exempt.
	 */
	public static function retention(): int {
		return (int) apply_filters( 'skirttique_privacy_retention', YEAR_IN_SECONDS );
	}

	/**
	 * Human-readable retention window, for the inbox notes.
	 */
	public static function retention_label(): string {
		return human_time_diff( 0, self::retention() );
	}

	/* ----------------------------------------------------------------- */
	/* Exporters                                                           */
	/* ----------------------------------------------------------------- */

	/**
	 * @param array<string, array{exporter_friendly_name: string, callback: callable}> $exporters Registered exporters.
	 * @return array<string, array{exporter_friendly_name: string, callback: callable}>
	 */
	public function exporters( array $exporters ): array {
		$exporters['skirttique-contact']    = array(
			'exporter_friendly_name' => __( 'Skirttique client-care messages', 'skirttique-core' ),
			'callback'               => array( $this, 'export_contact' ),
		);
		$exporters['skirttique-bespoke']    = array(
			'exporter_friendly_name' => __( 'Skirttique bespoke requests', 'skirttique-core' ),
			'callback'               => array( $this, 'export_bespoke' ),
		);
		$exporters['skirttique-newsletter'] = array(
			'exporter_friendly_name' => __( 'Skirttique house list', 'skirttique-core' ),
			'callback'               => array( $this, 'export_newsletter' ),
		);

		return $exporters;
	}

	/**
	 * @return array{data: list<array<string, mixed>>, done: bool}
	 */
	public function export_contact( string $email, int $page = 1 ): array {
		$items = array();
		foreach ( (array) get_option( ContactMessages::OPTION, array() ) as $i => $entry ) {
			if ( ! is_array( $entry ) || ! self::matches( $entry['email'] ?? '', $email ) ) {
				continue;
			}
			$items[] = array(
				'group_id'    => 'skirttique_contact',
				'group_label' => __( 'Skirttique — client-care messages', 'skirttique-core' ),
				'item_id'     => 'st-contact-' . $i,
				'data'        => array(
					array( 'name' => __( 'Received', 'skirttique-core' ), 'value' => self::when( $entry['time'] ?? 0 ) ),
					array( 'name' => __( 'Name', 'skirttique-core' ), 'value' => (string) ( $entry['name'] ?? '' ) ),
					array( 'name' => __( 'Email', 'skirttique-core' ), 'value' => (string) ( $entry['email'] ?? '' ) ),
					array( 'name' => __( 'Concerns', 'skirttique-core' ), 'value' => (string) ( $entry['topic'] ?? '' ) ),
					array( 'name' => __( 'Message', 'skirttique-core' ), 'value' => (string) ( $entry['message'] ?? '' ) ),
				),
			);
		}

		return array( 'data' => $items, 'done' => true );
	}

	/**
	 * @return array{data: list<array<string, mixed>>, done: bool}
	 */
	public function export_bespoke( string $email, int $page = 1 ): array {
		$items = array();
		foreach ( (array) get_option( BespokeRequests::OPTION, array() ) as $i => $entry ) {
			if ( ! is_array( $entry ) || ! self::matches( $entry['email'] ?? '', $email ) ) {
				continue;
			}
			$items[] = array(
				'group_id'    => 'skirttique_bespoke',
				'group_label' => __( 'Skirttique — bespoke requests', 'skirttique-core' ),
				'item_id'     => 'st-bespoke-' . $i,
				'data'        => array(
					array( 'name' => __( 'Received', 'skirttique-core' ), 'value' => self::when( $entry['time'] ?? 0 ) ),
					array( 'name' => __( 'Name', 'skirttique-core' ), 'value' => (string) ( $entry['name'] ?? '' ) ),
					array( 'name' => __( 'Email', 'skirttique-core' ), 'value' => (string) ( $entry['email'] ?? '' ) ),
					array( 'name' => __( 'WhatsApp', 'skirttique-core' ), 'value' => (string) ( $entry['whatsapp'] ?? '' ) ),
					array( 'name' => __( 'Occasion', 'skirttique-core' ), 'value' => (string) ( $entry['occasion'] ?? '' ) ),
					array( 'name' => __( 'Request', 'skirttique-core' ), 'value' => (string) ( $entry['message'] ?? '' ) ),
				),
			);
		}

		return array( 'data' => $items, 'done' => true );
	}

	/**
	 * @return array{data: list<array<string, mixed>>, done: bool}
	 */
	public function export_newsletter( string $email, int $page = 1 ): array {
		$items = array();
		foreach ( (array) get_option( Newsletter::OPTION, array() ) as $addr => $time ) {
			if ( ! self::matches( (string) $addr, $email ) ) {
				continue;
			}
			$items[] = array(
				'group_id'    => 'skirttique_newsletter',
				'group_label' => __( 'Skirttique — house list', 'skirttique-core' ),
				'item_id'     => 'st-newsletter',
				'data'        => array(
					array( 'name' => __( 'Email', 'skirttique-core' ), 'value' => (string) $addr ),
					array( 'name' => __( 'Joined', 'skirttique-core' ), 'value' => self::when( (int) $time ) ),
				),
			);
		}

		return array( 'data' => $items, 'done' => true );
	}

	/* ----------------------------------------------------------------- */
	/* Erasers                                                             */
	/* ----------------------------------------------------------------- */

	/**
	 * @param array<string, array{eraser_friendly_name: string, callback: callable}> $erasers Registered erasers.
	 * @return array<string, array{eraser_friendly_name: string, callback: callable}>
	 */
	public function erasers( array $erasers ): array {
		$erasers['skirttique-contact']    = array(
			'eraser_friendly_name' => __( 'Skirttique client-care messages', 'skirttique-core' ),
			'callback'             => array( $this, 'erase_contact' ),
		);
		$erasers['skirttique-bespoke']    = array(
			'eraser_friendly_name' => __( 'Skirttique bespoke requests', 'skirttique-core' ),
			'callback'             => array( $this, 'erase_bespoke' ),
		);
		$erasers['skirttique-newsletter'] = array(
			'eraser_friendly_name' => __( 'Skirttique house list', 'skirttique-core' ),
			'callback'             => array( $this, 'erase_newsletter' ),
		);

		return $erasers;
	}

	/**
	 * @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool}
	 */
	public function erase_contact( string $email, int $page = 1 ): array {
		return $this->erase_indexed( ContactMessages::OPTION, $email );
	}

	/**
	 * @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool}
	 */
	public function erase_bespoke( string $email, int $page = 1 ): array {
		return $this->erase_indexed( BespokeRequests::OPTION, $email );
	}

	/**
	 * Remove every entry whose 'email' matches, from an indexed inbox.
	 *
	 * @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool}
	 */
	private function erase_indexed( string $option, string $email ): array {
		$list    = (array) get_option( $option, array() );
		$kept    = array();
		$removed = 0;
		foreach ( $list as $entry ) {
			if ( is_array( $entry ) && self::matches( $entry['email'] ?? '', $email ) ) {
				++$removed;
				continue;
			}
			$kept[] = $entry;
		}
		if ( $removed > 0 ) {
			update_option( $option, array_values( $kept ), false );
		}

		return array(
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/**
	 * @return array{items_removed: bool, items_retained: bool, messages: list<string>, done: bool}
	 */
	public function erase_newsletter( string $email, int $page = 1 ): array {
		$list    = (array) get_option( Newsletter::OPTION, array() );
		$removed = 0;
		foreach ( array_keys( $list ) as $addr ) {
			if ( self::matches( (string) $addr, $email ) ) {
				unset( $list[ $addr ] );
				++$removed;
			}
		}
		if ( $removed > 0 ) {
			update_option( Newsletter::OPTION, $list, false );
		}

		return array(
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	/* ----------------------------------------------------------------- */
	/* Retention                                                           */
	/* ----------------------------------------------------------------- */

	public function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON );
		}
	}

	/**
	 * Drop transactional inbox entries older than the retention window.
	 * The consent-based house list is never age-pruned.
	 */
	public function prune(): void {
		$cutoff = time() - self::retention();

		foreach ( self::INBOXES as $option ) {
			$list = (array) get_option( $option, array() );
			$kept = array_values(
				array_filter(
					$list,
					static fn ( $entry ): bool => is_array( $entry ) && (int) ( $entry['time'] ?? 0 ) >= $cutoff
				)
			);
			if ( count( $kept ) !== count( $list ) ) {
				update_option( $option, $kept, false );
			}
		}
	}

	/* ----------------------------------------------------------------- */
	/* Admin "Clear all"                                                   */
	/* ----------------------------------------------------------------- */

	/**
	 * A nonce-guarded "Clear all" form for an inbox screen. Only the
	 * allow-listed inbox options can be targeted.
	 */
	public static function clear_button( string $option, string $label ): string {
		if ( ! in_array( $option, self::INBOXES, true ) ) {
			return '';
		}

		return sprintf(
			'<form method="post" action="%1$s" style="margin-top:1em" onsubmit="return confirm(%2$s);">'
				. '<input type="hidden" name="action" value="%3$s">'
				. '<input type="hidden" name="option" value="%4$s">'
				. '%5$s'
				. '<button type="submit" class="button button-secondary delete">%6$s</button>'
				. '</form>',
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( (string) wp_json_encode( __( 'Permanently delete every stored entry here? This cannot be undone.', 'skirttique-core' ) ) ),
			esc_attr( self::CLEAR_ACTION ),
			esc_attr( $option ),
			wp_nonce_field( self::CLEAR_ACTION, '_wpnonce', true, false ),
			esc_html( $label )
		);
	}

	public function handle_clear(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'skirttique-core' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::CLEAR_ACTION );

		$option = isset( $_POST['option'] ) ? sanitize_key( wp_unslash( $_POST['option'] ) ) : '';
		if ( in_array( $option, self::INBOXES, true ) ) {
			delete_option( $option );
		}

		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	/* ----------------------------------------------------------------- */
	/* Helpers                                                             */
	/* ----------------------------------------------------------------- */

	/**
	 * Case-insensitive email match against a stored value.
	 *
	 * @param mixed $stored The stored address (may be any type).
	 */
	private static function matches( $stored, string $email ): bool {
		return '' !== $email && strtolower( trim( (string) $stored ) ) === strtolower( trim( $email ) );
	}

	/**
	 * Format a stored unix time for export ('' when absent).
	 *
	 * @param mixed $time Stored timestamp.
	 */
	private static function when( $time ): string {
		$time = (int) $time;

		return $time > 0 ? wp_date( (string) get_option( 'date_format' ) . ' H:i', $time ) : '';
	}
}
