<?php
/**
 * Title: Bespoke request form
 * Slug: skirttique/bespoke-form
 * Categories: skirttique
 * Inserter: no
 *
 * The Custom Orders enquiry form (Stage 24) — plain POST to the
 * plugin's admin-post handler (Skirttique\Core\Services\BespokeRequests),
 * no JavaScript required. Success and error states ride GET flags.
 *
 * @package Skirttique
 */

declare( strict_types=1 );

$st_sent  = isset( $_GET['st-bespoke'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
$st_error = isset( $_GET['st-bespoke-error'] ) ? sanitize_key( wp_unslash( $_GET['st-bespoke-error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.
?>

<section class="st-section st-bespoke" id="st-bespoke-form" aria-labelledby="st-bespoke-title">
	<div class="st-drape"><div class="st-section__head">
		<p class="st-section__eyebrow"><?php esc_html_e( 'Begin a commission', 'skirttique' ); ?></p>
		<h2 class="st-section__title" id="st-bespoke-title"><?php esc_html_e( 'Tell the atelier', 'skirttique' ); ?></h2>
	</div></div>

	<?php if ( $st_sent ) : ?>
		<p class="st-bespoke__done" role="status">
			<?php esc_html_e( 'Received. The atelier reads every request personally and will reply within two working days.', 'skirttique' ); ?>
		</p>
	<?php else : ?>
		<form class="st-bespoke__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="skirttique_bespoke">
			<?php wp_nonce_field( 'skirttique_bespoke' ); ?>

			<p class="st-bespoke__hp" aria-hidden="true">
				<label for="st-bespoke-website"><?php esc_html_e( 'Website', 'skirttique' ); ?></label>
				<input type="text" name="st_website" id="st-bespoke-website" tabindex="-1" autocomplete="off">
			</p>

			<?php if ( 'expired' === $st_error ) : ?>
				<p class="st-bespoke__error" role="alert"><?php esc_html_e( 'That took a little long and the form timed out — please try again.', 'skirttique' ); ?></p>
			<?php elseif ( 'email' === $st_error ) : ?>
				<p class="st-bespoke__error" role="alert"><?php esc_html_e( 'Enter a full email address, like name@example.com.', 'skirttique' ); ?></p>
			<?php elseif ( 'missing' === $st_error ) : ?>
				<p class="st-bespoke__error" role="alert"><?php esc_html_e( 'Your name and a few words about the piece are all we need to begin.', 'skirttique' ); ?></p>
			<?php endif; ?>

			<div class="st-bespoke__row">
				<p>
					<label for="st-bespoke-name"><?php esc_html_e( 'Name', 'skirttique' ); ?></label>
					<input type="text" name="st_name" id="st-bespoke-name" autocomplete="name" required>
				</p>
				<p>
					<label for="st-bespoke-email"><?php esc_html_e( 'Email', 'skirttique' ); ?></label>
					<input type="email" name="st_email" id="st-bespoke-email" autocomplete="email" required>
				</p>
			</div>

			<div class="st-bespoke__row">
				<p>
					<label for="st-bespoke-whatsapp"><?php esc_html_e( 'WhatsApp (optional)', 'skirttique' ); ?></label>
					<input type="tel" name="st_whatsapp" id="st-bespoke-whatsapp" autocomplete="tel" placeholder="+234…">
				</p>
				<p>
					<label for="st-bespoke-occasion"><?php esc_html_e( 'The occasion, if there is one (optional)', 'skirttique' ); ?></label>
					<input type="text" name="st_occasion" id="st-bespoke-occasion" placeholder="<?php esc_attr_e( 'A wedding in June, a keynote in May…', 'skirttique' ); ?>">
				</p>
			</div>

			<p>
				<label for="st-bespoke-message"><?php esc_html_e( 'The piece you have in mind', 'skirttique' ); ?></label>
				<textarea name="st_message" id="st-bespoke-message" rows="6" required placeholder="<?php esc_attr_e( 'Length, cloth, colour, movement — as much or as little as you know.', 'skirttique' ); ?>"></textarea>
			</p>

			<p class="st-bespoke__actions">
				<button type="submit" class="st-btn st-btn--primary"><?php esc_html_e( 'Send to the atelier', 'skirttique' ); ?></button>
				<span class="st-bespoke__note"><?php esc_html_e( 'No deposit is taken until the design is agreed.', 'skirttique' ); ?></span>
			</p>
		</form>
	<?php endif; ?>
</section>
