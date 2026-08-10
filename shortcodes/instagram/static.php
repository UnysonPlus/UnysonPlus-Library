<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }

/**
 * Enqueue the Instagram feed's own scoped stylesheet.
 *
 * A Library shortcode can be installed EITHER in the plugin (core) OR in a theme's
 * framework-customizations dir, so the extension's get_declared_URI() (which points at
 * the core path) can't locate it. Instead derive the URL from THIS file's own location
 * (`__FILE__`) relative to wp-content — correct wherever the shortcode was downloaded to.
 */

$upw_ig_css = dirname( __FILE__ ) . '/static/css/instagram.css';
if ( file_exists( $upw_ig_css ) ) {
	$upw_ig_rel = str_replace( wp_normalize_path( WP_CONTENT_DIR ), '', wp_normalize_path( $upw_ig_css ) );
	wp_enqueue_style( 'upw-shortcode-instagram', content_url( $upw_ig_rel ), array(), '1.0.0' );
}
