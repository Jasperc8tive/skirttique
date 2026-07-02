<?php
/**
 * Dependency-free test runner for hosts without Composer/PHPUnit.
 *
 *   php tests/run.php
 *
 * Discovers every *Test.php under tests/Unit, runs each public test_*
 * method with a fresh setUp(), and reports pass/fail counts. The test
 * files themselves are standard PHPUnit and also run under the real
 * framework via phpunit.xml.dist — this runner just removes the tooling
 * barrier on a bare host.
 *
 * @package Skirttique\Core\Tests
 */

declare( strict_types=1 );

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

require __DIR__ . '/shim.php';      // Defines TestCase only if PHPUnit is absent.
require __DIR__ . '/bootstrap.php'; // Autoloader + WP function stubs.

foreach ( glob( __DIR__ . '/Unit/*Test.php' ) as $file ) {
	require $file;
}

$tests    = 0;
$failures = array();

foreach ( get_declared_classes() as $class ) {
	if ( ! is_subclass_of( $class, TestCase::class ) ) {
		continue;
	}

	$reflection = new ReflectionClass( $class );
	$setUp      = $reflection->hasMethod( 'setUp' ) ? $reflection->getMethod( 'setUp' ) : null;
	if ( $setUp ) {
		$setUp->setAccessible( true );
	}

	foreach ( $reflection->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
		if ( ! str_starts_with( $method->getName(), 'test_' ) ) {
			continue;
		}

		++$tests;
		$instance = $reflection->newInstance();

		try {
			if ( $setUp ) {
				$setUp->invoke( $instance );
			}
			$method->invoke( $instance );
			echo '.';
		} catch ( AssertionFailedError $e ) {
			echo 'F';
			$failures[] = sprintf( "%s::%s\n    %s", $class, $method->getName(), $e->getMessage() );
		} catch ( \Throwable $e ) {
			echo 'E';
			$failures[] = sprintf( "%s::%s\n    %s: %s", $class, $method->getName(), get_class( $e ), $e->getMessage() );
		}
	}
}

echo "\n\n";

if ( $failures ) {
	echo count( $failures ) . " FAILURES\n\n";
	echo implode( "\n\n", $failures ) . "\n\n";
	printf( "Tests: %d, Assertions: %d, Failures: %d\n", $tests, TestCase::$assertions, count( $failures ) );
	exit( 1 );
}

printf( "OK — Tests: %d, Assertions: %d\n", $tests, TestCase::$assertions );
exit( 0 );
