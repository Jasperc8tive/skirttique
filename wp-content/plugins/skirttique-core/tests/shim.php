<?php
/**
 * Minimal PHPUnit shim — a dependency-free stand-in used ONLY when real
 * PHPUnit is not installed (e.g. a host without Composer). It implements
 * just the assertions this suite uses, so the test files stay 100%
 * standard PHPUnit and run unchanged under the real framework in CI.
 *
 * Guarded by class_exists: when real PHPUnit is present it loads its
 * classes first and this file is a no-op.
 *
 * @package Skirttique\Core\Tests
 */

declare( strict_types=1 );

namespace PHPUnit\Framework {

	if ( ! class_exists( AssertionFailedError::class ) ) {

		/** Thrown by a failed assertion. */
		class AssertionFailedError extends \Exception {}

		/**
		 * Faithful-enough base test case: the assertions this suite calls,
		 * counting each success so the runner can report coverage.
		 */
		abstract class TestCase {

			public static int $assertions = 0;

			protected function setUp(): void {}

			private static function fail( string $message ): void {
				throw new AssertionFailedError( $message );
			}

			private static function export( $value ): string {
				return is_scalar( $value ) || null === $value
					? var_export( $value, true )
					: gettype( $value );
			}

			public function assertSame( $expected, $actual, string $message = '' ): void {
				++self::$assertions;
				if ( $expected !== $actual ) {
					self::fail( $message ?: sprintf( 'Failed asserting %s is identical to %s.', self::export( $actual ), self::export( $expected ) ) );
				}
			}

			public function assertTrue( $condition, string $message = '' ): void {
				++self::$assertions;
				if ( true !== $condition ) {
					self::fail( $message ?: 'Failed asserting that value is true.' );
				}
			}

			public function assertContains( $needle, iterable $haystack, string $message = '' ): void {
				++self::$assertions;
				$found = false;
				foreach ( $haystack as $item ) {
					if ( $item === $needle ) {
						$found = true;
						break;
					}
				}
				if ( ! $found ) {
					self::fail( $message ?: sprintf( 'Failed asserting that haystack contains %s.', self::export( $needle ) ) );
				}
			}

			public function assertStringContainsString( string $needle, string $haystack, string $message = '' ): void {
				++self::$assertions;
				if ( ! str_contains( $haystack, $needle ) ) {
					self::fail( $message ?: sprintf( 'Failed asserting that "%s" contains "%s".', $haystack, $needle ) );
				}
			}

			public function assertStringNotContainsString( string $needle, string $haystack, string $message = '' ): void {
				++self::$assertions;
				if ( str_contains( $haystack, $needle ) ) {
					self::fail( $message ?: sprintf( 'Failed asserting that "%s" does NOT contain "%s".', $haystack, $needle ) );
				}
			}

			public function assertArrayHasKey( $key, array $array, string $message = '' ): void {
				++self::$assertions;
				if ( ! array_key_exists( $key, $array ) ) {
					self::fail( $message ?: sprintf( 'Failed asserting that array has key %s.', self::export( $key ) ) );
				}
			}

			public function assertArrayNotHasKey( $key, array $array, string $message = '' ): void {
				++self::$assertions;
				if ( array_key_exists( $key, $array ) ) {
					self::fail( $message ?: sprintf( 'Failed asserting that array does NOT have key %s.', self::export( $key ) ) );
				}
			}
		}
	}
}
