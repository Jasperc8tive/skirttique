<?php
/**
 * Seed the standing content pages: Privacy Policy, Terms of Service,
 * Delivery & Returns. Idempotent — creates the page if the slug is
 * free, otherwise refreshes title + content. Run with:
 *
 *   npx wp-env run cli -- wp eval-file wp-content/themes/skirttique/tools/seed-content-pages.php
 *
 * Copy notes: policy specifics (returns window, bespoke policy, the
 * care@ address) follow the store copy already live on the PDP panels;
 * all three pages need a legal read-through before launch (NDPA/GDPR
 * wording especially). (wp eval-file forbids declare(strict_types).)
 *
 * @package Skirttique
 */

$st_updated = '<!-- wp:paragraph {"className":"st-page__updated"} --><p class="st-page__updated">Last updated 2 July 2026</p><!-- /wp:paragraph -->';

$st_pages = array(

	'privacy' => array(
		'title'   => 'Privacy Policy',
		'content' => $st_updated . '
<!-- wp:paragraph {"className":"st-page__lede"} --><p class="st-page__lede">Skirttique keeps what you share with us the way we keep our pieces: carefully, and only for as long as it serves you. This policy explains what we collect, why, and the choices you have.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">What we collect</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Your name, contact and delivery details when you place an order or create an account; your email address when you join the house list; and the essentials of how the store is used — your bag, your market and currency choice, the pieces you save.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">How we use it</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>To make and deliver what you order, to answer when you write to us, and — only with your consent — to send the house letters. We use browsing basics to keep the store working well. We do not sell your information, and we never will.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Payments</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Payments are handled by our payment providers on their secured systems. Your card details never touch our servers; we receive only confirmation that a payment succeeded.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Cookies</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>We set the small number of cookies the store needs to function: your session and bag, your sign-in, and your chosen market. Saved pieces and recently viewed items are kept on your own device, not on our servers. If we ever introduce analytics cookies, we will ask first.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Who we share it with</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Only the partners who help us serve you: delivery carriers (GIG Logistics within Nigeria; DHL and comparable couriers internationally), our payment providers, and the platform that sends the house letters. Each receives only what its work requires.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">How long we keep it</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Order records are kept for as long as tax and consumer law require. Your place on the house list lasts until you leave it — every letter carries an unsubscribe link, and one click ends it.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Your rights</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Under the Nigeria Data Protection Act and, where it applies to you, the GDPR, you may ask to see the information we hold about you, have it corrected or deleted, or object to how it is used. Write to us and we will act on it promptly.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Reaching us</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Questions about this policy or your information: <a href="mailto:care@skirttique.com">care@skirttique.com</a>.</p><!-- /wp:paragraph -->',
	),

	'terms' => array(
		'title'   => 'Terms of Service',
		'content' => $st_updated . '
<!-- wp:paragraph {"className":"st-page__lede"} --><p class="st-page__lede">These terms govern the Skirttique store. Placing an order means you accept them — they are short, and written to be read.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">The house</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Skirttique is a modest fashion house based in Lagos, Nigeria, making midi and maxi skirts for a global clientele. Writing to <a href="mailto:care@skirttique.com">care@skirttique.com</a> reaches us directly.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Orders</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Your order is an offer to purchase; it is accepted when we dispatch. We may decline an order where a piece has sold out, a price was displayed in error, or a payment cannot be verified — and if we do, anything paid is returned in full.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Pricing &amp; currency</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Prices are shown, and charged, in the currency of your chosen market. The price at the moment you place the order is the price — if we correct a display error afterwards, it will not reach an order already accepted. International orders may attract import duties; see <a href="/delivery-returns/">Delivery &amp; Returns</a>.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Bespoke &amp; limited editions</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Bespoke pieces are cut to your measurements, with the timeline agreed when the order is placed. Limited editions are exactly that — once a run is finished, it is not repeated.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Delivery &amp; returns</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Dispatch, timelines, and the 14-day return window are set out in full in <a href="/delivery-returns/">Delivery &amp; Returns</a>, which forms part of these terms.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">What is ours</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>The Skirttique name, imagery, patterns and designs, and everything published on this site belong to the house. Enjoy them here; do not reproduce them commercially without our written consent.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Liability</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>We are responsible for what the law makes us responsible for, and we do not attempt to exclude what cannot be excluded. Beyond that, our liability for any order is limited to the amount you paid for it.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Governing law</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>These terms are governed by the laws of the Federal Republic of Nigeria. If you shop from elsewhere, nothing here takes away protections your local law grants you and does not allow to be waived.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Changes</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>When these terms change, the date above changes with them. The terms in force when you order are the terms that apply to that order.</p><!-- /wp:paragraph -->',
	),

	'delivery-returns' => array(
		'title'   => 'Delivery & Returns',
		'content' => $st_updated . '
<!-- wp:paragraph {"className":"st-page__lede"} --><p class="st-page__lede">Every piece leaves our Lagos atelier checked, wrapped, and tracked — whether it is crossing the city or an ocean.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Dispatch</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Ready-to-wear pieces are dispatched within 1–2 business days of your order. Bespoke pieces follow the timeline agreed when the order is placed. You receive tracking the moment your piece leaves.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Within Nigeria</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Delivery is by GIG Logistics: typically 1–2 business days within Lagos, and 2–5 business days nationwide. The exact cost is shown at checkout before you pay.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Worldwide</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>International orders travel by express courier — DHL, or an equivalent where it serves your destination better — reaching most cities in 3–7 business days. The cost is shown at checkout.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Duties &amp; taxes</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Orders delivered outside Nigeria may attract import duties or local taxes, set by your country and payable by you on arrival. They are not included in our prices, and the courier will contact you if any are due.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Returns</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Unworn pieces return within 14 days of delivery — tags attached, in their original wrapping. Pieces that have been worn, altered, or marked cannot come back to the atelier.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">How to return</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Write to <a href="mailto:care@skirttique.com">care@skirttique.com</a> with your order number. We confirm the return and send instructions the same business day — you arrange the shipment, and we recommend a tracked service; the piece remains your care until it reaches us.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Refunds</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Once the piece reaches us and is checked, your refund goes back to the original payment method within 14 days. Original delivery costs are refunded only when a piece arrived faulty.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Exchanges</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Need a different size? Write to us first — where stock allows, we hold the size you need while your return travels, and dispatch it the day the original arrives back.</p><!-- /wp:paragraph -->

<!-- wp:heading --><h2 class="wp-block-heading">Bespoke pieces</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Because a bespoke piece is cut to your measurements alone, it returns only if it arrives faulty or not as agreed — in which case we make it right in full, delivery included.</p><!-- /wp:paragraph -->',
	),
);

foreach ( $st_pages as $st_slug => $st_page ) {
	$st_existing = get_page_by_path( $st_slug );

	$st_args = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $st_page['title'],
		'post_name'    => $st_slug,
		'post_content' => $st_page['content'],
	);

	if ( $st_existing ) {
		$st_args['ID'] = $st_existing->ID;
		wp_update_post( $st_args );
		echo "updated: {$st_slug} (#{$st_existing->ID})\n";
	} else {
		$st_id = wp_insert_post( $st_args );
		echo "created: {$st_slug} (#{$st_id})\n";
	}
}
