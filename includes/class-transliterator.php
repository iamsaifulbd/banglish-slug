<?php
/**
 * Bangla (Bengali script) to Banglish (Latin letters) transliterator.
 *
 * Works fully offline. No API key, no external request.
 *
 * @package Banglish_Slug
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'BANGLISH_SLUG_TESTING' ) ) {
	exit;
}

class Banglish_Slug_Transliterator {

	/** Independent vowels. */
	private static $vowels = array(
		'অ' => 'o',
		'আ' => 'a',
		'ই' => 'i',
		'ঈ' => 'i',
		'উ' => 'u',
		'ঊ' => 'u',
		'ঋ' => 'ri',
		'এ' => 'e',
		'ঐ' => 'oi',
		'ও' => 'o',
		'ঔ' => 'ou',
	);

	/** Dependent vowel signs (kar). */
	private static $kars = array(
		'া' => 'a',
		'ি' => 'i',
		'ী' => 'i',
		'ু' => 'u',
		'ূ' => 'u',
		'ৃ' => 'ri',
		'ে' => 'e',
		'ৈ' => 'oi',
		'ো' => 'o',
		'ৌ' => 'ou',
	);

	/** Consonants. */
	private static $consonants = array(
		'ক' => 'k',
		'খ' => 'kh',
		'গ' => 'g',
		'ঘ' => 'gh',
		'ঙ' => 'ng',
		'চ' => 'ch',
		'ছ' => 'chh',
		'জ' => 'j',
		'ঝ' => 'jh',
		'ঞ' => 'n',
		'ট' => 't',
		'ঠ' => 'th',
		'ড' => 'd',
		'ঢ' => 'dh',
		'ণ' => 'n',
		'ত' => 't',
		'থ' => 'th',
		'দ' => 'd',
		'ধ' => 'dh',
		'ন' => 'n',
		'প' => 'p',
		'ফ' => 'f',
		'ব' => 'b',
		'ভ' => 'bh',
		'ম' => 'm',
		'য' => 'j',
		'র' => 'r',
		'ল' => 'l',
		'শ' => 'sh',
		'ষ' => 'sh',
		'স' => 's',
		'হ' => 'h',
		"\u{09DC}" => 'r',  // ড়
		"\u{09DD}" => 'rh', // ঢ়
		"\u{09DF}" => 'y',  // য়
	);

	/** Signs that stand on their own. */
	private static $signs = array(
		'ৎ' => 't',
		'ং' => 'ng',
		'ঃ' => 'h',
		'ঁ' => '',
		'।' => ' ',
		'॥' => ' ',
	);

	/** Bangla digits. */
	private static $digits = array(
		'০' => '0',
		'১' => '1',
		'২' => '2',
		'৩' => '3',
		'৪' => '4',
		'৫' => '5',
		'৬' => '6',
		'৭' => '7',
		'৮' => '8',
		'৯' => '9',
	);

	const HASANTA = '্';
	const NUKTA   = '়';

	/**
	 * Does the text contain any Bangla character?
	 *
	 * @param string $text Input.
	 * @return bool
	 */
	public static function has_bangla( $text ) {
		return (bool) preg_match( '/[\x{0980}-\x{09FF}]/u', (string) $text );
	}

	/**
	 * Transliterate Bangla text into Banglish. Non-Bangla characters pass through.
	 *
	 * @param string $text Input text.
	 * @return string
	 */
	public static function transliterate( $text ) {
		$text = (string) $text;
		if ( '' === $text ) {
			return '';
		}

		// Fold decomposed nukta forms into the precomposed letters.
		$text = str_replace(
			array( "\u{09A1}\u{09BC}", "\u{09A2}\u{09BC}", "\u{09AF}\u{09BC}" ),
			array( "\u{09DC}", "\u{09DD}", "\u{09DF}" ),
			$text
		);

		$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
		$count = count( $chars );
		$out   = '';

		for ( $i = 0; $i < $count; $i++ ) {
			$ch = $chars[ $i ];

			if ( isset( self::$consonants[ $ch ] ) ) {
				$next = ( $i + 1 < $count ) ? $chars[ $i + 1 ] : '';

				// য-ফলা (consonant + hasanta + য) reads as "y".
				if ( self::HASANTA === $next && isset( $chars[ $i + 2 ] ) && 'য' === $chars[ $i + 2 ] ) {
					$out .= self::$consonants[ $ch ] . 'y';
					$i   += 2;
					$out .= self::inherent_vowel( $chars, $i, $count );
					continue;
				}

				$out .= self::$consonants[ $ch ];
				$out .= self::inherent_vowel( $chars, $i, $count );
				continue;
			}

			if ( isset( self::$kars[ $ch ] ) ) {
				$out .= self::$kars[ $ch ];
				continue;
			}

			if ( isset( self::$vowels[ $ch ] ) ) {
				$out .= self::$vowels[ $ch ];
				continue;
			}

			if ( isset( self::$signs[ $ch ] ) ) {
				$out .= self::$signs[ $ch ];
				continue;
			}

			if ( isset( self::$digits[ $ch ] ) ) {
				$out .= self::$digits[ $ch ];
				continue;
			}

			if ( self::HASANTA === $ch || self::NUKTA === $ch || "\u{200C}" === $ch || "\u{200D}" === $ch ) {
				continue; // Joiners and hasanta add no sound of their own.
			}

			$out .= $ch;
		}

		return $out;
	}

	/**
	 * Inherent "o" after a consonant at position $i.
	 *
	 * Added only when the next character is another consonant or ং/ঃ,
	 * never at the end of a word, before a kar or before a hasanta.
	 *
	 * @param array $chars Characters.
	 * @param int   $i     Index of the consonant (or last char of its cluster).
	 * @param int   $count Total characters.
	 * @return string
	 */
	private static function inherent_vowel( $chars, $i, $count ) {
		$next = ( $i + 1 < $count ) ? $chars[ $i + 1 ] : '';
		if ( '' === $next ) {
			return '';
		}
		if ( isset( self::$kars[ $next ] ) || self::HASANTA === $next ) {
			return '';
		}
		if ( 'ং' === $next || 'ঃ' === $next ) {
			return 'o';
		}
		if ( isset( self::$consonants[ $next ] ) ) {
			return 'o';
		}
		return '';
	}

	/**
	 * Build a URL slug (lowercase, hyphenated, ASCII only).
	 *
	 * @param string $text Title.
	 * @return string
	 */
	public static function slugify( $text ) {
		$latin = strtolower( self::transliterate( $text ) );
		$latin = preg_replace( '/[^a-z0-9]+/', '-', $latin );
		return trim( (string) $latin, '-' );
	}
}
