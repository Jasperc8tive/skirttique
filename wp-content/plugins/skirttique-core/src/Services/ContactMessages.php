<?php
/**
 * Contact messages — the client-care inbox.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;
use function add_action;
use function add_query_arg;
use function add_submenu_page;
use function do_action;
use function esc_attr;
use function esc_html;
use function esc_html_e;
use function get_option;
use function is_email;
use function remove_query_arg;
use function sanitize_email;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function sprintf;
use function update_option;
use function wp_date;
use function wp_get_referer;
use function wp_mail;
use function wp_parse_url;
use function wp_safe_redirect;
use function wp_unslash;
use function wp_verify_nonce;
use function __;

/**
 * Stage 25: the Contact page's message form. Progressive: plain POST +
 * redirect (the Newsletter/BespokeRequests pattern — nonce, honeypot,
 * path-only redirect). Messages are stored in a capped option and
 * emailed to the client-care address (House Settings → Contact, falling
 * back to the site admin email); the house reads them under
 * Skirttique → Messages.
 */
final class ContactMessages implements ServiceInterface {

	public const ACTION = 'skirttique_contact';
	public const OPTION = 'skirttique_contact_messages';

	/**
	 * Storage is an inbox, not a CRM — cap it.
	 */
	private const MAX_STORED = 500;

	/** @var array<string, string> Topic slugs → labels (mirrors the form). */
	private const TOPICS = array(
		'order'     => 'An order',
		'piece'     => 'A piece, or its size',
		'bespoke'   => 'A bespoke commission',
		'press'     => 'Press',
		'something' => 'Something else',
	);

	public function register(): void {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_post_nopriv_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Validate the message and bounce back with a state flag.
	 */
	public function handle(): void {
		$back = '/contact/';
		$ref  = wp_get_referer();
		if ( $ref ) {
			$parts = wp_parse_url( $ref );
			$back  = ( $parts['path'] ?? $back ) . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
			$back  = remove_query_arg( array( 'st-contact', 'st-contact-error' ), $back );
		}

		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::ACTION ) ) {
			$this->back_to_form( add_query_arg( 'st-contact-error', 'expired', $back ) );
		}

		// Honeypot — pretend success to bots.
		if ( '' !== trim( isset( $_POST['st_website'] ) ? (string) wp_unslash( $_POST['st_website'] ) : '' ) ) {
			$this->back_to_form( add_query_arg( 'st-contact', '1', $back ) );
		}

		// Cloudflare Turnstile (inert until keys are set) — a failed
		// challenge bounces back for another attempt.
		if ( ! Turnstile::verify() ) {
			$this->back_to_form( add_query_arg( 'st-contact-error', 'expired', $back ) );
		}

		$name    = sanitize_text_field( isset( $_POST['st_name'] ) ? wp_unslash( $_POST['st_name'] ) : '' );
		$email   = sanitize_email( isset( $_POST['st_email'] ) ? wp_unslash( $_POST['st_email'] ) : '' );
		$topic   = sanitize_key( isset( $_POST['st_topic'] ) ? wp_unslash( $_POST['st_topic'] ) : '' );
		$message = sanitize_textarea_field( isset( $_POST['st_message'] ) ? wp_unslash( $_POST['st_message'] ) : '' );

		if ( ! array_key_exists( $topic, self::TOPICS ) ) {
			$topic = 'something';
		}

		if ( ! is_email( $email ) ) {
			$this->back_to_form( add_query_arg( 'st-contact-error', 'email', $back ) );
		}
		if ( '' === $name || '' === trim( $message ) ) {
			$this->back_to_form( add_query_arg( 'st-contact-error', 'missing', $back ) );
		}

		$entry = array(
			'time'    => time(),
			'name'    => $name,
			'email'   => $email,
			'topic'   => $topic,
			'message' => $message,
		);

		$messages = (array) get_option( self::OPTION, array() );
		if ( count( $messages ) < self::MAX_STORED ) {
			$messages[] = $entry;
			update_option( self::OPTION, $messages, false );
		}

		$to = HouseContent::text( 'contact_email', (string) get_option( 'admin_email' ) );
		wp_mail(
			$to,
			/* translators: 1: topic label, 2: client name. */
			sprintf( __( 'Client message (%1$s) — %2$s', 'skirttique-core' ), self::TOPICS[ $topic ], $name ),
			sprintf(
				"%s\n\nEmail: %s\nConcerns: %s\n\n%s",
				$name,
				$email,
				self::TOPICS[ $topic ],
				$message
			)
		);

		/**
		 * Fires after a client-care message is received.
		 *
		 * @param array<string, mixed> $entry The stored message.
		 */
		do_action( 'skirttique_contact_message', $entry );

		$this->back_to_form( add_query_arg( 'st-contact', '1', $back ) );
	}

	/**
	 * Read-only inbox under the Skirttique menu.
	 */
	public function menu(): void {
		add_submenu_page(
			'skirttique-house',
			__( 'Messages', 'skirttique-core' ),
			__( 'Messages', 'skirttique-core' ),
			'manage_woocommerce',
			'skirttique-messages',
			array( $this, 'render' )
		);
	}

	public function render(): void {
		$messages = array_reverse( (array) get_option( self::OPTION, array() ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Messages', 'skirttique-core' ); ?></h1>
			<?php if ( ! $messages ) : ?>
				<p><?php esc_html_e( 'No messages yet. The form lives on the Contact page.', 'skirttique-core' ); ?></p>
			<?php else : ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Received', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'Name', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'Email', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'Concerns', 'skirttique-core' ); ?></th>
							<th><?php esc_html_e( 'Message', 'skirttique-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $messages as $entry ) : ?>
							<tr>
								<td><?php echo esc_html( wp_date( (string) get_option( 'date_format' ) . ' H:i', (int) ( $entry['time'] ?? 0 ) ) ); ?></td>
								<td><?php echo esc_html( (string) ( $entry['name'] ?? '' ) ); ?></td>
								<td><a href="mailto:<?php echo esc_attr( (string) ( $entry['email'] ?? '' ) ); ?>"><?php echo esc_html( (string) ( $entry['email'] ?? '' ) ); ?></a></td>
								<td><?php echo esc_html( self::TOPICS[ (string) ( $entry['topic'] ?? '' ) ] ?? (string) ( $entry['topic'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( (string) ( $entry['message'] ?? '' ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Redirect to the form section of the referring page.
	 */
	private function back_to_form( string $url ): never {
		wp_safe_redirect( $url . '#st-contact-form' );
		exit;
	}
}
