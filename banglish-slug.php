<?php
/**
 * Plugin Name:       Banglish Slug
 * Plugin URI:        https://github.com/iamsaifulbd/banglish-slug
 * Description:       Turns Bangla post, page and term titles into clean Banglish (English letter) URL slugs. Works offline, no API key. Includes a bulk tool to fix old Bangla slugs with automatic redirects.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Shaiful Islam
 * Author URI:        https://iamsaiful.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       banglish-slug
 *
 * @package Banglish_Slug
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BANGLISH_SLUG_VERSION', '1.0.0' );
define( 'BANGLISH_SLUG_OPTION', 'banglish_slug_settings' );

require_once __DIR__ . '/includes/class-transliterator.php';

/**
 * Plugin settings with defaults.
 *
 * @return array
 */
function banglish_slug_settings() {
	$defaults = array(
		'posts' => 1, // Posts, pages and custom post types.
		'terms' => 1, // Categories, tags and custom taxonomies.
	);
	$saved = get_option( BANGLISH_SLUG_OPTION, array() );
	return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
}

/**
 * Transliterate titles that contain Bangla before WordPress builds the slug.
 *
 * Runs only in the "save" context, so it never changes how existing
 * URLs are looked up. A slug the user types by hand in English is kept.
 *
 * @param string $title     Sanitized title so far.
 * @param string $raw_title Original title.
 * @param string $context   Sanitize context.
 * @return string
 */
function banglish_slug_filter_title( $title, $raw_title = '', $context = 'display' ) {
	if ( 'save' !== $context ) {
		return $title;
	}

	$source = '' !== (string) $raw_title ? $raw_title : $title;

	// WordPress may pass an already percent-encoded slug.
	if ( false !== strpos( $source, '%' ) ) {
		$source = rawurldecode( $source );
	}

	if ( ! Banglish_Slug_Transliterator::has_bangla( $source ) ) {
		return $title;
	}

	$settings = banglish_slug_settings();
	$is_term  = banglish_slug_called_from_term();

	if ( $is_term && empty( $settings['terms'] ) ) {
		return $title;
	}
	if ( ! $is_term && empty( $settings['posts'] ) ) {
		return $title;
	}

	$slug = Banglish_Slug_Transliterator::slugify( $source );
	return '' !== $slug ? $slug : $title;
}
add_filter( 'sanitize_title', 'banglish_slug_filter_title', 9, 3 );

/**
 * Is the slug being built for a category, tag or other term?
 * Only checked when the title holds Bangla, so the cost is small.
 *
 * @return bool
 */
function banglish_slug_called_from_term() {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
	foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 15 ) as $frame ) {
		if ( isset( $frame['function'] ) && in_array( $frame['function'], array( 'wp_insert_term', 'wp_update_term', 'wp_unique_term_slug' ), true ) ) {
			return true;
		}
	}
	return false;
}

/* ---------------------------------------------------------------------------
 * Admin: settings + bulk fixer (Tools → Banglish Slug)
 * ------------------------------------------------------------------------ */

/**
 * Register the admin page.
 */
function banglish_slug_admin_menu() {
	add_management_page(
		__( 'Banglish Slug', 'banglish-slug' ),
		__( 'Banglish Slug', 'banglish-slug' ),
		'manage_options',
		'banglish-slug',
		'banglish_slug_render_page'
	);
}
add_action( 'admin_menu', 'banglish_slug_admin_menu' );

/**
 * Add a "Settings" link on the Plugins screen.
 *
 * @param array $links Links.
 * @return array
 */
function banglish_slug_action_links( $links ) {
	$url = admin_url( 'tools.php?page=banglish-slug' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'banglish-slug' ) . '</a>' );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'banglish_slug_action_links' );

/**
 * Find posts whose slug still holds Bangla (stored percent-encoded).
 *
 * Bangla UTF-8 bytes start with E0 A6 or E0 A7.
 *
 * @param int $limit Max rows.
 * @return array
 */
function banglish_slug_find_posts( $limit = 50 ) {
	global $wpdb;

	$types = get_post_types( array( 'public' => true ) );
	unset( $types['attachment'] );
	if ( empty( $types ) ) {
		return array();
	}

	$type_in = implode( ',', array_fill( 0, count( $types ), '%s' ) );
	$like_a  = '%' . $wpdb->esc_like( '%e0%a6' ) . '%';
	$like_b  = '%' . $wpdb->esc_like( '%e0%a7' ) . '%';

	// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.DirectDatabaseQuery
	$sql = $wpdb->prepare(
		"SELECT ID, post_title, post_name, post_type, post_status
		 FROM {$wpdb->posts}
		 WHERE post_type IN ($type_in)
		   AND post_status IN ('publish','future','draft','pending','private')
		   AND ( LOWER(post_name) LIKE %s OR LOWER(post_name) LIKE %s )
		 ORDER BY ID DESC
		 LIMIT %d",
		array_merge( array_values( $types ), array( $like_a, $like_b, (int) $limit ) )
	);

	return $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
}

/**
 * Convert one post's slug. WordPress itself stores the old slug
 * (_wp_old_slug) for published posts, so old links redirect.
 *
 * @param WP_Post|object $post Post row.
 * @return string|false New slug, or false.
 */
function banglish_slug_fix_post( $post ) {
	$source = rawurldecode( $post->post_name );
	if ( ! Banglish_Slug_Transliterator::has_bangla( $source ) ) {
		$source = $post->post_title;
	}

	$slug = Banglish_Slug_Transliterator::slugify( $source );
	if ( '' === $slug ) {
		return false;
	}

	$slug = wp_unique_post_slug( $slug, $post->ID, $post->post_status, $post->post_type, (int) wp_get_post_parent_id( $post->ID ) );

	$result = wp_update_post(
		array(
			'ID'        => (int) $post->ID,
			'post_name' => $slug,
		),
		true
	);

	return is_wp_error( $result ) ? false : $slug;
}

/**
 * Handle form posts on the admin page.
 *
 * @return string Notice HTML.
 */
function banglish_slug_handle_actions() {
	if ( empty( $_POST['banglish_slug_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return '';
	}
	check_admin_referer( 'banglish_slug_admin' );

	$action = sanitize_key( wp_unslash( $_POST['banglish_slug_action'] ) );

	if ( 'save_settings' === $action ) {
		update_option(
			BANGLISH_SLUG_OPTION,
			array(
				'posts' => empty( $_POST['posts'] ) ? 0 : 1,
				'terms' => empty( $_POST['terms'] ) ? 0 : 1,
			)
		);
		return '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'banglish-slug' ) . '</p></div>';
	}

	if ( 'fix_posts' === $action ) {
		$ids   = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array();
		$fixed = 0;
		foreach ( $ids as $id ) {
			$post = get_post( $id );
			if ( $post && current_user_can( 'edit_post', $id ) && banglish_slug_fix_post( $post ) ) {
				$fixed++;
			}
		}
		/* translators: %d: number of posts */
		$msg = sprintf( _n( '%d slug converted. Old URLs of published posts redirect to the new ones.', '%d slugs converted. Old URLs of published posts redirect to the new ones.', $fixed, 'banglish-slug' ), $fixed );
		return '<div class="notice notice-success"><p>' . esc_html( $msg ) . '</p></div>';
	}

	return '';
}

/**
 * Render Tools → Banglish Slug.
 */
function banglish_slug_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice   = banglish_slug_handle_actions();
	$settings = banglish_slug_settings();
	$rows     = banglish_slug_find_posts( 50 );
	$sample   = 'আমার সোনার বাংলা';
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Banglish Slug', 'banglish-slug' ); ?></h1>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<p>
			<?php esc_html_e( 'Example:', 'banglish-slug' ); ?>
			<code><?php echo esc_html( $sample ); ?></code> →
			<code><?php echo esc_html( Banglish_Slug_Transliterator::slugify( $sample ) ); ?></code>
		</p>

		<h2><?php esc_html_e( 'Settings', 'banglish-slug' ); ?></h2>
		<form method="post">
			<?php wp_nonce_field( 'banglish_slug_admin' ); ?>
			<input type="hidden" name="banglish_slug_action" value="save_settings">
			<p><label><input type="checkbox" name="posts" value="1" <?php checked( $settings['posts'] ); ?>> <?php esc_html_e( 'Posts, pages and custom post types', 'banglish-slug' ); ?></label></p>
			<p><label><input type="checkbox" name="terms" value="1" <?php checked( $settings['terms'] ); ?>> <?php esc_html_e( 'Categories, tags and custom taxonomies', 'banglish-slug' ); ?></label></p>
			<?php submit_button( __( 'Save settings', 'banglish-slug' ) ); ?>
		</form>

		<h2><?php esc_html_e( 'Fix old Bangla slugs', 'banglish-slug' ); ?></h2>
		<?php if ( empty( $rows ) ) : ?>
			<p><?php esc_html_e( 'No posts with Bangla slugs found.', 'banglish-slug' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'These posts still have Bangla (percent-encoded) slugs. Showing up to 50 at a time. Take a database backup before converting.', 'banglish-slug' ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'banglish_slug_admin' ); ?>
				<input type="hidden" name="banglish_slug_action" value="fix_posts">
				<table class="widefat striped">
					<thead>
						<tr>
							<td class="check-column"><input type="checkbox" onclick="document.querySelectorAll('.bs-id').forEach(function(c){c.checked=this.checked}.bind(this))"></td>
							<th><?php esc_html_e( 'Title', 'banglish-slug' ); ?></th>
							<th><?php esc_html_e( 'Current slug', 'banglish-slug' ); ?></th>
							<th><?php esc_html_e( 'New slug', 'banglish-slug' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<th class="check-column"><input class="bs-id" type="checkbox" name="ids[]" value="<?php echo esc_attr( $row->ID ); ?>" checked></th>
							<td><a href="<?php echo esc_url( get_edit_post_link( $row->ID ) ); ?>"><?php echo esc_html( $row->post_title ); ?></a> <small>(<?php echo esc_html( $row->post_type ); ?>)</small></td>
							<td><code><?php echo esc_html( rawurldecode( $row->post_name ) ); ?></code></td>
							<td><code><?php echo esc_html( Banglish_Slug_Transliterator::slugify( rawurldecode( $row->post_name ) ) ); ?></code></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php submit_button( __( 'Convert selected slugs', 'banglish-slug' ) ); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
