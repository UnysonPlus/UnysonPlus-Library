<?php if ( ! defined( 'FW' ) ) { die( 'Forbidden' ); }
/**
 * Instagram feed view. Wraps in the standard element wrapper (sc_build_wrapper_attr →
 * scope class + CSS ID/Class + Position + Responsive-hide + `selector` Custom CSS), and
 * drives all layout/appearance via CSS variables so the Advanced → Custom CSS can override.
 *
 * @var array $atts
 */

if ( ! function_exists( 'upw_instagram_fetch' ) ) {
	/**
	 * Fetch a profile's recent media via the Instagram Graph API, server-side + transient-cached
	 * (1h), with a week-long "stale-while-error" backup so a transient outage doesn't blank the feed.
	 * Returns [ { image, permalink, caption, type } ], or [] with no token / no cache.
	 */
	function upw_instagram_fetch( $token, $count ) {
		$token = trim( (string) $token );
		$count = max( 1, min( 30, (int) $count ) );
		if ( '' === $token ) { return array(); }

		$key = 'upw_ig_' . md5( $token . '|' . $count );
		$bak = $key . '_bak';

		$cached = get_transient( $key );
		if ( is_array( $cached ) ) { return $cached; }

		$url = add_query_arg(
			array( 'fields' => 'id,caption,media_type,media_url,permalink,thumbnail_url', 'limit' => $count, 'access_token' => $token ),
			'https://graph.instagram.com/me/media'
		);
		$res = wp_remote_get( $url, array( 'timeout' => 12 ) );
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			$stale = get_transient( $bak );
			return is_array( $stale ) ? $stale : array();
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		$data = ( is_array( $body ) && isset( $body['data'] ) && is_array( $body['data'] ) ) ? $body['data'] : array();

		$items = array();
		foreach ( $data as $m ) {
			if ( ! is_array( $m ) ) { continue; }
			$type  = isset( $m['media_type'] ) ? (string) $m['media_type'] : 'IMAGE';
			$image = ( 'VIDEO' === $type && ! empty( $m['thumbnail_url'] ) ) ? $m['thumbnail_url'] : ( $m['media_url'] ?? '' );
			if ( '' === $image ) { continue; }
			$items[] = array(
				'image'     => esc_url_raw( (string) $image ),
				'permalink' => esc_url_raw( (string) ( $m['permalink'] ?? '' ) ),
				'caption'   => (string) ( $m['caption'] ?? '' ),
				'type'      => $type,
			);
			if ( count( $items ) >= $count ) { break; }
		}
		set_transient( $key, $items, HOUR_IN_SECONDS );
		set_transient( $bak, $items, WEEK_IN_SECONDS );
		return $items;
	}
}

if ( ! function_exists( 'upw_instagram_gap_value' ) ) {
	/** Resolve a Gap-Scale slug (from sc_get_gap_select_choices) to its CSS size, '' when none. */
	function upw_instagram_gap_value( $slug ) {
		$slug = (string) $slug;
		if ( '' === $slug ) { return ''; }
		if ( function_exists( 'unysonplus_get_gap_scale' ) ) {
			foreach ( unysonplus_get_gap_scale() as $e ) {
				if ( ! is_array( $e ) || empty( $e['name'] ) ) { continue; }
				$es = strtolower( function_exists( 'sc_sanitize_class' ) ? sc_sanitize_class( $e['name'] ) : sanitize_title( $e['name'] ) );
				if ( $es === $slug ) { return isset( $e['size'] ) ? (string) $e['size'] : ''; }
			}
		}
		return is_numeric( $slug ) ? $slug . 'px' : ''; // legacy numeric-px value
	}
}

/* --- resolve options --------------------------------------------------------------- */
$user  = isset( $atts['username'] ) ? sanitize_text_field( ltrim( (string) $atts['username'], '@' ) ) : '';
$token = isset( $atts['access_token'] ) ? (string) $atts['access_token'] : '';
$count = isset( $atts['count'] ) ? (int) $atts['count'] : 6;
$cols  = isset( $atts['columns'] ) ? max( 1, min( 6, (int) $atts['columns'] ) ) : 3;

$gap   = upw_instagram_gap_value( isset( $atts['gap'] ) ? $atts['gap'] : '' );
$gap   = '' !== $gap ? $gap : '0px';

$round_map = array( 'none' => '0', 'small' => '6px', 'medium' => '12px', 'large' => '20px', 'circle' => '50%' );
$round_key = isset( $atts['rounding'] ) ? (string) $atts['rounding'] : 'small';
$radius    = isset( $round_map[ $round_key ] ) ? $round_map[ $round_key ] : '6px';

$ar_map  = array( '1-1' => '1 / 1', '4-5' => '4 / 5', '3-4' => '3 / 4', '16-9' => '16 / 9' );
$ar_slug = isset( $atts['aspect']['ratio'] ) ? (string) $atts['aspect']['ratio'] : '1-1';
if ( 'custom' === $ar_slug ) {
	$aspect = isset( $atts['aspect']['aspect_custom'] ) ? trim( preg_replace( '/[^0-9\/.\s]/', '', (string) $atts['aspect']['aspect_custom'] ) ) : '';
} else {
	$aspect = ( 'auto' !== $ar_slug && isset( $ar_map[ $ar_slug ] ) ) ? $ar_map[ $ar_slug ] : '';
}

$mw    = ( isset( $atts['max_width'] ) && is_array( $atts['max_width'] ) ) ? $atts['max_width'] : array();
$mw_v  = isset( $mw['value'] ) ? trim( (string) $mw['value'] ) : '';
$mw_u  = isset( $mw['unit'] ) ? preg_replace( '/[^a-z%]/', '', (string) $mw['unit'] ) : 'px';
$max_w = ( '' !== $mw_v && is_numeric( $mw_v ) ) ? ( $mw_v . ( $mw_u ?: 'px' ) ) : '';
$align = isset( $atts['align'] ) && in_array( $atts['align'], array( 'left', 'center', 'right' ), true ) ? $atts['align'] : 'center';

$hover  = ( ! isset( $atts['hover_zoom'] ) || 'yes' === $atts['hover_zoom'] );
$cap    = ( isset( $atts['show_caption'] ) && 'yes' === $atts['show_caption'] );
$link   = ( ! isset( $atts['link_to_post'] ) || 'yes' === $atts['link_to_post'] );
$target = ( ! isset( $atts['new_tab'] ) || 'yes' === $atts['new_tab'] ) ? '_blank' : '_self';

$handle_color  = function_exists( 'sc_color_to_css' ) ? sc_color_to_css( isset( $atts['handle_color'] ) ? $atts['handle_color'] : '', '' ) : '';
$overlay_color = function_exists( 'sc_color_to_css' ) ? sc_color_to_css( isset( $atts['overlay_color'] ) ? $atts['overlay_color'] : '', '' ) : '';

/* --- CSS variables on the wrapper (Custom CSS can override any of them) ------------- */
$vars = '--ig-cols:' . (int) $cols . ';--ig-gap:' . $gap . ';--ig-radius:' . $radius . ';';
if ( '' !== $aspect )        { $vars .= '--ig-aspect:' . $aspect . ';'; }
if ( '' !== $overlay_color ) { $vars .= '--ig-overlay:' . $overlay_color . ';'; }
if ( '' !== $handle_color )  { $vars .= '--ig-handle:' . $handle_color . ';'; }
if ( '' !== $max_w ) {
	$vars .= 'max-width:' . $max_w . ';';
	$vars .= 'left' === $align ? 'margin-right:auto;' : ( 'right' === $align ? 'margin-left:auto;' : 'margin-left:auto;margin-right:auto;' );
}

/* --- standard element wrapper ------------------------------------------------------ */
$atts['base_class'] = 'upw-instagram';
$attr = function_exists( 'sc_build_wrapper_attr' ) ? sc_build_wrapper_attr( $atts ) : array( 'class' => 'upw-instagram' );
$classes = ( isset( $attr['class'] ) ? $attr['class'] : '' );
if ( ! $hover ) { $classes .= ' upw-instagram--no-zoom'; }
if ( $aspect === '' ) { $classes .= ' upw-instagram--natural'; }
$attr['class'] = trim( $classes );
$attr['style'] = ( isset( $attr['style'] ) ? rtrim( $attr['style'], ';' ) . ';' : '' ) . $vars;

$attr_html = '';
foreach ( $attr as $k => $v ) { $attr_html .= ' ' . $k . '="' . esc_attr( $v ) . '"'; }

$items = upw_instagram_fetch( $token, $count );
?>
<div<?php echo $attr_html; // phpcs:ignore ?>>
	<?php if ( '' !== $user ) : ?>
		<a class="upw-instagram__handle" href="<?php echo esc_url( 'https://instagram.com/' . rawurlencode( $user ) ); ?>" target="<?php echo esc_attr( $target ); ?>" rel="noopener noreferrer">@<?php echo esc_html( $user ); ?></a>
	<?php endif; ?>

	<?php if ( ! empty( $items ) ) : ?>
		<div class="upw-instagram__grid">
			<?php foreach ( $items as $it ) :
				$tag   = ( $link && '' !== $it['permalink'] ) ? 'a' : 'div';
				$attrs = ( 'a' === $tag ) ? ' href="' . esc_url( $it['permalink'] ) . '" target="' . esc_attr( $target ) . '" rel="noopener noreferrer"' : '';
				$capt  = trim( wp_strip_all_tags( $it['caption'] ) );
				?>
				<<?php echo $tag . $attrs; // phpcs:ignore ?> class="upw-instagram__tile">
					<img src="<?php echo esc_url( $it['image'] ); ?>" alt="<?php echo esc_attr( '' !== $capt ? wp_trim_words( $capt, 12, '…' ) : ( 'Instagram post' . ( $user ? ' by @' . $user : '' ) ) ); ?>" loading="lazy" />
					<?php if ( $cap && '' !== $capt ) : ?>
						<span class="upw-instagram__cap"><?php echo esc_html( wp_trim_words( $capt, 20, '…' ) ); ?></span>
					<?php endif; ?>
				</<?php echo $tag; // phpcs:ignore ?>>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<?php $ph = max( 1, min( 12, $count ) ); ?>
		<div class="upw-instagram__grid upw-instagram__grid--placeholder">
			<?php for ( $i = 0; $i < $ph; $i++ ) : ?>
				<span class="upw-instagram__tile upw-instagram__tile--ph" style="opacity:<?php echo esc_attr( 0.55 + 0.06 * ( $i % 5 ) ); ?>;"></span>
			<?php endfor; ?>
		</div>
		<p class="upw-instagram__note">
			<?php echo esc_html( '' === trim( $token ) ? __( 'Add an Instagram Access Token to display your feed.', 'fw' ) : __( 'Instagram feed is temporarily unavailable.', 'fw' ) ); ?>
		</p>
	<?php endif; ?>
</div>
