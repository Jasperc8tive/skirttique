<?php
/**
 * House content service — the owner's editing surface.
 *
 * @package Skirttique\Core
 */

declare( strict_types=1 );

namespace Skirttique\Core\Services;

use Skirttique\Core\Contracts\ServiceInterface;

/**
 * Stage 11 (CMS Experience): everything the house edits day-to-day,
 * on one screen — Skirttique → House Settings. Values live in the
 * `skirttique_house` option; currency rates in `skirttique_currency_rates`
 * (the option Services\Currency already reads).
 *
 * The theme stays authoritative for DEFAULTS: this service only bridges
 * saved values into the theme's existing filters (announcements, social
 * links, press quotes), so an empty setting falls back to the shipped
 * copy rather than a blank site. Products, collections (including their
 * card imagery — the category thumbnail), and pages are edited in their
 * native WordPress/WooCommerce screens, not here.
 */
final class HouseContent implements ServiceInterface {

	public const OPTION = 'skirttique_house';

	private const GROUP = 'skirttique_house_group';
	private const PAGE  = 'skirttique-house';

	/** @var array<string, float> Currencies editable on the rates section. */
	private const RATE_CURRENCIES = array(
		'USD' => 0.00065,
		'GBP' => 0.00051,
		'ZAR' => 0.0118,
		'AED' => 0.0024,
	);

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

		// Bridge saved values into the theme's filters (empty → theme defaults).
		add_filter( 'skirttique_announcements', array( $this, 'announcements' ) );
		add_filter( 'skirttique_social_links', array( $this, 'social_links' ) );
		add_filter( 'skirttique_press_quotes', array( $this, 'press_quotes' ) );
	}

	/**
	 * One saved value (trimmed; '' when unset).
	 */
	public static function value( string $key ): string {
		$options = get_option( self::OPTION, array() );

		return is_array( $options ) ? trim( (string) ( $options[ $key ] ?? '' ) ) : '';
	}

	/**
	 * A saved value with a fallback when the owner left it empty.
	 */
	public static function text( string $key, string $default ): string {
		$value = self::value( $key );

		return '' !== $value ? $value : $default;
	}

	/* ---------------------------------------------------------------- */
	/* Filter bridges                                                     */
	/* ---------------------------------------------------------------- */

	/**
	 * Announcement bar lines (one per textarea line).
	 *
	 * @param list<string> $defaults Theme defaults.
	 * @return list<string>
	 */
	public function announcements( array $defaults ): array {
		$raw   = self::value( 'announcements' );
		$lines = array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );

		return $lines ? array_slice( $lines, 0, 4 ) : $defaults;
	}

	/**
	 * Social links — only networks with a saved URL replace the defaults.
	 *
	 * @param array<string, string> $defaults Theme defaults.
	 * @return array<string, string>
	 */
	public function social_links( array $defaults ): array {
		$saved = array_filter(
			array(
				'Instagram' => self::value( 'social_instagram' ),
				'TikTok'    => self::value( 'social_tiktok' ),
				'Pinterest' => self::value( 'social_pinterest' ),
			)
		);

		return $saved ? $saved : $defaults;
	}

	/**
	 * Press/client quotes — pairs are used only when the quote is filled.
	 *
	 * @param list<array{quote: string, source: string}> $defaults Theme defaults.
	 * @return list<array{quote: string, source: string}>
	 */
	public function press_quotes( array $defaults ): array {
		$quotes = array();

		foreach ( array( 1, 2, 3 ) as $i ) {
			$quote = self::value( "quote_{$i}" );
			if ( '' !== $quote ) {
				$quotes[] = array(
					'quote'  => $quote,
					'source' => self::value( "quote_source_{$i}" ),
				);
			}
		}

		return $quotes ? $quotes : $defaults;
	}

	/* ---------------------------------------------------------------- */
	/* Admin screen                                                       */
	/* ---------------------------------------------------------------- */

	public function menu(): void {
		add_menu_page(
			__( 'House Settings', 'skirttique-core' ),
			__( 'Skirttique', 'skirttique-core' ),
			'manage_woocommerce',
			self::PAGE,
			array( $this, 'render' ),
			'dashicons-admin-customizer',
			58
		);
	}

	/**
	 * Media picker for the image fields, only on our screen.
	 */
	public function assets( string $hook ): void {
		if ( 'toplevel_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'skirttique-house-settings',
			plugins_url( 'assets/house-settings.js', SKIRTTIQUE_CORE_FILE ),
			array(),
			SKIRTTIQUE_CORE_VERSION,
			array( 'in_footer' => true )
		);
	}

	public function settings(): void {
		register_setting( self::GROUP, self::OPTION, array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
		register_setting( self::GROUP, Currency::RATES_OPTION, array( 'sanitize_callback' => array( $this, 'sanitize_rates' ) ) );

		$sections = array(
			'announcement' => __( 'Announcement bar', 'skirttique-core' ),
			'hero'         => __( 'Homepage — hero', 'skirttique-core' ),
			'craft'        => __( 'Homepage — craftsmanship', 'skirttique-core' ),
			'featured'     => __( 'Homepage — featured', 'skirttique-core' ),
			'why'          => __( 'Homepage — why Skirttique', 'skirttique-core' ),
			'philosophy'   => __( 'Homepage — philosophy', 'skirttique-core' ),
			'press'        => __( 'Homepage — in their words', 'skirttique-core' ),
			'closing'      => __( 'Homepage — closing band', 'skirttique-core' ),
			'social'       => __( 'Social profiles', 'skirttique-core' ),
			'contact'      => __( 'Contact details', 'skirttique-core' ),
			'experience'   => __( 'Experience', 'skirttique-core' ),
			'rates'        => __( 'Currency rates', 'skirttique-core' ),
		);
		foreach ( $sections as $id => $title ) {
			add_settings_section( "st_{$id}", $title, '__return_false', self::PAGE );
		}

		$this->field( 'announcements', __( 'Rotating lines (one per line, up to four)', 'skirttique-core' ), 'st_announcement', 'textarea' );

		$this->field( 'hero_eyebrow', __( 'Eyebrow', 'skirttique-core' ), 'st_hero' );
		$this->field( 'hero_statement', __( 'Statement', 'skirttique-core' ), 'st_hero' );
		$this->field( 'hero_sub', __( 'Supporting line', 'skirttique-core' ), 'st_hero' );
		$this->field( 'hero_cta', __( 'Button label', 'skirttique-core' ), 'st_hero' );
		$this->field( 'hero_image_id', __( 'Hero image', 'skirttique-core' ), 'st_hero', 'image' );

		// Stage 19 homepage sections — every field blank falls back to the
		// shipped copy, exactly like the hero and philosophy above.
		$this->field( 'craft_statement', __( 'Statement', 'skirttique-core' ), 'st_craft' );
		$this->field( 'craft_prose', __( 'Paragraph', 'skirttique-core' ), 'st_craft', 'textarea' );
		$this->field( 'craft_image_id', __( 'Figure image', 'skirttique-core' ), 'st_craft', 'image' );

		$this->field( 'featured_collection', __( 'Featured collection', 'skirttique-core' ), 'st_featured', 'collection' );
		$this->field( 'featured_product_id', __( 'Featured product', 'skirttique-core' ), 'st_featured', 'product' );

		$this->field( 'why_items', __( 'Reasons (one per line: Title|Description, up to four)', 'skirttique-core' ), 'st_why', 'textarea' );

		$this->field( 'philosophy_statement', __( 'Statement', 'skirttique-core' ), 'st_philosophy' );
		$this->field( 'philosophy_prose', __( 'Paragraph', 'skirttique-core' ), 'st_philosophy', 'textarea' );
		$this->field( 'philosophy_image_id', __( 'Figure image', 'skirttique-core' ), 'st_philosophy', 'image' );

		foreach ( array( 1, 2, 3 ) as $i ) {
			/* translators: %d: quote number. */
			$this->field( "quote_{$i}", sprintf( __( 'Quote %d', 'skirttique-core' ), $i ), 'st_press', 'textarea' );
			/* translators: %d: quote number. */
			$this->field( "quote_source_{$i}", sprintf( __( 'Quote %d — attribution', 'skirttique-core' ), $i ), 'st_press' );
		}

		$this->field( 'closing_statement', __( 'Statement', 'skirttique-core' ), 'st_closing' );

		$this->field( 'social_instagram', __( 'Instagram URL', 'skirttique-core' ), 'st_social', 'url' );
		$this->field( 'social_tiktok', __( 'TikTok URL', 'skirttique-core' ), 'st_social', 'url' );
		$this->field( 'social_pinterest', __( 'Pinterest URL', 'skirttique-core' ), 'st_social', 'url' );

		// Contact details — consumed by the Contact page, footer, and
		// WhatsApp links (Stage 25 surfaces).
		$this->field( 'contact_email', __( 'Client care email', 'skirttique-core' ), 'st_contact' );
		$this->field( 'contact_whatsapp', __( 'WhatsApp number (international format, e.g. +2348000000000)', 'skirttique-core' ), 'st_contact' );
		$this->field( 'contact_hours', __( 'Business hours', 'skirttique-core' ), 'st_contact' );
		$this->field( 'contact_location', __( 'Studio location', 'skirttique-core' ), 'st_contact', 'textarea' );
		// Stage 25: the Visit page's location cards. Blank falls back to
		// one card built from the studio location + hours above.
		$this->field( 'store_locations', __( 'Ateliers (one per line: Name|Address|Hours|Map link)', 'skirttique-core' ), 'st_contact', 'textarea' );

		// Experience — sitewide motion switches (both default ON; the
		// theme also honours prefers-reduced-motion regardless).
		$this->field( 'motion_transitions', __( 'Page transitions', 'skirttique-core' ), 'st_experience', 'checkbox' );
		$this->field( 'motion_parallax', __( 'Parallax drift', 'skirttique-core' ), 'st_experience', 'checkbox' );

		foreach ( self::RATE_CURRENCIES as $code => $placeholder ) {
			add_settings_field(
				"rate_{$code}",
				/* translators: %s: currency code. */
				sprintf( __( '%s per ₦1', 'skirttique-core' ), $code ),
				function () use ( $code, $placeholder ): void {
					$rates = get_option( Currency::RATES_OPTION, array() );
					$value = is_array( $rates ) && isset( $rates[ $code ] ) ? (string) $rates[ $code ] : '';
					printf(
						'<input type="number" step="any" min="0" name="%s[%s]" value="%s" placeholder="%s" class="regular-text">',
						esc_attr( Currency::RATES_OPTION ),
						esc_attr( $code ),
						esc_attr( $value ),
						esc_attr( (string) $placeholder )
					);
				},
				self::PAGE,
				'st_rates'
			);
		}
	}

	/**
	 * Register one option-array field.
	 */
	private function field( string $key, string $label, string $section, string $type = 'text' ): void {
		add_settings_field(
			$key,
			$label,
			function () use ( $key, $type ): void {
				$value = self::value( $key );
				$name  = esc_attr( self::OPTION . '[' . $key . ']' );

				if ( 'textarea' === $type ) {
					printf( '<textarea name="%s" rows="3" class="large-text">%s</textarea>', $name, esc_textarea( $value ) );
				} elseif ( 'checkbox' === $type ) {
					// Hidden 'off' guarantees the key posts when unchecked;
					// '' (never saved) and 'on' both mean enabled.
					printf(
						'<input type="hidden" name="%1$s" value="off"><label><input type="checkbox" name="%1$s" value="on" %2$s> %3$s</label>',
						$name,
						checked( 'off' !== $value, true, false ),
						esc_html__( 'Enabled', 'skirttique-core' )
					);
				} elseif ( 'collection' === $type ) {
					$terms = get_terms(
						array(
							'taxonomy'   => 'product_cat',
							'hide_empty' => false,
							'orderby'    => 'name',
							'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ),
						)
					);
					printf( '<select name="%s">', $name );
					printf( '<option value="">%s</option>', esc_html__( 'Automatic — first collection with an editorial story', 'skirttique-core' ) );
					foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
						printf( '<option value="%s"%s>%s</option>', esc_attr( $term->slug ), selected( $value, $term->slug, false ), esc_html( $term->name ) );
					}
					echo '</select>';
				} elseif ( 'product' === $type ) {
					$found = function_exists( 'wc_get_products' )
						? wc_get_products(
							array(
								'status'  => 'publish',
								'limit'   => 100,
								'orderby' => 'title',
								'order'   => 'ASC',
							)
						)
						: array();
					printf( '<select name="%s">', $name );
					printf( '<option value="">%s</option>', esc_html__( 'Automatic — the newest piece', 'skirttique-core' ) );
					foreach ( $found as $product ) {
						printf( '<option value="%d"%s>%s</option>', (int) $product->get_id(), selected( $value, (string) $product->get_id(), false ), esc_html( $product->get_name() ) );
					}
					echo '</select>';
				} elseif ( 'image' === $type ) {
					$id  = absint( $value );
					$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
					printf(
						'<div class="st-media-field">
							<input type="hidden" name="%s" value="%s" data-st-media-input>
							<img src="%s" alt="" style="max-width:140px;height:auto;%s" data-st-media-preview>
							<p>
								<button type="button" class="button" data-st-media-pick>%s</button>
								<button type="button" class="button" data-st-media-clear style="%s">%s</button>
							</p>
						</div>',
						$name,
						esc_attr( $id ? (string) $id : '' ),
						esc_url( (string) $src ),
						$src ? '' : 'display:none;',
						esc_html__( 'Choose image', 'skirttique-core' ),
						$id ? '' : 'display:none;',
						esc_html__( 'Remove', 'skirttique-core' )
					);
				} else {
					printf(
						'<input type="%s" name="%s" value="%s" class="regular-text">',
						'url' === $type ? 'url' : 'text',
						$name,
						esc_attr( $value )
					);
				}
			},
			self::PAGE,
			$section
		);
	}

	/**
	 * Sanitize the house option array.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array<string, string>
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$clean = array();
		foreach ( $input as $key => $value ) {
			$key   = sanitize_key( (string) $key );
			$value = (string) $value;

			if ( str_starts_with( $key, 'social_' ) ) {
				$clean[ $key ] = esc_url_raw( trim( $value ) );
			} elseif ( str_ends_with( $key, '_email' ) ) {
				$clean[ $key ] = sanitize_email( $value );
			} elseif ( str_starts_with( $key, 'motion_' ) ) {
				$clean[ $key ] = 'off' === $value ? 'off' : 'on';
			} elseif ( str_ends_with( $key, '_image_id' ) ) {
				$id            = absint( $value );
				$clean[ $key ] = $id && wp_attachment_is_image( $id ) ? (string) $id : '';
			} elseif ( 'featured_collection' === $key ) {
				$clean[ $key ] = sanitize_title( $value );
			} elseif ( str_ends_with( $key, '_product_id' ) ) {
				$id            = absint( $value );
				$clean[ $key ] = $id ? (string) $id : '';
			} elseif ( 'announcements' === $key || 'contact_location' === $key || str_starts_with( $key, 'quote_' ) || str_ends_with( $key, '_prose' ) || str_ends_with( $key, '_items' ) || str_ends_with( $key, '_locations' ) ) {
				$clean[ $key ] = sanitize_textarea_field( $value );
			} else {
				$clean[ $key ] = sanitize_text_field( $value );
			}
		}

		return $clean;
	}

	/**
	 * Sanitize rates: keep only known currencies with positive numbers —
	 * anything else falls back to the shipped placeholder rate.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array<string, float>
	 */
	public function sanitize_rates( $input ): array {
		if ( ! is_array( $input ) ) {
			return array();
		}

		$clean = array();
		foreach ( self::RATE_CURRENCIES as $code => $unused ) {
			$rate = isset( $input[ $code ] ) ? (float) $input[ $code ] : 0.0;
			if ( $rate > 0 ) {
				$clean[ $code ] = $rate;
			}
		}

		return $clean;
	}

	public function render(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'House Settings', 'skirttique-core' ); ?></h1>
			<?php settings_errors(); ?>
			<p>
				<?php esc_html_e( 'Everything left blank falls back to the shipped copy. Products and collections are edited under Products (a collection card\'s image is the category thumbnail: Products → Categories); pages under Pages.', 'skirttique-core' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button( __( 'Save house settings', 'skirttique-core' ) );
				?>
			</form>
		</div>
		<?php
	}
}
