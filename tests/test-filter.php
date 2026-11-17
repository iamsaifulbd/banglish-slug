<?php
/**
 * Checks the sanitize_title filter with small WordPress stubs.
 * Run: php tests/test-filter.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['bs_options'] = array();
function add_filter() {}
function add_action() {}
function plugin_basename( $f ) { return basename( $f ); }
function get_option( $k, $d = false ) { return $GLOBALS['bs_options'][ $k ] ?? $d; }
function wp_parse_args( $a, $d ) { return array_merge( $d, $a ); }

require __DIR__ . '/../banglish-slug.php';

function wp_insert_term( $name ) { return banglish_slug_filter_title( $name, $name, 'save' ); }

$fail  = 0;
$check = function ( $label, $got, $want ) use ( &$fail ) {
	$ok = $got === $want;
	$fail += $ok ? 0 : 1;
	printf( "%s  %-28s %s%s\n", $ok ? 'PASS' : 'FAIL', $label, $got, $ok ? '' : "  (want $want)" );
};

$check( 'post title, save context', banglish_slug_filter_title( 'x', 'আমার সোনার বাংলা', 'save' ), 'amar-sonar-bangla' );
$check( 'encoded slug', banglish_slug_filter_title( 'x', rawurlencode( 'বাংলা' ), 'save' ), 'bangla' );
$check( 'query context untouched', banglish_slug_filter_title( 'keep', 'বাংলা', 'query' ), 'keep' );
$check( 'english untouched', banglish_slug_filter_title( 'hello-world', 'Hello World', 'save' ), 'hello-world' );
$check( 'term', wp_insert_term( 'খেলাধুলা' ), 'kheladhula' );

$GLOBALS['bs_options'][ BANGLISH_SLUG_OPTION ] = array( 'posts' => 0, 'terms' => 1 );
$check( 'posts switched off', banglish_slug_filter_title( 'raw', 'বাংলা', 'save' ), 'raw' );
$check( 'terms still on', wp_insert_term( 'খেলা' ), 'khela' );

$GLOBALS['bs_options'][ BANGLISH_SLUG_OPTION ] = array( 'posts' => 1, 'terms' => 0 );
$check( 'terms switched off', wp_insert_term( 'খেলা' ), 'খেলা' );

echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit( $fail ? 1 : 0 );
