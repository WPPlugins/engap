<?php
/**
 * Plugin Name: enGap
 * Description: DIY mobile application for your site.
 * Version: 0.2
 * Author: enRaiser
 * Author URI: http://www.enraiser.com
 * Plugin URI: http://www.engap.org
 */

/**
 * Version number for our API.
 *
 * @var string
 */
define( 'ENGAP_API_VERSION', '0.1' );



/**
 * Include our files for the API.
 */

 
include_once( dirname( __FILE__ ) . '/lib/class-engap-responseinterface.php' );
include_once( dirname( __FILE__ ) . '/lib/class-engap-response.php' );
include_once( dirname( __FILE__ ) . '/lib/class-engap-responsehandler.php' );
include_once( dirname( __FILE__ ) . '/lib/class-engap-server.php' );
include_once( dirname( __FILE__ ) . '/lib/class-engap-posts.php' );
include_once( dirname( __FILE__ ) . '/lib/class-engap-customposttype.php' );
include_once( dirname( __FILE__ ) . '/lib/class-engap-pages.php' );
include_once( dirname( __FILE__ ) . '/lib/class-engap-users.php' );

/**
 * Register rewrite rules for the API.
 *
 * @global WP $wp Current WordPress environment instance.
 */
 

function engap_init() {
	engap_register_rewrites();

	global $wp;
	$wp->add_query_var( 'json_route' );
}
add_action( 'init', 'engap_init' );

/**
 * Add rewrite rules.
 */
function engap_register_rewrites() {
	add_rewrite_rule( '^' . json_get_url_prefix() . '/?$','index.php?json_route=/','top' );
	add_rewrite_rule( '^' . json_get_url_prefix() . '(.*)?','index.php?json_route=$matches[1]','top' );
    
    //flush_rewrite_rules();
}

/**
 * Determine if the rewrite rules should be flushed.
 */
function engap_maybe_flush_rewrites() {
	$version = get_option( 'engap_plugin_version', null );

	if ( empty( $version ) ||  $version !== ENGAP_API_VERSION ) {
		flush_rewrite_rules();
		update_option( 'engap_plugin_version', ENGAP_API_VERSION );
	}

}
add_action( 'init', 'engap_maybe_flush_rewrites', 999 );

/**
 * Register the default ENGAP  filters.
 *
 * @internal This will live in default-filters.php
 *
 * @global ENGAP_Posts      $wp_json_posts
 * @global ENGAP_Pages      $wp_json_pages
 * @global ENGAP_Media      $wp_json_media
 * @global ENGAP_Taxonomies $wp_json_taxonomies
 *
 * @param ENGAP_ResponseHandler $server Server object.
 */
 



function engap_default_filters( $server ) {
	global $wp_json_posts, $wp_json_pages, $wp_json_media, $wp_json_taxonomies;
    

	// Posts.
	$wp_json_posts = new ENGAP_Posts( $server );
	add_filter( 'json_endpoints', array( $wp_json_posts, 'register_routes' ), 0 );
	add_filter( 'json_prepare_taxonomy', array( $wp_json_posts, 'add_post_type_data' ), 10, 3 );

	// Users.
	$wp_json_users = new ENGAP_Users( $server );
	add_filter( 'json_endpoints',       array( $wp_json_users, 'register_routes'         ), 0     );
	add_filter( 'json_prepare_post',    array( $wp_json_users, 'add_post_author_data'    ), 10, 3 );
	add_filter( 'json_prepare_comment', array( $wp_json_users, 'add_comment_author_data' ), 10, 3 );

	// Pages.
	$wp_json_pages = new ENGAP_Pages( $server );
	$wp_json_pages->register_filters();

	// Post meta.
/* 	$wp_json_post_meta = new ENGAP_Meta_Posts( $server );
	add_filter( 'json_endpoints',    array( $wp_json_post_meta, 'register_routes'    ), 0 );
	add_filter( 'json_prepare_post', array( $wp_json_post_meta, 'add_post_meta_data' ), 10, 3 );
	add_filter( 'json_insert_post',  array( $wp_json_post_meta, 'insert_post_meta'   ), 10, 2 ); */

	// Media.
/* 	$wp_json_media = new ENGAP_Media( $server );
	add_filter( 'json_endpoints',       array( $wp_json_media, 'register_routes'    ), 1     );
	add_filter( 'json_prepare_post',    array( $wp_json_media, 'add_thumbnail_data' ), 10, 3 );
	add_filter( 'json_pre_insert_post', array( $wp_json_media, 'preinsert_check'    ), 10, 3 );
	add_filter( 'json_insert_post',     array( $wp_json_media, 'attach_thumbnail'   ), 10, 3 );
	add_filter( 'json_post_type_data',  array( $wp_json_media, 'type_archive_link'  ), 10, 2 ); */

	// Posts.


	// Default serving
	add_filter( 'json_serve_request', 'json_send_cors_headers'             );
	add_filter( 'json_pre_dispatch',  'json_handle_options_request', 10, 2 );
}
add_action( 'wp_json_server_before_serve', 'engap_default_filters', 10, 1 );

/**
 * Load the ENGAP .
 *
 * @todo Extract code that should be unit tested into isolated methods such as
 *       the wp_json_server_class filter and serving requests. This would also
 *       help for code re-use by `engap` endpoint. Note that we can't unit
 *       test any method that calls die().
 */
function engap_loaded() {
	if ( empty( $GLOBALS['wp']->query_vars['json_route'] ) )
		return;

	/**
	 * Whether this is a XML-RPC Request.
	 *
	 * @var bool
	 * @todo Remove me in favour of JSON_REQUEST
	 */
	define( 'XMLRPC_REQUEST', true );

	/**
	 * Whether this is a JSON Request.
	 *
	 * @var bool
	 */
	define( 'JSON_REQUEST', true );

	global $wp_json_server;

	// Allow for a plugin to insert a different class to handle requests.
	$wp_json_server_class = apply_filters( 'wp_json_server_class', 'ENGAP_Server' );
	$wp_json_server = new $wp_json_server_class;

	/**
	 * Fires when preparing to serve an API request.
	 *
	 * Endpoint objects should be created and register their hooks on this
	 * action rather than another action to ensure they're only loaded when
	 * needed.
	 *
	 * @param ENGAP_ResponseHandler $wp_json_server Response handler object.
	 */
	do_action( 'wp_json_server_before_serve', $wp_json_server );

	// Fire off the request.
	$wp_json_server->serve_request( $GLOBALS['wp']->query_vars['json_route'] );

	// We're done.
	die();
}
add_action( 'template_redirect', 'engap_loaded', -100 );

/**
 * Register routes and flush the rewrite rules on activation.
 *
 * @param bool $network_wide ?
 */
function engap_activation( $network_wide ) {
	if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {
		$mu_blogs = wp_get_sites();

		foreach ( $mu_blogs as $mu_blog ) {
			switch_to_blog( $mu_blog['blog_id'] );

			engap_register_rewrites();
			update_option( 'engap_plugin_version', null );
		}

		restore_current_blog();
	} else {
		engap_register_rewrites();
		update_option( 'engap_plugin_version', null );
	}
}
register_activation_hook( __FILE__, 'engap_activation' );

/**
 * Flush the rewrite rules on deactivation.
 *
 * @param bool $network_wide ?
 */
function engap_deactivation( $network_wide ) {
	if ( function_exists( 'is_multisite' ) && is_multisite() && $network_wide ) {

		$mu_blogs = wp_get_sites();

		foreach ( $mu_blogs as $mu_blog ) {
			switch_to_blog( $mu_blog['blog_id'] );
			delete_option( 'engap_plugin_version' );
		}

		restore_current_blog();
	} else {
		delete_option( 'engap_plugin_version' );
	}
}
register_deactivation_hook( __FILE__, 'engap_deactivation' );

/**
 * Add the API URL to the WP RSD endpoint.
 */
function json_output_rsd() {
	?>
	<api name="WP-API" blogID="1" preferred="false" apiLink="<?php echo get_json_url() ?>" />
	<?php
}
add_action( 'xmlrpc_rsd_apis', 'json_output_rsd' );



/**
 * Add 'show_in_json' {@see register_post_type()} argument.
 *
 * Adds the 'show_in_json' post type argument to {@see register_post_type()}.
 * This value controls whether the post type is available via API endpoints,
 * and defaults to the value of $publicly_queryable.
 *
 * @global array $wp_post_types Post types list.
 *
 * @param string   $post_type Post type to register.
 * @param stdClass $args      Post type arguments.
 */
function json_register_post_type( $post_type, $args ) {
	global $wp_post_types;

	$type = &$wp_post_types[ $post_type ];

	// Exception for pages.
	if ( $post_type === 'page' ) {
		$type->show_in_json = true;
	}

	// Exception for revisions.
	if ( $post_type === 'revision' ) {
		$type->show_in_json = true;
	}

	// Default to the value of $publicly_queryable.
	if ( ! isset( $type->show_in_json ) ) {
		$type->show_in_json = $type->publicly_queryable;
	}
}
add_action( 'registered_post_type', 'json_register_post_type', 10, 2 );

/**
 * Check for errors when using cookie-based authentication.
 *
 * WordPress' built-in cookie authentication is always active
 * for logged in users. However, the API has to check nonces
 * for each request to ensure users are not vulnerable to CSRF.
 *
 * @global mixed $wp_json_auth_cookie
 *
 * @param WP_Error|mixed $result Error from another authentication handler,
 *                               null if we should handle it, or another
 *                               value if not
 * @return WP_Error|mixed|bool WP_Error if the cookie is invalid, the $result,
 *                             otherwise true.
 */
function json_cookie_check_errors( $result ) {
	if ( ! empty( $result ) ) {
		return $result;
	}

	global $wp_json_auth_cookie;

	/*
	 * Is cookie authentication being used? (If we get an auth
	 * error, but we're still logged in, another authentication
	 * must have been used.)
	 */
	if ( $wp_json_auth_cookie !== true && is_user_logged_in() ) {
		return $result;
	}

	// Is there a nonce?
	$nonce = null;
	if ( isset( $_REQUEST['_wp_json_nonce'] ) ) {
		$nonce = $_REQUEST['_wp_json_nonce'];
	} elseif ( isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
		$nonce = $_SERVER['HTTP_X_WP_NONCE'];
	}

	if ( $nonce === null ) {
		// No nonce at all, so act as if it's an unauthenticated request.
		wp_set_current_user( 0 );
		return true;
	}

	// Check the nonce.
	$result = wp_verify_nonce( $nonce, 'wp_json' );
	if ( ! $result ) {
		return new WP_Error( 'json_cookie_invalid_nonce', __( 'Cookie nonce is invalid' ), array( 'status' => 403 ) );
	}

	return true;
}
add_filter( 'json_authentication_errors', 'json_cookie_check_errors', 100 );

/**
 * Collect cookie authentication status.
 *
 * Collects errors from {@see wp_validate_auth_cookie} for
 * use by {@see json_cookie_check_errors}.
 *
 * @see current_action()
 * @global mixed $wp_json_auth_cookie
 */
function json_cookie_collect_status() {
	global $wp_json_auth_cookie;

	$status_type = current_filter();// sachin replace current_action

	if ( $status_type !== 'auth_cookie_valid' ) {
		$wp_json_auth_cookie = substr( $status_type, 12 );
		return;
	}

	$wp_json_auth_cookie = true;
}
add_action( 'auth_cookie_malformed',    'json_cookie_collect_status' );
add_action( 'auth_cookie_expired',      'json_cookie_collect_status' );
add_action( 'auth_cookie_bad_username', 'json_cookie_collect_status' );
add_action( 'auth_cookie_bad_hash',     'json_cookie_collect_status' );
add_action( 'auth_cookie_valid',        'json_cookie_collect_status' );

/**
 * Get the URL prefix for any API resource.
 *
 * @return string Prefix.
 */
function json_get_url_prefix() {
	/**
	 * Filter the JSON URL prefix.
	 *
	 * @since 1.0
	 *
	 * @param string $prefix URL prefix. Default 'wp-json'.
	 */
	return apply_filters( 'json_url_prefix', 'engap' );
}

/**
 * Get URL to a JSON endpoint on a site.
 *
 * @todo Check if this is even necessary
 *
 * @param int    $blog_id Blog ID.
 * @param string $path    Optional. JSON route. Default empty.
 * @param string $scheme  Optional. Sanitization scheme. Default 'json'.
 * @return string Full URL to the endpoint.
 */
function get_json_url( $blog_id = null, $path = '', $scheme = 'json' ) {
	if ( get_option( 'permalink_structure' ) ) {
		$url = get_home_url( $blog_id, json_get_url_prefix(), $scheme );

		if ( ! empty( $path ) && is_string( $path ) && strpos( $path, '..' ) === false )
			$url .= '/' . ltrim( $path, '/' );
	} else {
		$url = trailingslashit( get_home_url( $blog_id, '', $scheme ) );

		if ( empty( $path ) ) {
			$path = '/';
		} else {
			$path = '/' . ltrim( $path, '/' );
		}

		$url = add_query_arg( 'json_route', $path, $url );
	}

	/**
	 * Filter the JSON URL.
	 *
	 * @since 1.0
	 *
	 * @param string $url     JSON URL.
	 * @param string $path    JSON route.
	 * @param int    $blod_ig Blog ID.
	 * @param string $scheme  Sanitization scheme.
	 */
	return apply_filters( 'json_url', $url, $path, $blog_id, $scheme );
}

/**
 * Get URL to a JSON endpoint.
 *
 * @param string $path   Optional. JSON route. Default empty.
 * @param string $scheme Optional. Sanitization scheme. Default 'json'.
 * @return string Full URL to the endpoint.
 */
function json_url( $path = '', $scheme = 'json' ) {
	return get_json_url( null, $path, $scheme );
}

/**
 * Ensure a JSON response is a response object.
 *
 * This ensures that the response is consistent, and implements
 * {@see ENGAP_ResponseInterface}, allowing usage of
 * `set_status`/`header`/etc without needing to double-check the object. Will
 * also allow {@see WP_Error} to indicate error responses, so users should
 * immediately check for this value.
 *
 * @param WP_Error|ENGAP_ResponseInterface|mixed $response Response to check.
 * @return WP_Error|ENGAP_ResponseInterface WP_Error if present, ENGAP_ResponseInterface
 *                                            instance otherwise.
 */
function json_ensure_response( $response ) {
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( $response instanceof ENGAP_ResponseInterface ) {
		return $response;
	}

	return new ENGAP_Response( $response );
}

/**
 * Check if we have permission to interact with the post object.
 *
 * @param WP_Post $post Post object.
 * @param string $capability Permission to check.
 * @return boolean Can we interact with it?
 */
function json_check_post_permission( $post, $capability = 'read' ) {
	$permission = false;
	$post_type = get_post_type_object( $post['post_type'] );

	switch ( $capability ) {
		case 'read' :
			if ( ! $post_type->show_in_json ) {
				return false;
			}

			if ( 'publish' === $post['post_status'] || current_user_can( $post_type->cap->read_post, $post['ID'] ) ) {
				$permission = true;
			}

			// Can we read the parent if we're inheriting?
			if ( 'inherit' === $post['post_status'] && $post['post_parent'] > 0 ) {
				$parent = get_post( $post['post_parent'], ARRAY_A );

				if ( json_check_post_permission( $parent, 'read' ) ) {
					$permission = true;
				}
			}

			// If we don't have a parent, but the status is set to inherit, assume
			// it's published (as per get_post_status())
			if ( 'inherit' === $post['post_status'] ) {
				$permission = true;
			}
			break;

		case 'edit' :
			if ( current_user_can( $post_type->cap->edit_post, $post['ID'] ) ) {
				$permission = true;
			}
			break;

		case 'create' :
			if ( current_user_can( $post_type->cap->create_posts ) || current_user_can( $post_type->cap->edit_posts ) ) {
				$permission = true;
			}
			break;

		case 'delete' :
			if ( current_user_can( $post_type->cap->delete_post, $post['ID'] ) ) {
				$permission = true;
			}
			break;

		default :
			if ( current_user_can( $post_type->cap->$capability ) ) {
				$permission = true;
			}
	}

	return apply_filters( "json_check_post_{$capability}_permission", $permission, $post );
}

/**
 * Parse an RFC3339 timestamp into a DateTime.
 *
 * @param string $date      RFC3339 timestamp.
 * @param bool   $force_utc Force UTC timezone instead of using the timestamp's TZ.
 * @return DateTime DateTime instance.
 */
function json_parse_date( $date, $force_utc = false ) {
	if ( $force_utc ) {
		$date = preg_replace( '/[+-]\d+:?\d+$/', '+00:00', $date );
	}

	$regex = '#^\d{4}-\d{2}-\d{2}[Tt ]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}(?::\d{2})?)?$#';

	if ( ! preg_match( $regex, $date, $matches ) ) {
		return false;
	}

	return strtotime( $date );
}

/**
 * Get a local date with its GMT equivalent, in MySQL datetime format.
 *
 * @param string $date      RFC3339 timestamp
 * @param bool   $force_utc Whether a UTC timestamp should be forced.
 * @return array|null Local and UTC datetime strings, in MySQL datetime format (Y-m-d H:i:s),
 *                    null on failure.
 */
function json_get_date_with_gmt( $date, $force_utc = false ) {
	$date = json_parse_date( $date, $force_utc );

	if ( empty( $date ) ) {
		return null;
	}

	$utc = date( 'Y-m-d H:i:s', $date );
	$local = get_date_from_gmt( $utc );

	return array( $local, $utc );
}

/**
 * Parses and formats a MySQL datetime (Y-m-d H:i:s) for ISO8601/RFC3339
 *
 * Explicitly strips timezones, as datetimes are not saved with any timezone
 * information. Including any information on the offset could be misleading.
 *
 * @param string $date_string
 *
 * @return mixed
 */
function json_mysql_to_rfc3339( $date_string ) {
	$formatted = mysql2date( 'c', $date_string, false );

	// Strip timezone information
	return preg_replace( '/(?:Z|[+-]\d{2}(?::\d{2})?)$/', '', $formatted );
}

/**
 * Retrieve the avatar url for a user who provided a user ID or email address.
 *
 * {@see get_avatar()} doesn't return just the URL, so we have to
 * extract it here.
 *
 * @param string $email Email address.
 * @return string URL for the user's avatar, empty string otherwise.
*/
function json_get_avatar_url( $email ) {
	$avatar_html = get_avatar( $email );

	// Strip the avatar url from the get_avatar img tag.
	preg_match('/src=["|\'](.+)[\&|"|\']/U', $avatar_html, $matches);

	if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
		return esc_url_raw( $matches[1] );
	}

	return '';
}

/**
 * Get the timezone object for the site.
 *
 * @return DateTimeZone DateTimeZone instance.
 */
function json_get_timezone() {
	static $zone = null;

	if ( $zone !== null ) {
		return $zone;
	}

	$tzstring = get_option( 'timezone_string' );

	if ( ! $tzstring ) {
		// Create a UTC+- zone if no timezone string exists
		$current_offset = get_option( 'gmt_offset' );
		if ( 0 == $current_offset ) {
			$tzstring = 'UTC';
		} elseif ( $current_offset < 0 ) {
			$tzstring = 'Etc/GMT' . $current_offset;
		} else {
			$tzstring = 'Etc/GMT+' . $current_offset;
		}
	}
	$zone = new DateTimeZone( $tzstring );

	return $zone;
}





/**
 * Send Cross-Origin Resource Sharing headers with API requests
 *
 * @param mixed $value Response data
 * @return mixed Response data
 */
function json_send_cors_headers( $value ) {
	$origin = get_http_origin();

	if ( $origin ) {
		header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
		header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
		header( 'Access-Control-Allow-Credentials: true' );
	}

	return $value;
}

/**
 * Handle OPTIONS requests for the server
 *
 * This is handled outside of the server code, as it doesn't obey normal route
 * mapping.
 *
 * @param mixed $response Current response, either response or `null` to indicate pass-through
 * @param ENGAP_Server $handler ResponseHandler instance (usually ENGAP_Server)
 * @return ENGAP_ResponseHandler Modified response, either response or `null` to indicate pass-through
 */
function json_handle_options_request( $response, $handler ) {
	if ( ! empty( $response ) || $handler->method !== 'OPTIONS' ) {
		return $response;
	}

	$response = new ENGAP_Response();

	$accept = array();

	$handler_class = get_class( $handler );
	$class_vars = get_class_vars( $handler_class );
	$map = $class_vars['method_map'];

	foreach ( $handler->get_routes() as $route => $endpoints ) {
		$match = preg_match( '@^' . $route . '$@i', $handler->path, $args );

		if ( ! $match ) {
			continue;
		}

		foreach ( $endpoints as $endpoint ) {
			foreach ( $map as $type => $bitmask ) {
				if ( $endpoint[1] & $bitmask ) {
					$accept[] = $type;
				}
			}
		}
		break;
	}
	$accept = array_unique( $accept );

	$response->header( 'Accept', implode( ', ', $accept ) );

	return $response;
}

if ( ! function_exists( 'json_last_error_msg' ) ):
/**
 * Returns the error string of the last json_encode() or json_decode() call
 *
 * @internal This is a compatibility function for PHP <5.5
 *
 * @return boolean|string Returns the error message on success, "No Error" if no error has occurred, or FALSE on failure.
 */
function json_last_error_msg() {
	// see https://core.trac.wordpress.org/ticket/27799
	if ( ! function_exists( 'json_last_error' ) ) {
		return false;
	}

	$last_error_code = json_last_error();

	// just in case JSON_ERROR_NONE is not defined
	$error_code_none = defined( 'JSON_ERROR_NONE' ) ? JSON_ERROR_NONE : 0;

	switch ( true ) {
		case $last_error_code === $error_code_none:
			return 'No error';

		case defined( 'JSON_ERROR_DEPTH' ) && JSON_ERROR_DEPTH === $last_error_code:
			return 'Maximum stack depth exceeded';

		case defined( 'JSON_ERROR_STATE_MISMATCH' ) && JSON_ERROR_STATE_MISMATCH === $last_error_code:
			return 'State mismatch (invalid or malformed JSON)';

		case defined( 'JSON_ERROR_CTRL_CHAR' ) && JSON_ERROR_CTRL_CHAR === $last_error_code:
			return 'Control character error, possibly incorrectly encoded';

		case defined( 'JSON_ERROR_SYNTAX' ) && JSON_ERROR_SYNTAX === $last_error_code:
			return 'Syntax error';

		case defined( 'JSON_ERROR_UTF8' ) && JSON_ERROR_UTF8 === $last_error_code:
			return 'Malformed UTF-8 characters, possibly incorrectly encoded';

		case defined( 'JSON_ERROR_RECURSION' ) && JSON_ERROR_RECURSION === $last_error_code:
			return 'Recursion detected';

		case defined( 'JSON_ERROR_INF_OR_NAN' ) && JSON_ERROR_INF_OR_NAN === $last_error_code:
			return 'Inf and NaN cannot be JSON encoded';

		case defined( 'JSON_ERROR_UNSUPPORTED_TYPE' ) && JSON_ERROR_UNSUPPORTED_TYPE === $last_error_code:
			return 'Type is not supported';

		default:
			return 'An unknown error occurred';
	}
}
endif;
