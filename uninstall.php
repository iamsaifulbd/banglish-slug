<?php
/**
 * Remove plugin settings on uninstall. Slugs already converted stay as they are.
 *
 * @package Banglish_Slug
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'banglish_slug_settings' );
