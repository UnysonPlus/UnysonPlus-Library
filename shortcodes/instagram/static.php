<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Front-end assets. This library element can live in a theme's framework-customizations (where the Shortcodes
 * Library installs it), in the update-safe uploads folder (Upload a .zip), or in a plugin, and static.php runs
 * without the shortcode object, so it works out its own URL from its folder.
 */
if ( ! function_exists( 'fw_sc_igf_dir_to_uri' ) ) {
	function fw_sc_igf_dir_to_uri( $dir ) {
		$dir  = wp_normalize_path( $dir );
		$maps = array();
		$up   = wp_upload_dir();
		if ( ! empty( $up['basedir'] ) ) {
			$maps[] = array( wp_normalize_path( $up['basedir'] ), rtrim( $up['baseurl'], '/' ) );
		}
		$maps[] = array( wp_normalize_path( get_stylesheet_directory() ), rtrim( get_stylesheet_directory_uri(), '/' ) );
		$maps[] = array( wp_normalize_path( get_template_directory() ), rtrim( get_template_directory_uri(), '/' ) );
		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			$maps[] = array( wp_normalize_path( WP_PLUGIN_DIR ), rtrim( plugins_url(), '/' ) );
		}
		foreach ( $maps as $m ) {
			if ( $m[0] !== '' && strpos( $dir, $m[0] ) === 0 ) {
				return $m[1] . substr( $dir, strlen( $m[0] ) );
			}
		}
		return rtrim( content_url(), '/' );
	}
}

$igf_uri = fw_sc_igf_dir_to_uri( dirname( __FILE__ ) );
$igf_ver = '2.0.0';

wp_enqueue_style( 'fw-shortcode-instagram-feed', $igf_uri . '/static/css/instagram.css', array(), $igf_ver );
wp_enqueue_script( 'fw-shortcode-instagram-feed', $igf_uri . '/static/js/instagram.js', array(), $igf_ver, true );
