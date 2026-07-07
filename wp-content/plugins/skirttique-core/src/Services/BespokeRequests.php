<?php
/**
 * Bespoke (custom order) requests.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Stage 24: the Custom Orders enquiry flow — deliberately a form, not a
 * booking system (decision of record: a low-volume luxury bespoke
 * service starts as a conversation; a calendar plugin can come later if
 * volume justifies it).
 *
 * Progressive: plain POST + redirect (Newsletter's pattern — nonce,
 * honeypot, path-only redirect). Requests are stored in a capped option
 * and emailed to the client-care address (House Settings → Contact,
 * falling back to the site admin email); the house reads them under
 * Skirttique → Bespoke requests.
 */
final class BespokeRequests implements ServiceInterface {

	public const ACTION = 'skirttique_bespoke';
	public const OPTION = 'skirttique_bespoke_requests';

	/**
	 * Storage is an inbox, not a CRM — cap it.
	 */
	private const MAX_STORED = 500;

	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Validate the enquiry and bounce back with a state flag.
	 */
	public function handle(): void {
		$back = '/custom-orders/';
		$ref  = wp_get_referer();
		if ( $ref ) {
			$parts = wp_parse_url( $ref );
			$back  = ( $parts['path'] ?? $back ) . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
			$back  = remove_query_arg( array( 'st-bespoke', 'st-bespoke-error' ), $back );
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->back_to_form( add_query_arg( 'st-bespoke-error', 'expired', $back ) );
		}

		// Honeypot — pretend success to bots.
		if ( '' !== trim( isset( $_POST['st_website'] ) ? (string) wp_unslash( $_POST['st_website'] ) : '' ) ) {
			$this->back_to_form( add_query_arg( 'st-bespoke', '1', $back ) );
		}

		// Cloudflare Turnstile (inert until keys are set) — a failed
		// challenge bounces back for another attempt.
		if ( ! Turnstile::verify() ) {
			$this->back_to_form( add_query_arg( 'st-bespoke-error', 'expired', $back ) );
		}

		$name     = sanitize_text_field( isset( $_POST['st_name'] ) ? wp_unslash( $_POST['st_name'] ) : '' );
		$email    = sanitize_email( isset( $_POST['st_email'] ) ? wp_unslash( $_POST['st_email'] ) : '' );
		$whatsapp = sanitize_text_field( isset( $_POST['st_whatsapp'] ) ? wp_unslash( $_POST['st_whatsapp'] ) : '' );
		$occasion = sanitize_text_field( isset( $_POST['st_occasion'] ) ? wp_unslash( $_POST['st_occasion'] ) : '' );
		$message  = sanitize_textarea_field( isset( $_POST['st_message'] ) ? wp_unslash( $_POST['st_message'] ) : '' );

		if ( ! is_email( $email ) ) {
			$this->back_to_form( add_query_arg( 'st-bespoke-error', 'email', $back ) );
		}
		if ( '' === $name || '' === trim( $message ) ) {
			$this->back_to_form( add_query_arg( 'st-bespoke-error', 'missing', $back ) );
		}

		$entry = array(
			'time'     => time(),
			'name'     => $name,
			'email'    => $email,
			'whatsapp' => $whatsapp,
			'occasion' => $occasion,
			'message'  => $message,
		);

		$requests = (array) get_option( self::OPTION, array() );
		if ( count( $requests ) < self::MAX_STORED ) {
			$requests[] = $entry;
			update_option( self::OPTION, $requests, false );
		}

		$to = HouseContent::text( 'contact_email', (string) get_option( 'admin_email' ) );
		wp_mail(
			$to,
			/* translators: %s: client name. */
			sprintf( __( 'Bespoke request — %s', 'skirttique-core' ), $name ),
			sprintf(
				"%s\n\nEmail: %s\nWhatsApp: %s\nOccasion: %s\n\n%s",
				$name,
				$email,
				'' !== $whatsapp ? $whatsapp : '—',
				'' !== $occasion ? $occasion : '—',
				$message
			)
		);

		/**
		 * Fires after a bespoke request is received.
		 *
		 * @param array<string, mixed> $entry The stored request.
		 */
		do_action( 'skirttique_bespoke_requested', $entry );

		$this->back_to_form( add_query_arg( 'st-bespoke', '1', $back ) );
	}

	/**
	 * Read-only inbox under the Skirttique menu.
	 */
	public function menu(): void {
		add_submenu_page(
			'skirttique-house',
			__( 'Bespoke requests', 'skirttique-core' ),
			__( 'Bespoke requests', 'skirttique-core' ),
			'manage_woocommerce',
			'skirttique-bespoke',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		$requests = array_reverse( (array) get_option( self::OPTION, array() ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bespoke requests', 'skirttique-core' ); ?></h1>
			<?php if ( ! $requests ) : ?>
				<p><?php esc_html_e( 'No requests yet. The form lives on the Custom Orders page.', 'skirttique-core' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Received', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'Name', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'Email', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'WhatsApp', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'Occasion', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'Request', 'skirttique-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $requests as $request ) : ?>
							<tr>
								<td><?php echo esc_html( wp_date( (string) get_option( 'date_format' ) . ' H:i', (int) ( $request['time'] ?? 0 ) ) ); ?></td>
								<td><?php echo esc_html( (string) ( $request['name'] ?? '' ) ); ?></td>
								<td><a href="mailto:<?php echo esc_attr( (string) ( $request['email'] ?? '' ) ); ?>"><?php echo esc_html( (string) ( $request['email'] ?? '' ) ); ?></a></td>
								<td><?php echo esc_html( (string) ( $request['whatsapp'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $request['occasion'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $request['message'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
					<p class="description">
						<?php
						/* translators: %s: retention duration, e.g. "1 year". */
						echo esc_html( sprintf( __( 'Requests older than %s are pruned automatically. To answer a data-subject request for one client, use Tools → Export / Erase Personal Data.', 'skirttique-core' ), Privacy::retention_label() ) );
						?>
					</p>
					<?php echo Privacy::export_button( self::OPTION, __( 'Export CSV', 'skirttique-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in export_button(). ?>
					<?php echo Privacy::clear_button( self::OPTION, __( 'Clear all requests', 'skirttique-core' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in clear_button(). ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Redirect to the form section of the referring page.
	 */
	private function back_to_form( string $url ): never {
		wp_safe_redirect( $url . '#st-bespoke-form' );
		exit;
	}
}
