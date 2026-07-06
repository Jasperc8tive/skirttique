<?php
/**
 * Title: Contact form
 * Slug: skirttique/contact-form
 * Categories: skirttique
 * Inserter: no
 *
 * The client-care message form (Stage 25) — plain POST to the plugin's
 * admin-post handler (Skirttique\Core\Services\ContactMessages), no
 * JavaScript required. Success and error states ride GET flags; the
 * same nonce + honeypot pattern as the bespoke and newsletter forms.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_sent  = isset( $_GET['st-contact'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
$st_error = isset( $_GET['st-contact-error'] ) ? sanitize_key( wp_unslash( $_GET['st-contact-error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.

$st_topics = array(
	'order'     => __( 'An order', 'skirttique' ),
	'piece'     => __( 'A piece, or its size', 'skirttique' ),
	'bespoke'   => __( 'A bespoke commission', 'skirttique' ),
	'press'     => __( 'Press', 'skirttique' ),
	'something' => __( 'Something else', 'skirttique' ),
);
?>

<section class="st-section st-enquiry" id="st-contact-form" aria-labelledby="st-contact-title">
	<div class="st-drape"><div class="st-section__head">
		<p class="st-section__eyebrow"><?php esc_html_e( 'Write to us', 'skirttique' ); ?></p>
		<h2 class="st-section__title" id="st-contact-title"><?php esc_html_e( 'A message to client care', 'skirttique' ); ?></h2>
	</div></div>

	<?php if ( $st_sent ) : ?>
		<p class="st-enquiry__done" role="status">
			<?php esc_html_e( 'Received. Client care replies within one working day.', 'skirttique' ); ?>
		</p>
	<?php else : ?>
		<form class="st-enquiry__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="skirttique_contact">
			<?php wp_nonce_field( 'skirttique_contact' ); ?>

			<p class="st-enquiry__hp" aria-hidden="true">
				<label for="st-contact-website"><?php esc_html_e( 'Website', 'skirttique' ); ?></label>
				<input type="text" name="st_website" id="st-contact-website" tabindex="-1" autocomplete="off">
			</p>

			<?php if ( 'expired' === $st_error ) : ?>
				<p class="st-enquiry__error" role="alert"><?php esc_html_e( 'That took a little long and the form timed out — please try again.', 'skirttique' ); ?></p>
			<?php elseif ( 'email' === $st_error ) : ?>
				<p class="st-enquiry__error" role="alert"><?php esc_html_e( 'Enter a full email address, like name@example.com.', 'skirttique' ); ?></p>
			<?php elseif ( 'missing' === $st_error ) : ?>
				<p class="st-enquiry__error" role="alert"><?php esc_html_e( 'Your name and a few words are all we need.', 'skirttique' ); ?></p>
			<?php endif; ?>

			<div class="st-enquiry__row">
				<p>
					<label for="st-contact-name"><?php esc_html_e( 'Name', 'skirttique' ); ?></label>
					<input type="text" name="st_name" id="st-contact-name" autocomplete="name" required>
				</p>
				<p>
					<label for="st-contact-email"><?php esc_html_e( 'Email', 'skirttique' ); ?></label>
					<input type="email" name="st_email" id="st-contact-email" autocomplete="email" required>
				</p>
			</div>

			<p>
				<label for="st-contact-topic"><?php esc_html_e( 'It concerns', 'skirttique' ); ?></label>
				<select name="st_topic" id="st-contact-topic">
					<?php foreach ( $st_topics as $st_value => $st_label ) : ?>
						<option value="<?php echo esc_attr( $st_value ); ?>"><?php echo esc_html( $st_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="st-contact-message"><?php esc_html_e( 'Your message', 'skirttique' ); ?></label>
				<textarea name="st_message" id="st-contact-message" rows="6" required placeholder="<?php esc_attr_e( 'Order numbers help with orders; occasions help with pieces.', 'skirttique' ); ?>"></textarea>
			</p>

			<?php
			if ( class_exists( '\Skirttique\Core\Services\Turnstile' ) ) {
				echo \Skirttique\Core\Services\Turnstile::field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in field().
			}
			?>

			<p class="st-enquiry__actions">
				<button type="submit" class="st-btn st-btn--primary"><?php esc_html_e( 'Send to client care', 'skirttique' ); ?></button>
				<span class="st-enquiry__note"><?php esc_html_e( 'Replies come from a person, not a queue.', 'skirttique' ); ?></span>
			</p>
		</form>
	<?php endif; ?>
</section>
