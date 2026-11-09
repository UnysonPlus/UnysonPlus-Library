<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

/**
 * Instagram Feed (library element `instagram`, v2.0.0) — shows an Instagram profile header and a grid (or paged slider) of its posts.
 *
 * Two sources:
 *   - account: the latest posts of a connected Instagram Business / Creator account, read with an
 *     access token from the Instagram API (Instagram Login). Only these account types expose their
 *     media to an API; a personal account cannot be used.
 *   - manual:  images picked from the Media Library, each with its own link. Works with any account
 *     (or none), and needs no token.
 *
 * Account data is never fetched on a busy page view if it can be avoided:
 *   - the profile, the post list and a LOCAL copy of each image (resized, in
 *     uploads/unysonplus/instagram/) are kept in an option, so the feed keeps showing the last good
 *     copy when Instagram is slow, down or the token stops working;
 *   - a transient marks that copy fresh for `cache_hours`; when it lapses a one-off cron event
 *     refreshes it in the background and visitors keep seeing the stored copy meanwhile;
 *   - only the very first render (no stored copy yet) fetches inline.
 * Instagram's own image URLs expire after a while, which is why the images are copied locally.
 *
 * The token lives in the element's options (editors only) and, once used, in the
 * `fw_igf_tokens` option keyed by a hash of the token as entered. A long-lived token lasts 60 days;
 * it is refreshed (Instagram's refresh endpoint) whenever it is more than 7 days old, and the new
 * token is stored under the same hash, so the element keeps working without anyone re-entering it.
 */
class FW_Shortcode_Instagram extends FW_Shortcode {

	const API      = 'https://graph.instagram.com';
	const OPT_DATA = 'fw_igf_data_';   // + hash: stored copy (profile + items + fetched time)
	const OPT_TOK  = 'fw_igf_tokens';  // hash => array( token, refreshed )
	const T_FRESH  = 'fw_igf_fresh_';  // + hash: transient, set while the stored copy is fresh
	const T_LOCK   = 'fw_igf_lock_';   // + hash: transient, set while a fetch runs
	const CRON     = 'fw_igf_refresh';

	/** @internal */
	public function _init() {
		add_action( self::CRON, array( __CLASS__, 'refresh' ), 10, 1 );
	}

	/**
	 * The hash that keys everything stored for one connected account.
	 *
	 * @param string $token The token as entered in the element.
	 * @return string
	 */
	public static function key( $token ) {
		return substr( md5( trim( (string) $token ) ), 0, 16 );
	}

	/**
	 * Returns the feed data for a token: the stored copy, refreshed in the background when stale,
	 * or fetched now when there is no stored copy yet.
	 *
	 * @param string $token       Access token as entered in the element.
	 * @param int    $count       How many posts the element shows.
	 * @param int    $cache_hours How long a fetched copy stays fresh.
	 * @return array{profile: array, items: array, error?: string}
	 */
	public static function get_feed( $token, $count, $cache_hours ) {
		$token = trim( (string) $token );
		if ( $token === '' ) {
			return array( 'profile' => array(), 'items' => array(), 'error' => 'no_token' );
		}
		$key = self::key( $token );

		$tokens = get_option( self::OPT_TOK, array() );
		if ( ! is_array( $tokens ) ) {
			$tokens = array();
		}
		if ( empty( $tokens[ $key ] ) ) {
			$tokens[ $key ] = array( 'token' => $token, 'refreshed' => time(), 'count' => $count, 'hours' => $cache_hours );
			update_option( self::OPT_TOK, $tokens, false );
		} elseif ( (int) ( $tokens[ $key ]['count'] ?? 0 ) < $count || (int) ( $tokens[ $key ]['hours'] ?? 0 ) !== (int) $cache_hours ) {
			$tokens[ $key ]['count'] = max( $count, (int) ( $tokens[ $key ]['count'] ?? 0 ) );
			$tokens[ $key ]['hours'] = $cache_hours;
			update_option( self::OPT_TOK, $tokens, false );
		}

		$data  = get_option( self::OPT_DATA . $key );
		$fresh = false !== get_transient( self::T_FRESH . $key );
		if ( ! is_array( $data ) || empty( $data['items'] ) ) {
			// Nothing to show yet: fetch now, unless a fetch just failed (then wait out the pause).
			if ( ! $fresh ) {
				self::refresh( $key );
				$data = get_option( self::OPT_DATA . $key );
			}
		} elseif ( ( ! $fresh || (int) ( $data['requested'] ?? 0 ) < $count ) && ! wp_next_scheduled( self::CRON, array( $key ) ) ) {
			// Stale, or the element now asks for more posts: refresh in the background.
			if ( (int) ( $data['requested'] ?? 0 ) < $count ) {
				delete_transient( self::T_FRESH . $key );
			}
			wp_schedule_single_event( time(), self::CRON, array( $key ) );
		}

		if ( ! is_array( $data ) ) {
			return array( 'profile' => array(), 'items' => array(), 'error' => 'fetch_failed' );
		}
		return $data;
	}

	/**
	 * Fetches the profile + latest posts for a stored token and saves the copy. Runs inline on the
	 * first render and from the one-off cron event afterwards. A lock stops two requests fetching
	 * at once; a failure keeps the last good copy and waits a while before trying again.
	 *
	 * @param string $key The token hash.
	 * @return bool True when a fresh copy was saved.
	 */
	public static function refresh( $key ) {
		$key    = preg_replace( '/[^a-f0-9]/', '', (string) $key );
		$tokens = get_option( self::OPT_TOK, array() );
		if ( $key === '' || empty( $tokens[ $key ]['token'] ) ) {
			return false;
		}
		if ( get_transient( self::T_LOCK . $key ) ) {
			return false;
		}
		set_transient( self::T_LOCK . $key, 1, 2 * MINUTE_IN_SECONDS );

		$row   = $tokens[ $key ];
		$token = self::maybe_refresh_token( $key, $row );
		$count = max( 1, min( 50, (int) ( $row['count'] ?? 12 ) ) );
		$hours = max( 1, min( 168, (int) ( $row['hours'] ?? 6 ) ) );

		$profile = self::api( '/me', array( 'fields' => 'user_id,username,name,profile_picture_url,followers_count,media_count' ), $token );
		$media   = self::api( '/me/media', array( 'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp', 'limit' => $count ), $token );

		if ( is_wp_error( $profile ) || is_wp_error( $media ) || empty( $profile['username'] ) ) {
			// Keep the last good copy; try again in 30 minutes rather than on every page view.
			set_transient( self::T_FRESH . $key, 1, 30 * MINUTE_IN_SECONDS );
			$old = get_option( self::OPT_DATA . $key );
			if ( is_array( $old ) ) {
				$old['error'] = is_wp_error( $profile ) ? $profile->get_error_message() : ( is_wp_error( $media ) ? $media->get_error_message() : 'unknown' );
				update_option( self::OPT_DATA . $key, $old, false );
			}
			delete_transient( self::T_LOCK . $key );
			return false;
		}

		$dir   = function_exists( 'fw_upw_uploads_dir' ) ? fw_upw_uploads_dir( 'instagram' ) : null;
		$items = array();
		foreach ( (array) ( $media['data'] ?? array() ) as $m ) {
			$type = (string) ( $m['media_type'] ?? 'IMAGE' );
			$src  = $type === 'VIDEO' ? (string) ( $m['thumbnail_url'] ?? '' ) : (string) ( $m['media_url'] ?? '' );
			if ( $src === '' || empty( $m['id'] ) ) {
				continue;
			}
			$local = $dir ? self::store_image( $src, $dir, $key . '-' . preg_replace( '/\D/', '', (string) $m['id'] ) ) : '';
			$items[] = array(
				'id'      => (string) $m['id'],
				'type'    => $type === 'VIDEO' ? 'video' : ( $type === 'CAROUSEL_ALBUM' ? 'album' : 'image' ),
				'image'   => $local !== '' ? $local : $src,
				'link'    => esc_url_raw( (string) ( $m['permalink'] ?? '' ) ),
				'caption' => wp_strip_all_tags( (string) ( $m['caption'] ?? '' ) ),
				'time'    => (string) ( $m['timestamp'] ?? '' ),
			);
		}

		$avatar = (string) ( $profile['profile_picture_url'] ?? '' );
		if ( $avatar !== '' && $dir ) {
			$avatar = self::store_image( $avatar, $dir, $key . '-avatar', 160 ) ?: $avatar;
		}

		update_option( self::OPT_DATA . $key, array(
			'profile'   => array(
				'username'  => (string) $profile['username'],
				'name'      => (string) ( $profile['name'] ?? '' ),
				'avatar'    => $avatar,
				'followers' => (int) ( $profile['followers_count'] ?? 0 ),
				'posts'     => (int) ( $profile['media_count'] ?? 0 ),
			),
			'items'     => $items,
			'requested' => $count,
			'fetched'   => time(),
		), false );
		set_transient( self::T_FRESH . $key, 1, $hours * HOUR_IN_SECONDS );
		delete_transient( self::T_LOCK . $key );
		return true;
	}

	/**
	 * Refreshes a long-lived token when it is more than 7 days old (Instagram allows a refresh once
	 * the token is at least 24 hours old, and a refreshed token is valid for another 60 days).
	 *
	 * @param string $key Token hash.
	 * @param array  $row Stored token row.
	 * @return string The token to use now.
	 */
	private static function maybe_refresh_token( $key, $row ) {
		$token = (string) $row['token'];
		if ( time() - (int) ( $row['refreshed'] ?? 0 ) < 7 * DAY_IN_SECONDS ) {
			return $token;
		}
		$res = self::api( '/refresh_access_token', array( 'grant_type' => 'ig_refresh_token' ), $token );
		$tokens = get_option( self::OPT_TOK, array() );
		if ( ! is_wp_error( $res ) && ! empty( $res['access_token'] ) ) {
			$token = (string) $res['access_token'];
		}
		// Record the attempt either way, so a failing refresh is not retried on every fetch.
		$tokens[ $key ]['token']     = $token;
		$tokens[ $key ]['refreshed'] = time();
		update_option( self::OPT_TOK, $tokens, false );
		return $token;
	}

	/**
	 * One GET request to the Instagram API.
	 *
	 * @return array|WP_Error Decoded JSON, or an error with Instagram's message.
	 */
	private static function api( $path, $args, $token ) {
		/** Filters the Instagram API base URL (e.g. to pin an API version such as https://graph.instagram.com/v22.0). */
		$base = apply_filters( 'fw_instagram_feed_api_base', self::API );
		$url  = add_query_arg( array_merge( $args, array( 'access_token' => $token ) ), $base . $path );
		$res  = wp_safe_remote_get( $url, array( 'timeout' => 12, 'redirection' => 2 ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( wp_remote_retrieve_response_code( $res ) !== 200 || ! is_array( $body ) || isset( $body['error'] ) ) {
			$msg = is_array( $body ) && isset( $body['error']['message'] ) ? (string) $body['error']['message'] : 'HTTP ' . wp_remote_retrieve_response_code( $res );
			return new WP_Error( 'instagram_api', $msg );
		}
		return $body;
	}

	/**
	 * Copies one remote image into uploads/unysonplus/instagram/, resized to $width, and returns
	 * its URL. An existing copy is reused.
	 *
	 * @return string Local URL, or '' on failure.
	 */
	private static function store_image( $src, $dir, $name, $width = 720 ) {
		$name = sanitize_file_name( $name ) . '.jpg';
		$file = trailingslashit( $dir['path'] ) . $name;
		$url  = trailingslashit( $dir['url'] ) . $name;
		if ( file_exists( $file ) && filesize( $file ) > 0 ) {
			return $url;
		}
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		wp_mkdir_p( $dir['path'] );
		$tmp = download_url( $src, 20 );
		if ( is_wp_error( $tmp ) ) {
			return '';
		}
		$editor = wp_get_image_editor( $tmp );
		if ( ! is_wp_error( $editor ) ) {
			$editor->resize( $width, null, false );
			$editor->set_quality( 82 );
			$saved = $editor->save( $file, 'image/jpeg' );
			wp_delete_file( $tmp );
			return is_wp_error( $saved ) ? '' : $url;
		}
		$ok = @rename( $tmp, $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		if ( ! $ok ) {
			wp_delete_file( $tmp );
		}
		return $ok ? $url : '';
	}
}
