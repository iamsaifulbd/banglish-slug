<?php
/**
 * Run: php tests/test-transliterator.php
 */

define( 'BANGLISH_SLUG_TESTING', true );
require __DIR__ . '/../includes/class-transliterator.php';

$cases = array(
	'বাংলাদেশ'              => 'bangladesh',
	'পৃথিবী'                 => 'prithibi',
	'আমার সোনার বাংলা'      => 'amar-sonar-bangla',
	'মানুষ'                  => 'manush',
	'কলম'                   => 'kolom',
	'ঢাকা ২০২৬'              => 'dhaka-2026',
	'রং'                    => 'rong',
	'বিদ্যালয়'               => 'bidyaloy',
	'ছুটির নিয়ম'             => 'chhutir-niyom',
	'WordPress গাইড'         => 'wordpress-gaid',
	'চা-বাগান'               => 'cha-bagan',
	'ঈদ মোবারক!'            => 'id-mobarok',
	'ভালোবাসা'               => 'bhalobasa',
	'হ্যালো'                  => 'hyalo',
);

$fail = 0;
foreach ( $cases as $in => $want ) {
	$got = Banglish_Slug_Transliterator::slugify( $in );
	$ok  = $got === $want;
	if ( ! $ok ) {
		$fail++;
	}
	printf( "%s  %-22s => %-20s %s\n", $ok ? 'PASS' : 'FAIL', $in, $got, $ok ? '' : "(want $want)" );
}

if ( Banglish_Slug_Transliterator::has_bangla( 'hello' ) || ! Banglish_Slug_Transliterator::has_bangla( 'hi বাংলা' ) ) {
	$fail++;
	echo "FAIL  has_bangla\n";
}

echo $fail ? "\n$fail failed\n" : "\nAll passed\n";
exit( $fail ? 1 : 0 );
