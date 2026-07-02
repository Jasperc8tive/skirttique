<?php
/**
 * House Settings sanitizers — the owner can never save something unsafe
 * or blank the site by mistake.
 *
 * @package Skirttique\Core\Tests
 */

declare( strict_types=1 );

namespace Skirttique\Core\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Skirttique\Core\Services\HouseContent;

final class HouseContentSanitizeTest extends TestCase {

	private HouseContent $house;

	protected function setUp(): void {
		st_test_reset();
		$this->house = new HouseContent();
	}

	/** Non-array input sanitizes to an empty array, never a fatal. */
	public function test_non_array_input_returns_empty(): void {
		$this->assertSame( array(), $this->house->sanitize( 'nope' ) );
		$this->assertSame( array(), $this->house->sanitize( null ) );
	}

	/** Plain text fields are tag-stripped. */
	public function test_text_fields_strip_tags(): void {
		$clean = $this->house->sanitize( array( 'hero_statement' => '<b>The skirt</b>, reconsidered' ) );
		$this->assertSame( 'The skirt, reconsidered', $clean['hero_statement'] );
	}

	/** A script payload in a text field is neutralised. */
	public function test_text_field_neutralises_script(): void {
		$clean = $this->house->sanitize( array( 'hero_eyebrow' => 'Collection I<script>alert(1)</script>' ) );
		$this->assertStringNotContainsString( '<script', $clean['hero_eyebrow'] );
		$this->assertStringNotContainsString( 'alert', $clean['hero_eyebrow'] );
	}

	/** Textarea fields keep newlines (announcements are one line each). */
	public function test_textarea_preserves_newlines(): void {
		$clean = $this->house->sanitize( array( 'announcements' => "Line one\nLine two\nLine three" ) );
		$this->assertSame( "Line one\nLine two\nLine three", $clean['announcements'] );
	}

	/** Quote fields (quote_*) are treated as textarea and keep newlines. */
	public function test_quote_fields_are_textarea(): void {
		$clean = $this->house->sanitize( array( 'quote_1' => "First line\nsecond line" ) );
		$this->assertStringContainsString( "\n", $clean['quote_1'] );
	}

	/** Social links run through URL sanitisation. */
	public function test_social_links_are_url_sanitised(): void {
		$clean = $this->house->sanitize( array( 'social_instagram' => '  https://instagram.com/skirttique  ' ) );
		$this->assertSame( 'https://instagram.com/skirttique', $clean['social_instagram'] );
	}

	/** A javascript: URL in a social field is rejected. */
	public function test_social_link_rejects_javascript_scheme(): void {
		$clean = $this->house->sanitize( array( 'social_tiktok' => 'javascript:alert(1)' ) );
		$this->assertSame( '', $clean['social_tiktok'] );
	}

	/** A valid image id that is a real image is kept. */
	public function test_valid_image_id_is_kept(): void {
		$GLOBALS['__st_image_ids'] = array( 42 );

		$clean = $this->house->sanitize( array( 'hero_image_id' => '42' ) );
		$this->assertSame( '42', $clean['hero_image_id'] );
	}

	/** An id that is not an image is dropped to empty (falls back to shipped media). */
	public function test_non_image_id_is_dropped(): void {
		$GLOBALS['__st_image_ids'] = array( 42 );

		$clean = $this->house->sanitize( array( 'hero_image_id' => '999' ) );
		$this->assertSame( '', $clean['hero_image_id'] );
	}

	/** Valid rates survive. */
	public function test_valid_rates_are_kept(): void {
		$clean = $this->house->sanitize_rates(
			array( 'USD' => '0.0007', 'GBP' => '0.0005', 'ZAR' => '0.012', 'AED' => '0.0025' )
		);

		$this->assertSame( 0.0007, $clean['USD'] );
		$this->assertSame( 0.012, $clean['ZAR'] );
	}

	/** Zero, negative, and blank rates are dropped so they fall back to placeholders. */
	public function test_invalid_rates_are_dropped(): void {
		$clean = $this->house->sanitize_rates(
			array( 'USD' => '0', 'GBP' => '-0.1', 'ZAR' => '', 'AED' => '0.0025' )
		);

		$this->assertArrayNotHasKey( 'USD', $clean );
		$this->assertArrayNotHasKey( 'GBP', $clean );
		$this->assertArrayNotHasKey( 'ZAR', $clean );
		$this->assertSame( 0.0025, $clean['AED'] );
	}

	/** Unknown currencies are ignored — only the four editable ones are stored. */
	public function test_unknown_currencies_are_ignored(): void {
		$clean = $this->house->sanitize_rates( array( 'USD' => '0.0007', 'JPY' => '0.9', 'NGN' => '2' ) );

		$this->assertSame( array( 'USD' => 0.0007 ), $clean );
	}

	/** Non-array rate input is safe. */
	public function test_non_array_rates_return_empty(): void {
		$this->assertSame( array(), $this->house->sanitize_rates( 'nope' ) );
	}
}
