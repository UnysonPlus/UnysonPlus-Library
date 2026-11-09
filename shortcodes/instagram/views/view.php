<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * @var array $atts
 *
 * Instagram Feed: a profile header (picture, name, @handle, Follow) and the posts as a grid or a
 * paged slider. Every tile is a plain link rendered here on the server; the slider script only adds
 * the arrows, so the feed works (as a horizontally scrollable strip) without JavaScript.
 */

/*
 * Pages saved with v1.x of this element stored flat options: username, access_token, count,
 * columns, gap (a Gap Scale slug), aspect {ratio}, rounding, max_width, align, hover_zoom,
 * link_to_post, new_tab. Map them onto the v2 options so those pages keep their feed.
 */
$legacy = ! isset( $atts['source'] ) && ( isset( $atts['access_token'] ) || isset( $atts['username'] ) );
if ( $legacy ) {
	$atts['source']         = array( 'mode' => 'account', 'account' => array( 'access_token' => (string) ( $atts['access_token'] ?? '' ), 'cache_hours' => '1' ) );
	$atts['profile_handle'] = (string) ( $atts['profile_handle'] ?? ( $atts['username'] ?? '' ) );
	$atts['layout']         = 'grid';
	$atts['frame']          = 'no';
	$atts['count']          = (string) ( $atts['count'] ?? '6' );
	$atts['columns']        = (string) ( $atts['columns'] ?? '3' );
	$atts['hover']          = ( isset( $atts['hover_zoom'] ) && 'no' === $atts['hover_zoom'] ) ? 'none' : 'zoom';
	$old_ratio              = (string) ( $atts['aspect']['ratio'] ?? '1-1' );
	$atts['ratio']          = in_array( $old_ratio, array( '1-1', '4-5', '9-16' ), true ) ? $old_ratio : '1-1';
}
$link_posts = ! ( $legacy && isset( $atts['link_to_post'] ) && 'no' === $atts['link_to_post'] );

// Gap: v2 stores pixels; v1 stored a Gap Scale slug from Theme Settings.
if ( ! function_exists( 'upw_instagram_no_auto_sizes' ) ) {
	/** Removes WordPress's "sizes=auto" from feed tiles: auto picks by tile width and ignores the cover crop. */
	function upw_instagram_no_auto_sizes( $image ) {
		if ( strpos( $image, 'igf__img' ) === false ) {
			return $image;
		}
		return preg_replace( '/\ssizes="auto,\s*/i', ' sizes="', $image );
	}
}

if ( ! function_exists( 'upw_instagram_gap_value' ) ) {
	/** Resolves a Gap Scale slug (or a plain number of pixels) to a CSS length; '' when none. */
	function upw_instagram_gap_value( $slug ) {
		$slug = (string) $slug;
		if ( '' === $slug ) {
			return '';
		}
		if ( is_numeric( $slug ) ) {
			return max( 0, min( 40, (int) $slug ) ) . 'px';
		}
		if ( function_exists( 'unysonplus_get_gap_scale' ) ) {
			foreach ( unysonplus_get_gap_scale() as $e ) {
				if ( ! is_array( $e ) || empty( $e['name'] ) ) {
					continue;
				}
				$es = strtolower( function_exists( 'sc_sanitize_class' ) ? sc_sanitize_class( $e['name'] ) : sanitize_title( $e['name'] ) );
				if ( $es === $slug ) {
					return isset( $e['size'] ) ? (string) $e['size'] : '';
				}
			}
		}
		return '';
	}
}

$mode    = fw_akg( 'source/mode', $atts, 'account' );
$count   = max( 1, min( 50, (int) fw_akg( 'count', $atts, 16 ) ) );
$profile = array( 'username' => '', 'name' => '', 'avatar' => '' );
$items   = array();
$error   = '';

if ( $mode === 'manual' ) {
	foreach ( (array) fw_akg( 'source/manual/posts', $atts, array() ) as $p ) {
		$img = fw_akg( 'image', $p, array() );
		$url = is_array( $img ) ? (string) ( $img['url'] ?? '' ) : '';
		if ( $url === '' ) {
			continue;
		}
		$aid = is_array( $img ) ? (int) ( $img['attachment_id'] ?? 0 ) : 0;
		$alt = trim( (string) fw_akg( 'alt', $p, '' ) );
		if ( $alt === '' && $aid ) {
			$alt = (string) get_post_meta( $aid, '_wp_attachment_image_alt', true );
		}
		$items[] = array(
			'type'          => in_array( fw_akg( 'kind', $p, 'image' ), array( 'video', 'album' ), true ) ? fw_akg( 'kind', $p, 'image' ) : 'image',
			'image'         => $url,
			'attachment_id' => $aid,
			'link'          => trim( (string) fw_akg( 'link', $p, '' ) ),
			'caption'       => $alt,
		);
	}
	$items = array_slice( $items, 0, $count );
} elseif ( class_exists( 'FW_Shortcode_Instagram' ) ) {
	$feed    = FW_Shortcode_Instagram::get_feed( (string) fw_akg( 'source/account/access_token', $atts, '' ), $count, (int) fw_akg( 'source/account/cache_hours', $atts, 6 ) );
	$profile = array_merge( $profile, (array) ( $feed['profile'] ?? array() ) );
	$items   = array_slice( (array) ( $feed['items'] ?? array() ), 0, $count );
	$error   = (string) ( $feed['error'] ?? '' );
}

// The element's own header fields win over what the account reports.
$handle = ltrim( trim( (string) fw_akg( 'profile_handle', $atts, '' ) ), '@' );
if ( $handle === '' ) {
	$handle = (string) $profile['username'];
}
$name = trim( (string) fw_akg( 'profile_name', $atts, '' ) );
if ( $name === '' ) {
	$name = $profile['name'] !== '' ? (string) $profile['name'] : $handle;
}
$avatar = fw_akg( 'profile_avatar/url', $atts, '' );
if ( $avatar === '' ) {
	$avatar = (string) $profile['avatar'];
}
$profile_url = $handle !== '' ? 'https://www.instagram.com/' . rawurlencode( $handle ) . '/' : '';

$new_tab     = fw_akg( 'new_tab', $atts, 'yes' ) === 'yes';
$target_html = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
$layout      = fw_akg( 'layout', $atts, 'slider' ) === 'grid' ? 'grid' : 'slider';
$rows        = max( 1, min( 3, (int) fw_akg( 'rows', $atts, 2 ) ) );
$cols        = max( 1, min( 6, (int) fw_akg( 'columns', $atts, 4 ) ) );
$cols_t      = max( 1, min( 6, (int) fw_akg( 'columns_tablet', $atts, 3 ) ) );
$cols_m      = max( 1, min( 6, (int) fw_akg( 'columns_mobile', $atts, 2 ) ) );
$ratio       = in_array( fw_akg( 'ratio', $atts, '1-1' ), array( '1-1', '4-5', '9-16' ), true ) ? fw_akg( 'ratio', $atts, '1-1' ) : '1-1';
$gap_css     = upw_instagram_gap_value( fw_akg( 'gap', $atts, '0' ) );
$gap_css     = $gap_css !== '' ? $gap_css : '0px';
$radius_map  = array( 'none' => '0px', 'small' => '6px', 'medium' => '12px', 'large' => '20px' );
$radius      = $radius_map[ (string) fw_akg( 'rounding', $atts, 'none' ) ] ?? '0px';
$mw          = (array) fw_akg( 'max_width', $atts, array() );
$mw_value    = trim( (string) ( $mw['value'] ?? '' ) );
$mw_unit     = preg_replace( '/[^a-z%]/', '', (string) ( $mw['unit'] ?? 'px' ) );
$max_width   = ( $mw_value !== '' && is_numeric( $mw_value ) ) ? $mw_value . ( $mw_unit ?: 'px' ) : '';
$align       = in_array( fw_akg( 'align', $atts, 'center' ), array( 'left', 'center', 'right' ), true ) ? fw_akg( 'align', $atts, 'center' ) : 'center';
$hover       = in_array( fw_akg( 'hover', $atts, 'zoom' ), array( 'zoom', 'darken', 'none' ), true ) ? fw_akg( 'hover', $atts, 'zoom' ) : 'zoom';
$type_icon   = fw_akg( 'show_type_icon', $atts, 'yes' ) === 'yes';
$frame       = fw_akg( 'frame', $atts, 'yes' ) === 'yes';

$atts['base_class']       = 'igf';
$atts['unique_id_prefix'] = 'igf-';
$attr                     = sc_build_wrapper_attr( $atts );
$attr['class']            = trim( ( $attr['class'] ?? '' ) . ' igf--' . $layout . ' igf--hover-' . $hover . ( $frame ? ' igf--frame' : '' ) );
$attr['style']            = trim( ( $attr['style'] ?? '' ) . sprintf( '--igf-cols:%d;--igf-cols-t:%d;--igf-cols-m:%d;--igf-rows:%d;--igf-gap:%s;--igf-ratio:%s;--igf-radius:%s;', $cols, $cols_t, $cols_m, $rows, $gap_css, str_replace( '-', '/', $ratio ), $radius ) );
if ( $max_width !== '' ) {
	$attr['style'] .= 'max-width:' . $max_width . ';' . ( 'left' === $align ? 'margin-right:auto;' : ( 'right' === $align ? 'margin-left:auto;' : 'margin-left:auto;margin-right:auto;' ) );
}

if ( ! $items ) {
	// Visitors see nothing; editors see why.
	if ( function_exists( 'fw_is_editor_context' ) && fw_is_editor_context() || current_user_can( 'edit_posts' ) ) {
		$why = $mode === 'manual'
			? __( 'Instagram Feed: add some images to show here.', 'fw' )
			: ( $error === 'no_token'
				? __( 'Instagram Feed: paste an access token for a Business or Creator account to show its posts.', 'fw' )
				: sprintf( __( 'Instagram Feed: no posts could be loaded yet (%s). Check the access token; the feed tries again within 30 minutes.', 'fw' ), $error !== '' ? $error : __( 'no response', 'fw' ) ) );
		echo '<div ' . fw_attr_to_html( $attr ) . '><p class="igf__note">' . esc_html( $why ) . '</p></div>';
	}
	return;
}

$icons = array(
	'video' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4 3h16a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H4a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Zm0 2a1 1 0 0 0-1 1v1.5h4.6L9.2 5H4Zm7.6 0L10 7.5h4.4L16 5h-4.4Zm6.8 0-1.6 2.5H21V6a1 1 0 0 0-1-1h-1.6ZM3 9.5V18a1 1 0 0 0 1 1h16a1 1 0 0 0 1-1V9.5H3Zm7 1.8 5 2.95-5 2.95v-5.9Z"/></svg>',
	'album' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M7 2h12a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3Zm0 2a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H7ZM2 7v12a3 3 0 0 0 3 3h12v-2H5a1 1 0 0 1-1-1V7H2Z"/></svg>',
);
$ig_glyph = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2.2c3.2 0 3.6 0 4.8.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4.9.4.4.7.9.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.9.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.9-.9-1.4-.2-.4-.4-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8c.1-1.2.2-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.9-.7 1.4-.9.4-.2 1-.4 2.2-.4C8.4 2.2 8.8 2.2 12 2.2Zm0 4.6a5.2 5.2 0 1 0 0 10.4 5.2 5.2 0 0 0 0-10.4Zm0 8.6a3.4 3.4 0 1 1 0-6.8 3.4 3.4 0 0 1 0 6.8Zm5.4-9.9a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4Z"/></svg>';

$btn_preset = trim( (string) fw_akg( 'button_preset', $atts, '' ) );
$btn_class  = 'igf__follow' . ( $btn_preset !== '' ? ' igf__follow--preset btn ' . implode( ' ', array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', $btn_preset ) ) ) ) : '' );
$follow     = trim( (string) fw_akg( 'follow_label', $atts, __( 'Follow', 'fw' ) ) );
$label_id   = 'igf-label-' . substr( md5( wp_json_encode( $attr ) ), 0, 8 );
?>
<div <?php echo fw_attr_to_html( $attr ); ?>>
	<?php if ( fw_akg( 'show_header', $atts, 'yes' ) === 'yes' && ( $name !== '' || $handle !== '' ) ) : ?>
		<div class="igf__head">
			<?php if ( $profile_url !== '' ) : ?><a class="igf__who" href="<?php echo esc_url( $profile_url ); ?>"<?php echo $target_html; ?>><?php else : ?><div class="igf__who"><?php endif; ?>
				<span class="igf__avatar">
					<?php if ( $avatar !== '' ) : ?>
						<img src="<?php echo esc_url( $avatar ); ?>" alt="" width="56" height="56" loading="lazy" decoding="async" />
					<?php else : ?>
						<?php echo $ig_glyph; // static SVG ?>
					<?php endif; ?>
				</span>
				<span class="igf__names">
					<span class="igf__name" id="<?php echo esc_attr( $label_id ); ?>"><?php echo esc_html( $name ); ?></span>
					<?php if ( $handle !== '' ) : ?><span class="igf__handle">@<?php echo esc_html( $handle ); ?></span><?php endif; ?>
				</span>
			<?php if ( $profile_url !== '' ) : ?></a><?php else : ?></div><?php endif; ?>
			<?php if ( $profile_url !== '' && $follow !== '' ) : ?>
				<a class="<?php echo esc_attr( $btn_class ); ?>" href="<?php echo esc_url( $profile_url ); ?>"<?php echo $target_html; ?>>
					<?php if ( $btn_preset === '' ) : ?><span class="igf__follow-icon"><?php echo $ig_glyph; // static SVG ?></span><?php endif; ?>
					<span><?php echo esc_html( $follow ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="igf__viewport">
		<ul class="igf__track" role="list"<?php echo $name !== '' ? ' aria-labelledby="' . esc_attr( $label_id ) . '"' : ''; ?>>
			<?php foreach ( $items as $i => $it ) :
				$link = ! $link_posts ? '' : ( $it['link'] !== '' ? $it['link'] : $profile_url );
				$alt  = $it['caption'] !== '' ? wp_trim_words( $it['caption'], 18, '…' ) : ( $name !== '' ? sprintf( __( 'Instagram post by %s', 'fw' ), $name ) : __( 'Instagram post', 'fw' ) );
				$img = '<img class="igf__img" src="' . esc_url( $it['image'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" />';
				if ( ! empty( $it['attachment_id'] ) ) {
					// The tile crops the image to its ratio (object-fit: cover), so a landscape photo in a
					// portrait tile shows at more than the tile's width. Ask for that width, not the tile's,
					// and keep WordPress's "sizes=auto" off: auto picks by tile width and the crop goes soft.
					$meta   = wp_get_attachment_metadata( (int) $it['attachment_id'] );
					$aspect = ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) ? $meta['width'] / $meta['height'] : 1;
					$parts  = explode( '-', $ratio );
					$factor = max( 1, ( (float) $parts[1] / (float) $parts[0] ) * $aspect );
					$vw     = function ( $c ) use ( $factor ) { return (int) ceil( 100 / max( 1, $c ) * $factor ) . 'vw'; };
					$sizes  = '(max-width: 599.98px) ' . $vw( $cols_m ) . ', (max-width: 991.98px) ' . $vw( $cols_t ) . ', ' . $vw( $cols );
					add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
					$img = wp_get_attachment_image( (int) $it['attachment_id'], 'full', false, array( 'class' => 'igf__img', 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => $sizes ) );
					remove_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
					// The page content filter adds "auto" again later; take it back off these tiles only.
					if ( ! has_filter( 'wp_content_img_tag', 'upw_instagram_no_auto_sizes' ) ) {
						add_filter( 'wp_content_img_tag', 'upw_instagram_no_auto_sizes' );
					}
				}
				?>
				<li class="igf__item">
					<?php if ( $link !== '' ) : ?><a class="igf__tile" href="<?php echo esc_url( $link ); ?>"<?php echo $target_html; ?>><?php else : ?><span class="igf__tile"><?php endif; ?>
						<?php echo $img; // escaped above / by WordPress ?>
						<?php if ( $type_icon && isset( $icons[ $it['type'] ] ) ) : ?><span class="igf__type"><?php echo $icons[ $it['type'] ]; // static SVG ?></span><?php endif; ?>
						<span class="igf__overlay" aria-hidden="true"><?php echo $ig_glyph; // static SVG ?></span>
					<?php if ( $link !== '' ) : ?></a><?php else : ?></span><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $layout === 'slider' ) : ?>
			<button type="button" class="igf__arrow igf__arrow--prev" aria-label="<?php esc_attr_e( 'Previous posts', 'fw' ); ?>" hidden><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M15.4 5.4 14 4l-8 8 8 8 1.4-1.4L8.8 12z"/></svg></button>
			<button type="button" class="igf__arrow igf__arrow--next" aria-label="<?php esc_attr_e( 'More posts', 'fw' ); ?>" hidden><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M8.6 18.6 10 20l8-8-8-8-1.4 1.4 6.6 6.6z"/></svg></button>
		<?php endif; ?>
	</div>
</div>
