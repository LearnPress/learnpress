<?php
/**
 * LearnPress Core Functions
 * Define common functions for both front-end and back-end
 *
 * @author   ThimPress
 * @package  LearnPress/Functions
 * @version  1.0
 */

use LearnPress\Helpers\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Vue write php has script, so when sanitize will error, so use code is mask pass check sanitize, esc
 *
 * @param $content
 *
 * @return void
 * @since 4.1.6.9
 */
function learn_press_echo_vuejs_write_on_php( $content ) {
	echo ( $content );
}

function learnpress_gutenberg_disable_cpt( $can_edit, $post_type ) {
	$post_types = array(
		LP_COURSE_CPT   => LP_Settings::get_option( 'enable_gutenberg_course', 'no' ),
		LP_LESSON_CPT   => LP_Settings::get_option( 'enable_gutenberg_lesson', 'no' ),
		LP_QUIZ_CPT     => LP_Settings::get_option( 'enable_gutenberg_quiz', 'no' ),
		LP_QUESTION_CPT => LP_Settings::get_option( 'enable_gutenberg_question', 'no' ),
	);

	foreach ( $post_types as $key => $pt ) {
		if ( $post_type === $key && $pt !== 'yes' ) {
			$can_edit = false;
		}
	}

	return $can_edit;
}
add_filter( 'use_block_editor_for_post_type', 'learnpress_gutenberg_disable_cpt', 10, 2 );

if ( ! function_exists( 'lp_add_body_class' ) ) {
	function lp_add_body_class( $classes ) {
		$classes = (array) $classes;

		if ( LP_Page_Controller::is_page_profile() ) {
			$classes[] = 'learnpress-profile';
		} elseif ( learn_press_is_checkout() ) {
			$classes[] = 'learnpress-checkout';
		}

		return $classes;
	}
	add_filter( 'body_class', 'lp_add_body_class' );
}

/**
 * Short function to get name of a theme
 *
 * @param string $folder
 *
 * @return mixed|string
 */
function learn_press_get_theme_name( $folder ) {
	$theme = wp_get_theme( $folder );

	return ! empty( $theme['Name'] ) ? $theme['Name'] : '';
}

/**
 * Clean.
 *
 * @param [type] $var
 *
 * @version 4.0.0
 * @author Nhamdv <daonham95@gmail.com>
 */
function learnpress_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'learnpress_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Display HTML of element for building QuickTip JS.
 *
 * @param string $tip
 * @param bool   $echo
 * @param array  $options
 *
 * @return string
 * @since 3.0.0
 */
function learn_press_quick_tip( $tip, $echo = true, $options = array() ) {
	$atts = '';
	if ( $options ) {
		foreach ( $options as $k => $v ) {
			$options[ $k ] = "data-{$k}=\"{$v}\"";
		}
		$atts = ' ' . implode( ' ', $options );
	}

	$tip = sprintf( '<span class="learn-press-tip" ' . $atts . '>%s</span>', $tip );

	if ( $echo ) {
		echo wp_kses_post( $tip );
	}

	return $tip;
}

/**
 * Get current post ID.
 *
 * @return int
 */
function learn_press_get_post() {
	global $post;

	$post_id = learn_press_get_request( 'post' );

	if ( ! $post_id ) {
		$post_id = ! empty( $post ) ? $post->ID : 0;
	}
	if ( empty( $post_id ) ) {
		$post_id = learn_press_get_request( 'post_ID' );
	}

	return absint( $post_id );
}

/**
 * Get the LearnPress plugin url
 *
 * @param string $sub_dir
 *
 * @return string
 */
function learn_press_plugin_url( $sub_dir = '' ) {
	return LearnPress::instance()->plugin_url( $sub_dir );
}

/**
 * Get the LearnPress plugin path.
 *
 * @param string $sub_dir
 *
 * @return string
 */
function learn_press_plugin_path( $sub_dir = '' ) {
	return LearnPress::instance()->plugin_path( $sub_dir );
}

/**
 * Includes file base on LearnPress path
 *
 * @param string $file
 * @param string $folder
 * @param bool   $include_once
 *
 * @return bool
 */
function learn_press_include( $file, $folder = 'inc', $include_once = true ) {
	$include = learn_press_plugin_path( "{$folder}/{$file}" );

	if ( file_exists( $include ) ) {
		if ( $include_once ) {
			include_once $include;
		} else {
			include $include;
		}

		return true;
	}

	return false;
}

/**
 * Get current IP of the user
 *
 * @return mixed
 */
function learn_press_get_ip() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
	}
	return '';
}

/**
 * Get user agent.
 *
 * @return string
 */
function learn_press_get_user_agent(): string {
	return LP_Helper::sanitize_params_submitted( $_SERVER['HTTP_USER_AGENT'] ?? '' );
}

/**
 * Generate an unique string.
 *
 * @param string $prefix
 *
 * @return string
 */
function learn_press_uniqid( $prefix = '' ) {
	$hash = str_replace( '.', '', microtime( true ) . uniqid() );

	return apply_filters( 'learn-press/generate-hash', $prefix . $hash, $prefix );
}

function learn_press_random_value( $len = 8 ) {
	return substr( md5( uniqid( mt_rand(), true ) ), 0, $len );
}

function learn_press_map_columns_format( $columns, $format ) {
	$return = array();
	foreach ( $columns as $k => $v ) {
		if ( ! empty( $format[ $k ] ) ) {
			$return[] = $format[ $k ];
		} else {
			$return[] = '%s'; // default is string
		}
	}

	return $return;
}

/**
 * Check to see if an endpoint is showing in current URL.
 *
 * @param bool $endpoint
 *
 * @return bool
 */
function learn_press_is_endpoint_url( $endpoint = false ) {
	global $wp;

	$endpoints = array();

	if ( $endpoint !== false ) {
		if ( ! isset( $endpoints[ $endpoint ] ) ) {
			return false;
		} else {
			$endpoint_var = $endpoints[ $endpoint ];
		}

		return isset( $wp->query_vars[ $endpoint_var ] );
	} else {
		foreach ( $endpoints as $key => $value ) {
			if ( isset( $wp->query_vars[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}
}

/**
 * Get current URL user is viewing.
 *
 * @return string
 * @deprecated 4.2.2
 */
function learn_press_get_current_url() {
	//_deprecated_function( __FUNCTION__, '4.2.2', 'LP_Helper::getUrlCurrent' );
	return LP_Helper::getUrlCurrent();

	$url = untrailingslashit( esc_url_raw( $_SERVER['REQUEST_URI'] ) );

	if ( ! preg_match( '!^https?!', $url ) ) {
		$siteurl    = trailingslashit( get_home_url() );
		$home_query = '';

		if ( strpos( $siteurl, '?' ) !== false ) {
			$parts      = explode( '?', $siteurl );
			$home_query = $parts[1];
			$siteurl    = $parts[0];
		}

		if ( $home_query ) {
			parse_str( untrailingslashit( $home_query ), $home_query );
			$url = esc_url_raw( add_query_arg( $home_query, $url ) );
		}

		$segs1 = explode( '/', $siteurl );
		$segs2 = explode( '/', $url );

		if ( $removed = array_intersect( $segs1, $segs2 ) ) {
			if ( $segs2 = array_diff( $segs2, $removed ) ) {
				$current_url = $siteurl . join( '/', $segs2 );
				if ( strpos( $current_url, '?' ) === false ) {
					$current_url = trailingslashit( $current_url );
				}
			}
		}
	}

	return $current_url;
}

/**
 * Remove unneeded characters in an URL
 *
 * @param string $url
 * @param bool   $trailingslashit
 *
 * @return string
 */
function learn_press_sanitize_url( $url, $trailingslashit = true ) {
	if ( $url ) {
		preg_match( '!(https?://)?(.*)!', $url, $matches );
		$url_without_http = $matches[2];
		$url_without_http = preg_replace( '![/]+!', '/', $url_without_http );
		$url              = $matches[1] . $url_without_http;

		return ( $trailingslashit &&
				strpos( $url, '?' ) === false ) ? trailingslashit( $url ) : untrailingslashit( $url );
	}

	return $url;
}

/**
 * Get all types of question supported
 *
 * @return mixed
 */
function learn_press_question_types() {
	return LP_Question::get_types();
}

/**
 * Get human name of question's type by slug
 *
 * @param string $slug
 *
 * @return array
 */
function learn_press_question_name_from_slug( $slug ) {
	$types = learn_press_question_types();
	$name  = ! empty( $types[ $slug ] ) ? $types[ $slug ] : '';

	return apply_filters( 'learn-press/question/slug-to-name', $name, $slug );
}

/**
 * Get terms of a course by taxonomy.
 * E.g: course_tag, course_category
 *
 * @param int    $course_id
 * @param string $taxonomy
 * @param array  $args
 *
 * @return array|mixed
 */
function learn_press_get_course_terms( $course_id, $taxonomy, $args = array() ) {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	// Support ordering by parent
	if ( ! empty( $args['orderby'] ) && in_array( $args['orderby'], array( 'name_num', 'parent' ) ) ) {
		$fields  = isset( $args['fields'] ) ? $args['fields'] : 'all';
		$orderby = $args['orderby'];

		// Unset for wp_get_post_terms
		unset( $args['orderby'] );
		unset( $args['fields'] );

		$terms = wp_get_post_terms( $course_id, $taxonomy, $args );

		switch ( $orderby ) {
			case 'name_num':
				usort( $terms, '_learn_press_get_course_terms_name_num_usort_callback' );
				break;
			case 'parent':
				usort( $terms, '_learn_press_get_course_terms_parent_usort_callback' );
				break;
		}

		switch ( $fields ) {
			case 'names':
				$terms = wp_list_pluck( $terms, 'name' );
				break;
			case 'ids':
				$terms = wp_list_pluck( $terms, 'term_id' );
				break;
			case 'slugs':
				$terms = wp_list_pluck( $terms, 'slug' );
				break;
		}
	} elseif ( ! empty( $args['orderby'] ) && $args['orderby'] === 'menu_order' ) {
		// wp_get_post_terms doesn't let us use custom sort order
		$args['include'] = wp_get_post_terms( $course_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( empty( $args['include'] ) ) {
			$terms = array();
		} else {
			// This isn't needed for get_terms
			unset( $args['orderby'] );

			// Set args for get_terms
			$args['menu_order'] = $args['order'] ?? 'ASC';
			$args['hide_empty'] = $args['hide_empty'] ?? 0;
			$args['fields']     = $args['fields'] ?? 'names';

			// Ensure slugs is valid for get_terms - slugs isn't supported
			$args['fields'] = $args['fields'] === 'slugs' ? 'id=>slug' : $args['fields'];
			$terms          = get_terms( $taxonomy, $args );
		}
	} else {
		$terms = wp_get_post_terms( $course_id, $taxonomy, $args );
	}

	// @deprecated
	$terms = apply_filters( 'learn_press_get_course_terms', $terms, $course_id, $taxonomy, $args );

	return apply_filters( 'learn-press/course/terms', $terms, $course_id, $taxonomy, $args );
}

/**
 * Callback function for sorting terms of course by name.
 *
 * @param object $a
 * @param object $b
 *
 * @return int
 */
function _learn_press_get_course_terms_name_num_usort_callback( $a, $b ) {
	if ( $a->name + 0 === $b->name + 0 ) {
		return 0;
	}

	return ( $a->name + 0 < $b->name + 0 ) ? - 1 : 1;
}

/**
 * Callback function for sorting terms of course by parent.
 *
 * @param object $a
 * @param object $b
 *
 * @return int
 */
function _learn_press_get_course_terms_parent_usort_callback( $a, $b ) {
	if ( $a->parent === $b->parent ) {
		return 0;
	}

	return ( $a->parent < $b->parent ) ? 1 : - 1;
}

/**
 * Get posts by it's post-name (slug).
 *
 * @param string $name
 * @param string $type
 * @param bool   $single
 *
 * @return array|bool|null|WP_Post
 */
function learn_press_get_post_by_name( $name, $type, $single = true ) {
	$post_name = sanitize_title( $name );
	$id        = LP_Object_Cache::get( $type . '-' . $post_name, 'learn-press/post-names' );

	if ( false === $id ) {
		foreach ( array( $name, urldecode( $name ) ) as $_name ) {
			$args = array(
				'name'      => $_name,
				'post_type' => array( $type ),
			);

			$posts = get_posts( $args );

			if ( $posts ) {
				$post = $posts[0];
				$id   = $post->ID;
				wp_cache_set( $id, $post, 'posts' );
				LP_Object_Cache::set( $type . '-' . $name, $id, 'learn-press/post-names' );
				break;
			}
		}
	}

	return $id ? get_post( $id ) : false;
}

if ( ! function_exists( 'learn_press_is_ajax' ) ) {
	function learn_press_is_ajax() {
		return defined( 'LP_DOING_AJAX' ) && LP_DOING_AJAX && 'yes' != learn_press_get_request( 'noajax' );
	}
}

/**
 * Get page id from admin settings page
 *
 * @param string $name
 *
 * @since 3.0.0
 * @version 1.0.2
 * @return int
 */
function learn_press_get_page_id( string $name ): int {
	$page_id = LP_Settings::get_option( "{$name}_page_id", false );

	/*if ( function_exists( 'icl_object_id' ) ) {
		$page_id = icl_object_id( $page_id, 'page', false, defined( 'ICL_LANGUAGE_CODE' ) ? ICL_LANGUAGE_CODE : '' );
	}*/

	$page_id = (int) $page_id;

	return apply_filters( 'learn_press_get_page_id', $page_id, $name );
}

/**
 * display the seconds in time format h:i:s
 *
 * @param        $seconds
 * @param string  $separator
 *
 * @return string
 */
function learn_press_seconds_to_time( $seconds, $separator = ':' ) {
	return sprintf(
		'%02d%s%02d%s%02d',
		floor( $seconds / 3600 ),
		$separator,
		( $seconds / 60 ) % 60,
		$separator,
		$seconds % 60
	);
}

/* nav */
if ( ! function_exists( 'learn_press_course_paging_nav' ) ) {

	/**
	 * Display navigation to next/previous set of posts when applicable.
	 *
	 * @param array
	 */
	function learn_press_course_paging_nav( $args = array() ) {
		learn_press_paging_nav(
			array(
				'num_pages'     => $GLOBALS['wp_query']->max_num_pages,
				'wrapper_class' => 'navigation pagination',
			)
		);
	}
}

/* nav */
if ( ! function_exists( 'learn_press_paging_nav' ) ) {
	function learn_press_paging_nav( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'num_pages'     => 0,
				'paged'         => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'wrapper_class' => 'learn-press-pagination',
				'base'          => false,
				'format'        => '',
				'echo'          => true,
			)
		);

		if ( $args['num_pages'] < 2 ) {
			return false;
		}

		$paged        = $args['paged'];
		$pagenum_link = html_entity_decode( $args['base'] === false ? get_pagenum_link() : $args['base'] );

		$query_args = array();
		$url_parts  = explode( '?', $pagenum_link );

		if ( isset( $url_parts[1] ) ) {
			wp_parse_str( $url_parts[1], $query_args );
		}

		$pagenum_link = esc_url_raw( remove_query_arg( array_keys( $query_args ), $pagenum_link ) );
		$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

		$format  = $GLOBALS['wp_rewrite']->using_index_permalinks() && ! strpos(
			$pagenum_link,
			'index.php'
		) ? 'index.php/' : '';
		$format .= $args['format'] ? $args['format'] : ( $GLOBALS['wp_rewrite']->using_permalinks() ? user_trailingslashit(
			'page/%#%',
			'paged'
		) : '?paged=%#%' );

		$link_args = array(
			'base'      => $pagenum_link,
			'format'    => $format,
			'total'     => $args['num_pages'],
			'current'   => max( 1, $paged ),
			'mid_size'  => 1,
			'add_args'  => array_map( 'urlencode', $query_args ),
			'prev_text' => __( '<', 'learnpress' ),
			'next_text' => __( '>', 'learnpress' ),
			'type'      => 'list',
		);

		// Set up paginated links.
		$links = paginate_links( $link_args );

		ob_start();

		if ( $links ) {
			?>
			<div class="<?php echo esc_attr( $args['wrapper_class'] ); ?>">
				<?php echo wp_kses_post( $links ); ?>
			</div>
			<?php
		}

		$output = ob_get_clean();

		if ( $args['echo'] ) {
			echo wp_kses_post( $output );
		}

		return $output;
	}
}

/**
 * Get number of pages by rows and items per page.
 *
 * @param int $total
 * @param int $limit
 *
 * @return int
 */
function learn_press_get_num_pages( $total, $limit = 10 ) {
	$limit = $limit <= 0 ? 10 : $limit;

	if ( $total <= $limit ) {
		return 1;
	}

	$pages = absint( $total / $limit );

	if ( $total % $limit != 0 ) {
		++$pages;
	}

	return $pages;
}

/**
 * Get text
 *
 * @param $status_id
 *
 * @return mixed
 */
function learn_press_get_status_text( $status_id ) {
	switch ( $status_id ) {
		case 1:
			$text = 'pending';
			break;
		case 2:
			$text = 'complete';
			break;
		case - 1:
			$text = 'cancel';
			break;
		case - 2:
			$text = 'refund';
			break;
		default:
			$text = 'on-hold';
	}

	return $text;
}

function learn_press_get_course_duration_support() {
	return apply_filters(
		'learn_press_course_duration_support',
		array(
			'minute' => esc_html__( 'Minute(s)', 'learnpress' ),
			'hour'   => esc_html__( 'Hour(s)', 'learnpress' ),
			'day'    => esc_html__( 'Day(s)', 'learnpress' ),
			'week'   => esc_html__( 'Week(s)', 'learnpress' ),
		)
	);
}

function learn_press_number_to_string_time( $number ) {
	$str = $number;

	if ( preg_match( '!([0-9.]+) (minute|hour|day|week)!', $number, $matches ) ) {
		switch ( $matches[2] ) {
			case 'hour':
				$minute = $matches[1] * 60;
				$str    = sprintf( '%s hour %s minute', absint( $minute / 60 ), $minute % 60 );
				break;
			case 'day':
				$hour = $matches[1] * 24;
				$str  = sprintf( '%s day %s hour', absint( $hour / 24 ), $hour % 24 );
				break;
			case 'week':
				$day = $matches[1] * 7;
				$str = sprintf( '%s week %s day', absint( $day / 7 ), $day % 7 );
				break;
		}
	}

	return $str;
}

function learn_press_human_time_to_seconds( $time, $default = '' ) {
	$duration      = learn_press_get_course_duration_support();
	$duration_keys = array_keys( $duration );

	if ( preg_match_all( '!([0-9]+)\s*(' . join( '|', $duration_keys ) . ')?!', $time, $matches ) ) {
		$a1 = $matches[1][0];
		$a2 = in_array( $matches[2][0], $duration_keys ) ? $matches[2][0] : '';
	} else {
		$a1 = absint( $time );
		$a2 = '';
	}

	if ( $a2 ) {
		$b  = array(
			'minute' => 60,
			'hour'   => 3600,
			'day'    => 3600 * 24,
			'week'   => 3600 * 24 * 7,
		);
		$a1 = $a1 * $b[ $a2 ];
	}

	return $a1;
}

/**
 * @param $user_id
 *
 * @return WP_Query
 */
function learn_press_get_own_courses( $user_id ) {
	$arr_query = array(
		'post_type'           => 'lp_course',
		'author'              => $user_id,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'posts_per_page'      => - 1,
	);
	$my_query  = new WP_Query( $arr_query );

	return $my_query;
}

/**
 * Return array list of currency positions.
 *
 * @param bool|string $currency
 *
 * @return array
 */
function learn_press_currency_positions( $currency = false ) {
	$positions = array(
		'left'             => __( 'Left', 'learnpress' ),
		'right'            => __( 'Right', 'learnpress' ),
		'left_with_space'  => __( 'Left with space', 'learnpress' ),
		'right_with_space' => __( 'Right with space', 'learnpress' ),
	);

	if ( false === $currency ) {
		$currency = learn_press_get_currency_symbol();
	}

	$settings = LP_Settings::instance();

	$thousands_separator = '';
	$decimals_separator  = $settings->get( 'decimals_separator', '.' );
	$number_of_decimals  = $settings->get( 'number_of_decimals', 2 );

	if ( $number_of_decimals > 0 ) {
		$example = '69' . $decimals_separator . str_repeat( '9', $number_of_decimals );
	} else {
		$example = '69';
	}

	foreach ( $positions as $pos => $text ) {
		switch ( $pos ) {
			case 'left':
				$text = sprintf( '%s ( %s%s )', $text, $currency, $example );
				break;
			case 'right':
				$text = sprintf( '%s ( %s%s )', $text, $example, $currency );
				break;
			case 'left_with_space':
				$text = sprintf( '%s ( %s %s )', $text, $currency, $example );
				break;
			case 'right_with_space':
				$text = sprintf( '%s ( %s %s )', $text, $example, $currency );
				break;
		}
		$positions[ $pos ] = $text;
	}

	$positions = apply_filters( 'learn_press_currency_positions', $positions );

	return apply_filters( 'learn-press/currency-positions', $positions );
}

/**
 * @return array
 */
function learn_press_get_payment_currencies() {
	return apply_filters( 'learn_press_get_payment_currencies', learn_press_currencies() );
}

/**
 * Get the list of currencies with code and name.
 *
 * @return  array
 * @version 3.0.1
 *
 * @author  ThimPress
 */
function learn_press_currencies() {
	return Config::instance()->get( 'currencies', 'settings' );
}

/**
 * Return list of common symbols of the currencies on the world.
 *
 * @return array
 * @version 3.0.1
 */
function learn_press_currency_symbols() {
	return Config::instance()->get( 'currency-symbols', 'settings' );
}

/**
 * Get current setting of currency.
 *
 * @return string
 */
function learn_press_get_currency(): string {
	$currency = LP_Settings::instance()->get( 'currency', 'USD' );

	return esc_html( apply_filters( 'learn-press/currency', $currency ) );
}

/**
 * Return currency symbol from the code.
 *
 * @param string $currency
 *
 * @return string
 */
function learn_press_get_currency_symbol( $currency = '' ) {
	if ( ! $currency ) {
		$currency = learn_press_get_currency();
	}
	$symbols         = learn_press_currency_symbols();
	$currency_symbol = $symbols[ $currency ] ?? '';

	$currency_symbol = apply_filters( 'learn_press_currency_symbol', $currency_symbol, $currency );

	return apply_filters( 'learn-press/currency-symbol', $currency_symbol, $currency );
}

/**
 * Get static page for LP page by name.
 *
 * @param string $key
 *
 * @return string
 * @editor tungnx
 * @modify 4.1.4
 */
function learn_press_get_page_link( string $key ): string {
	$page_id   = learn_press_get_page_id( $key );
	$key_cache = "lp/page-link/{$key}";
	$link      = LP_Cache::cache_load_first( 'get', $key_cache );
	if ( false !== $link ) {
		return $link;
	}

	if ( $page_id && get_post_status( $page_id ) == 'publish' ) {
		$permalink = get_permalink( $page_id );
		$link      = apply_filters( 'learn-press/get-page-link', trailingslashit( $permalink ), $page_id, $key );
		LP_Cache::cache_load_first( 'set', $key_cache, $link );
	}

	return $link;
}

/**
 * Get static page for LP page by name.
 *
 * @param string $key
 *
 * @return string
 */
function learn_press_get_page_title( $key ) {
	$page_id = LP_Settings::instance()->get( $key . '_page_id' );
	$title   = '';

	if ( $page_id && get_post_status( $page_id ) == 'publish' ) {
		$title = apply_filters( 'learn-press/get-page-title', get_the_title( $page_id ), $page_id, $key );
	}

	return apply_filters( 'learn-press/get-page-' . $key . '-title', $title, $page_id );
}

/**
 * get the ID of a course by order ID
 *
 * @param $order_id
 *
 * @return bool|mixed
 */
function learn_press_get_course_by_order( $order_id ) {
	$order_items = get_post_meta( $order_id, '_learn_press_order_items', true );

	if ( $order_items && $order_items->products ) {
		$array_keys = array_keys( $order_items->products );

		return reset( $array_keys );
	}

	return false;
}

/**
 * Convert a number of seconds to weeks/days/hours.
 *
 * @param int $secs
 *
 * @return bool|string
 */
function learn_press_seconds_to_weeks( int $secs = 0 ) {
	$secs = (int) $secs;

	if ( 0 === $secs ) {
		return false;
	}
	// variables for holding values.
	$mins  = 0;
	$hours = 0;
	$days  = 0;
	$weeks = 0;
	// calculations.
	if ( $secs >= 60 ) {
		$mins = (int) ( $secs / 60 );
		$secs = $secs % 60;
	}
	if ( $mins >= 60 ) {
		$hours = (int) ( $mins / 60 );
		$mins  = $mins % 60;
	}
	if ( $hours >= 24 ) {
		$days  = (int) ( $hours / 24 );
		$hours = $hours % 24;
	}
	if ( $days >= 7 ) {
		$weeks = (int) ( $days / 7 );
		$days  = $days % 7;
	}
	// format result.
	$result = '';
	if ( $weeks ) {
		$result .= sprintf( _n( '%s week', '%s weeks', $weeks, 'learnpress' ), $weeks ) . ' ';
	}

	if ( $days ) {
		$result .= sprintf( _n( '%s day', '%s days', $days, 'learnpress' ), $days ) . ' ';
	}

	if ( ! $weeks ) {
		if ( $hours ) {
			$result .= sprintf( _n( '%s hour', '%s hours', $hours, 'learnpress' ), $hours ) . ' ';
		}

		if ( $mins ) {
			$result .= sprintf( _n( '%s minute', '%s minutes', $mins, 'learnpress' ), $mins ) . ' ';
		}
	}

	$result = rtrim( $result );

	return $result;
}

function learn_press_user_maybe_is_a_teacher( $user = null ) {
	if ( ! $user ) {
		$user = learn_press_get_current_user();
	} elseif ( is_numeric( $user ) ) {
		$user = learn_press_get_user( $user );
	}
	if ( ! $user || $user instanceof LP_User_Guest ) {
		return false;
	}

	$role = $user->has_role( 'administrator' ) ? 'administrator' : false;
	if ( ! $role ) {
		$role = $user->has_role( 'lp_teacher' ) ? 'lp_teacher' : false;
	}

	return apply_filters( 'learn-press/user/is-teacher', $role, $user->get_id() );
}

function learn_press_become_teacher_sent( $user_id = 0 ) {
	if ( func_num_args() == 0 ) {
		$user_id = get_current_user_id();
	}

	return 'yes' === get_user_meta( $user_id, '_requested_become_teacher', true );
}

function _learn_press_translate_user_roles( $translations, $text, $context, $domain ) {
	$plugin_domain = 'learnpress';
	$roles         = array( 'LP Instructor' );

	if ( $context === 'User role' && in_array( $text, $roles ) && $domain !== $plugin_domain ) {
		return translate_with_gettext_context( $text, $context, $plugin_domain );
	}

	return $translations;
}

add_filter( 'gettext_with_context', '_learn_press_translate_user_roles', 10, 4 );

if ( ! function_exists( 'learn_press_send_json' ) ) {
	function learn_press_send_json( $data ) {
		echo '<-- LP_AJAX_START -->';
		echo wp_json_encode( $data );
		echo '<-- LP_AJAX_END -->';
		die;
	}
}

/**
 * Send json with success signal to browser.
 *
 * @param array|object|WP_Error $data
 *
 * @since 3.0.1
 */
function learn_press_send_json_error( $data = '' ) {
	$response = array( 'success' => false );

	if ( isset( $data ) ) {
		if ( is_wp_error( $data ) ) {
			$result = array();
			foreach ( $data->errors as $code => $messages ) {
				foreach ( $messages as $message ) {
					$result[] = array(
						'code'    => $code,
						'message' => $message,
					);
				}
			}

			$response['data'] = $result;
		} else {
			$response['data'] = $data;
		}
	}

	learn_press_send_json( $response );
}

/**
 * Send json with error signal to browser.
 *
 * @param array|object|WP_Error $data
 *
 * @since 3.0.0
 */
function learn_press_send_json_success( $data = '' ) {
	$response = array( 'success' => true );

	if ( isset( $data ) ) {
		$response['data'] = $data;
	}

	learn_press_send_json( $response );
}

/**
 * Check if ajax is calling then send json data.
 *
 * @param array $data
 * @param mixed $callback
 *
 * @return bool
 */
function learn_press_maybe_send_json( $data, $callback = null ) {
	if ( learn_press_is_ajax() ) {
		is_callable( $callback ) && call_user_func( $callback );
		$message = learn_press_get_messages( true );
		if ( empty( $data['message'] ) && $message ) {
			$data['message'] = $message;
		}
		learn_press_send_json( $data );
	}

	return false;
}

/**
 * Get data from request.
 *
 * @param string $key
 * @param mixed  $default
 * @param mixed  $hash
 *
 * @return mixed
 */
function learn_press_get_request( $key, $default = null, $hash = null ) {
	$return = $default;

	if ( $hash ) {
		if ( ! empty( $hash[ $key ] ) ) {
			$return = $hash[ $key ];
		}
	} else {
		if ( ! empty( $_POST[ $key ] ) ) {
			$return = LP_Helper::sanitize_params_submitted( $_POST[ $key ] );
		} elseif ( ! empty( $_GET[ $key ] ) ) {
			$return = LP_Helper::sanitize_params_submitted( $_GET[ $key ] );
		} elseif ( ! empty( $_REQUEST[ $key ] ) ) {
			$return = LP_Helper::sanitize_params_submitted( $_REQUEST[ $key ] );
		}
	}

	return $return;
}

/**
 * @return mixed
 */
function is_learnpress() {
	return apply_filters(
		'is_learnpress',
		( learn_press_is_course_archive() || learn_press_is_course_taxonomy() || learn_press_is_course() || learn_press_is_quiz() || learn_press_is_search() ) ? true : false
	);
}

if ( ! function_exists( 'learn_press_is_search' ) ) {
	function learn_press_is_search() {
		return array_key_exists( 's', $_REQUEST ) && array_key_exists( 'ref', $_REQUEST ) && sanitize_text_field( $_REQUEST['ref'] ) == 'course';
	}
}

if ( ! function_exists( 'learn_press_is_courses' ) ) {
	function learn_press_is_courses() {
		return learn_press_is_course_archive();
	}
}


if ( ! function_exists( 'learn_press_is_course_archive' ) ) {
	function learn_press_is_course_archive() {
		global $wp_query;

		$queried_object_id = ! empty( $wp_query->queried_object ) ? $wp_query->queried_object : 0;
		$is_tag            = defined( 'LEARNPRESS_IS_TAG' ) && LEARNPRESS_IS_TAG || is_tax( 'course_tag' );
		$is_category       = defined( 'LEARNPRESS_IS_CATEGORY' ) && LEARNPRESS_IS_CATEGORY || is_tax( 'course_category' );
		$page_id           = learn_press_get_page_id( 'courses' );

		return ( $is_category || $is_tag ) || is_post_type_archive( 'lp_course' ) || ( $page_id && ( $queried_object_id && is_page( $page_id ) ) );
	}
}

if ( ! function_exists( 'learn_press_is_course_tax' ) ) {
	function learn_press_is_course_tax() {
		return is_tax( get_object_taxonomies( LP_COURSE_CPT ) );
	}
}

if ( ! function_exists( 'learn_press_is_course_taxonomy' ) ) {
	function learn_press_is_course_taxonomy() {
		return ( defined( 'LEARNPRESS_IS_TAX' ) && LEARNPRESS_IS_TAX ) || learn_press_is_course_tax();
	}
}


if ( ! function_exists( 'learn_press_is_course_category' ) ) {
	function learn_press_is_course_category( $term = '' ) {
		return ( defined( 'LEARNPRESS_IS_CATEGORY' ) && LEARNPRESS_IS_CATEGORY ) || is_tax( 'course_category', $term );
	}
}


if ( ! function_exists( 'learn_press_is_course_tag' ) ) {
	function learn_press_is_course_tag( $term = '' ) {
		return ( defined( 'LEARNPRESS_IS_TAG' ) && LEARNPRESS_IS_TAG ) || is_tax( 'course_tag', $term );
	}
}

if ( ! function_exists( 'learn_press_is_course' ) ) {
	function learn_press_is_course(): bool {
		try {
			return is_singular( array( LP_COURSE_CPT ) );
		} catch ( Throwable $e ) {
		}

		return false;
	}
}

if ( ! function_exists( 'learn_press_is_lesson' ) ) {
	function learn_press_is_lesson() {
		return is_singular( array( LP_LESSON_CPT ) );
	}
}

if ( ! function_exists( 'learn_press_is_quiz' ) ) {
	function learn_press_is_quiz() {
		return is_singular( array( LP_QUIZ_CPT ) );
	}
}

function lp_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}

/**
 * Returns true when viewing profile page.
 *
 * @return bool
 */
function learn_press_is_profile() {
	$page_id = learn_press_get_page_id( 'profile' );

	if ( $page_id && is_page( $page_id ) || lp_content_has_shortcode( 'learn_press_profile' ) ) {
		return true;
	}

	return apply_filters( 'learn-press/is-profile', false );
}

/**
 * Return true if user is in checking out page
 *
 * @return bool
 */
function learn_press_is_checkout() {
	$page_id = learn_press_get_page_id( 'checkout' );

	if ( $page_id && is_page( $page_id ) ) {
		return true;
	}

	return apply_filters( 'learn-press/is-checkout', false );
}

function learn_press_is_instructors() {
	$page_id = learn_press_get_page_id( 'instructors' );

	if ( $page_id && is_page( $page_id ) ) {
		return true;
	}

	return apply_filters( 'learn-press/is-instructor', false );
}


/**
 * Return register permalink
 *
 * @return mixed
 */
function learn_press_get_register_url() {
	return apply_filters( 'learn_press_register_url', wp_registration_url() );
}

/**
 * Add a new notice into queue
 *
 * @param string
 * @param string
 *
 * @return mixed
 * @deprecated 4.2.5
 */
function learn_press_add_notice( $message, $type = 'updated' ) {
	_deprecated_function( __FUNCTION__, '4.2.5' );
	LP_Admin_Notice::instance()->add( $message, $type );
}

/**
 * Set user's cookie
 *
 * @param string $name
 * @param mixed $value
 * @param int $expire
 * @param bool $httponly
 *
 * @editor tungnx
 * @version 1.0.3
 */
function learn_press_setcookie( string $name = '', string $value = '', int $expire = 0, bool $httponly = true ) {
	@setcookie( $name, $value, $expire, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), $httponly );
}

/**
 * Clear cookie
 *
 * @param string $name
 */
function learn_press_remove_cookie( string $name = '' ) {
	if ( ! empty( $name ) ) {
		setcookie( $name, '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}

	if ( array_key_exists( $name, $_COOKIE ) ) {
		unset( $_COOKIE[ $name ] );
	}
}

/**
 * Filter the login url so third-party can be customized
 *
 * @param string $redirect
 *
 * @return mixed
 */
function learn_press_get_login_url( $redirect = null ) {
	$url          = wp_login_url( $redirect );
	$profile_page = learn_press_get_page_link( 'profile' );

	if ( 'yes' === LP_Settings::instance()->get( 'enable_login_profile' ) && $profile_page ) {
		$parse_url = parse_url( $url );
		$url       = $profile_page . ( ! empty( $parse_url['query'] ) ? '?' . $parse_url['query'] : '' );
	}

	return apply_filters( 'learn-press/login-url', $url );
}

/**
 * Add variable to an url by checking the permalink structure.
 *
 * @param string $name
 * @param string $value
 * @param string $url
 *
 * @return string
 */
function learn_press_get_endpoint_url( $name, $value, $url ) {
	if ( ! $url ) {
		$url = get_permalink();
	}

	// Map endpoint to options
	$name = isset( LearnPress::instance()->query_vars[ $name ] ) ? LearnPress::instance()->query_vars[ $name ] : $name;

	if ( get_option( 'permalink_structure' ) ) {
		if ( strstr( $url, '?' ) ) {
			$query_string = '?' . parse_url( $url, PHP_URL_QUERY );
			$url          = current( explode( '?', $url ) );
		} else {
			$query_string = '';
		}
		$url = trailingslashit( $url ) . ( $name ? $name . '/' : '' ) . $value . $query_string;

	} else {
		$url = esc_url_raw( add_query_arg( $name, $value, $url ) );
	}

	return apply_filters( 'learn_press_get_endpoint_url', esc_url_raw( $url ), $name, $value, $url );
}

/**
 * @deprecated 4.2.3
 */
function learn_press_is_yes( $value ) {
	_deprecated_function( __FUNCTION__, '4.2.3' );
	return ( $value === 1 ) || ( $value === '1' ) || ( $value == 'yes' ) || ( $value == true ) || ( $value == 'on' );
}

/**
 * @param mixed $value
 *
 * @return bool
 * @deprecated 4.2.3
 */
function _is_false_value( $value ) {
	_deprecated_function( __FUNCTION__, '4.2.3' );
	if ( is_numeric( $value ) ) {
		return $value == 0;
	} elseif ( is_string( $value ) ) {
		return ( empty( $value ) || is_null( $value ) || in_array( $value, array( 'no', 'off', 'false' ) ) );
	}

	return ! ! $value;
}

/**
 * Map the query vars from LP to query vars of WP core
 * when WP parse the requesting.
 */
function learn_press_parse_request() {
	global $wp;

	// Map query vars to their keys, or get them if endpoints are not supported
	foreach ( LearnPress::instance()->query_vars as $key => $var ) {
		if ( isset( $_GET[ $var ] ) ) {
			$wp->query_vars[ $key ] = LP_Helper::sanitize_params_submitted( $_GET[ $var ] ?? '' );
		} elseif ( isset( $wp->query_vars[ $var ] ) ) {
			$wp->query_vars[ $key ] = LP_Helper::sanitize_params_submitted( $wp->query_vars[ $var ] ?? '' );
		}
	}
}

add_action( 'parse_request', 'learn_press_parse_request' );

if ( ! function_exists( 'learn_press_reset_auto_increment' ) ) {
	/**
	 * Reset AUTO INCREMENT of the table.
	 *
	 * @param $table
	 */
	function learn_press_reset_auto_increment( $table ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->prefix}$table AUTO_INCREMENT = %d", 1 ) );
	}
}

/**
 * Get the cart object in checkout page
 *
 * @return LP_Cart
 * @deprecated 4.2.0
 */
function learn_press_get_checkout_cart() {
	_deprecated_function( __FUNCTION__, '4.2.0', 'LearnPress::instance()->cart' );
	return apply_filters( 'learn_press_checkout_cart', LearnPress::instance()->cart );
}

function learn_press_user_time( $time, $format = 'timestamp' ) {
	if ( is_string( $time ) ) {
		$time = @strtotime( $time );
	}
	$time = $time + ( get_option( 'gmt_offset' ) - $_COOKIE['timezone'] / 60 ) * HOUR_IN_SECONDS;
	switch ( $format ) {
		case 'timestamp':
			return $time;
		default:
			return date( 'Y-m-d H:i:s', $time );
	}
}

function learn_press_get_current_version() {
	$data = get_plugin_data( LP_PLUGIN_FILE, $markup = true, $translate = true );

	return $data['Version'];
}

/**
 * Get current tab is displaying in user profile.
 * If there is no tab then get the first tab in
 * the list of tabs.
 *
 * @param bool $default
 *
 * @return mixed|string
 * @deprecated 4.2.2
 * addon commission 4.0.2 still use this function
 */
function learn_press_get_current_profile_tab( $default = true ) {
	global $wp_query, $wp;
	$current = '';

	// Only load on profile page.
	if ( ! LP_Page_Controller::is_page_profile() ) {
		return false;
	}

	if ( ! empty( $_REQUEST['tab'] ) ) {
		$current = LP_Helper::sanitize_params_submitted( $_REQUEST['tab'] );
	} elseif ( ! empty( $wp_query->query_vars['tab'] ) ) {
		$current = $wp_query->query_vars['tab'];
	} elseif ( ! empty( $wp->query_vars['view'] ) ) {
		$current = $wp->query_vars['view'];
	} else {
		$tabs = learn_press_get_user_profile_tabs();
		if ( $default && $tabs ) {
			// Fixed for array_keys does not work with ArrayAccess instance
			if ( $tabs instanceof LP_Profile_Tabs ) {
				$tabs = $tabs->tabs();
			}

			$tab_keys = array_keys( $tabs );
			$current  = reset( $tab_keys );
		}
	}

	return $current;
}

//add_action( 'init', 'learn_press_get_current_profile_tab' );

function learn_press_profile_tab_exists( $tab ) {
	$tabs = learn_press_get_user_profile_tabs();

	if ( $tabs ) {
		return ! empty( $tabs[ $tab ] ) ? true : false;
	}

	return false;
}

function learn_press_single_term_title( $prefix = '', $display = true ) {
	$term = get_queried_object();

	if ( ! $term ) {
		return '';
	}

	if ( learn_press_is_course_category() ) {
		$term_name = apply_filters( 'single_course_category_title', $term->name );
	} elseif ( learn_press_is_course_tag() ) {
		$term_name = apply_filters( 'single_course_tag_title', $term->name );
	} elseif ( learn_press_is_course_taxonomy() ) {
		$term_name = apply_filters( 'single_course_term_title', $term->name );
	} else {
		return single_term_title( $prefix, $display );
	}

	if ( empty( $term_name ) ) {
		return single_term_title( $prefix, $display );
	}

	if ( $display ) {
		echo wp_kses_post( $prefix . $term_name );
	}

	return $prefix . $term_name;
}

/**
 * Control the template file if user is searching course.
 * Use the template of archive course to display the
 * result if there is a flag in request to search course.
 *
 * @param string $template
 *
 * @return string
 */
function learn_press_search_template( $template ) {
	if ( ! empty( $_REQUEST['ref'] ) && sanitize_text_field( $_REQUEST['ref'] ) == 'course' ) {
		$template = learn_press_locate_template( 'archive-course.php' );
	}

	return $template;
}

/**
 * Auto enroll user to a course after an order is completed
 * if the option auto-enroll is turn on.
 *
 * @param int $order_id
 *
 * @return mixed
 * @editor tungnx
 */
function learn_press_auto_enroll_user_to_courses( $order_id ) {
	_deprecated_function( __FUNCTION__, '4.1.3' );
}

// add_action( 'learn_press_order_status_completed', 'learn_press_auto_enroll_user_to_courses' );

/**
 * Return true if enable cart
 *
 * @return bool
 */
function learn_press_is_enable_cart() {
	return defined( 'LP_ENABLE_CART' ) && LP_ENABLE_CART == true;
}

/**
 * Short way to get checkout object
 *
 * @param array
 *
 * @return LP_Checkout
 */
function learn_press_get_checkout( $args = null ) {
	$checkout = LP_Checkout::instance();

	if ( is_array( $args ) ) {
		foreach ( $args as $k => $v ) {
			$checkout->{$k} = $v;
		}
	}

	return $checkout;
}

if ( defined( 'LP_ENABLE_CART' ) && LP_ENABLE_CART ) {
	add_filter( 'learn_press_checkout_settings', '_learn_press_cart_settings', 10, 2 );
	function _learn_press_cart_settings( $settings, $class ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => __( 'Cart', 'learnpress' ),
					'type'  => 'title',
				),
				array(
					'title'   => __( 'Enable cart', 'learnpress' ),
					'desc'    => __(
						'Check this option to enable users to purchase multiple courses at one time.',
						'learnpress'
					),
					'id'      => $class->get_field_name( 'enable_cart' ),
					'default' => 'yes',
					'type'    => 'checkbox',
				),
				array(
					'title'   => __( 'Add to cart redirect', 'learnpress' ),
					'desc'    => __( 'Redirect to checkout immediately after adding the course to the cart.', 'learnpress' ),
					'id'      => $class->get_field_name( 'redirect_after_add' ),
					'default' => 'yes',
					'type'    => 'checkbox',
				),
				array(
					'title'   => __( 'AJAX add to cart', 'learnpress' ),
					'desc'    => __( 'Using AJAX to add the course to the cart.', 'learnpress' ),
					'id'      => $class->get_field_name( 'ajax_add_to_cart' ),
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'   => __( 'Cart page', 'learnpress' ),
					'id'      => $class->get_field_name( 'cart_page_id' ),
					'default' => '',
					'type'    => 'pages-dropdown',
				),
			)
		);

		return $settings;
	}
} else {
	add_filter( 'learn_press_enable_cart', '_learn_press_enable_cart', 1000 );
	function _learn_press_enable_cart( $r ) {
		return false;
	}

	add_filter( 'learn_press_get_template', '_learn_press_enroll_button', 1000, 5 );
	function _learn_press_enroll_button( $located, $template_name, $args, $template_path, $default_path ) {
		if ( $template_name == 'single-course/enroll-button.php' ) {
			$located = learn_press_locate_template(
				'single-course/enroll-button-new.php',
				$template_path,
				$default_path
			);
		}

		return $located;
	}
}

/**
 * Returns checkout url from setting
 *
 * @return string
 */
function learn_press_get_checkout_url() {
	$checkout_url = learn_press_get_page_link( 'checkout' );

	return apply_filters( 'learn_press_get_checkout_url', $checkout_url );
}

/**
 * @return string
 */
function learn_press_checkout_needs_payment() {
	return LearnPress::instance()->cart->needs_payment();
}

/**
 * Return plugin basename
 *
 * @param string $filepath
 *
 * @return string
 */
/*
function learn_press_plugin_basename( $filepath ) {
	$file          = str_replace( '\\', '/', $filepath );
	$file          = preg_replace( '|/+|', '/', $file );
	$plugin_dir    = str_replace( '\\', '/', WP_PLUGIN_DIR );
	$plugin_dir    = preg_replace( '|/+|', '/', $plugin_dir );
	$mu_plugin_dir = str_replace( '\\', '/', WPMU_PLUGIN_DIR );
	$mu_plugin_dir = preg_replace( '|/+|', '/', $mu_plugin_dir );
	$sp_plugin_dir = dirname( $filepath );
	$sp_plugin_dir = dirname( $sp_plugin_dir );
	$sp_plugin_dir = str_replace( '\\', '/', $sp_plugin_dir );
	$sp_plugin_dir = preg_replace( '|/+|', '/', $sp_plugin_dir );

	$file = preg_replace(
		'#^' . preg_quote( $sp_plugin_dir, '#' ) . '/|^' . preg_quote(
			$plugin_dir,
			'#'
		) . '/|^' . preg_quote(
			$mu_plugin_dir,
			'#'
		) . '/#',
		'',
		$file
	);
	$file = trim( $file, '/' );

	return strtolower( $file );
}*/

/**
 * Update log data for each LP version into wp option.
 *
 * @param string $version
 * @param mixed  $data
 */
function learn_press_update_log( $version, $data ) {
	$logs = get_option( 'learn_press_update_logs' );
	if ( ! $logs ) {
		$logs = array( $version => $data );
	} else {
		$logs[ $version ] = $data;
	}
	update_option( 'learn_press_update_logs', $logs );
}

/**
 * Output variables to screen for debugging.
 */
function learn_press_debug() {
	$args  = func_get_args();
	$debug = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );

	echo '<pre>';
	print_r( $debug );
	$arg = false;

	if ( $args ) {
		foreach ( $args as $arg ) {
			echo "\n======LearnPress Debug=======\n";
			print_r( $arg );
			echo "\n=============================\n";
		}
	}
	echo '</pre>';

	if ( true === $arg ) {
		die( __FUNCTION__ );
	}
}

function learn_press_get_requested_post_type() {
	global $pagenow;
	if ( $pagenow == 'post-new.php' && ! empty( $_REQUEST['post_type'] ) ) {
		$post_type = LP_Helper::sanitize_params_submitted( $_REQUEST['post_type'] );
	} else {
		$post_id   = learn_press_get_post();
		$post_type = learn_press_get_post_type( $post_id );
	}

	return $post_type;
}

/**
 * Get human string from grade slug.
 *
 * @param string $slug
 *
 * @return string
 */
function learn_press_get_graduation_text( $slug ) {
	switch ( $slug ) {
		case 'passed':
			$text = esc_html__( 'Passed', 'learnpress' );
			break;
		case 'failed':
			$text = esc_html__( 'Failed', 'learnpress' );
			break;
		case 'in-progress':
			$text = esc_html__( 'In Progress', 'learnpress' );
			break;
		default:
			$text = $slug;
	}

	return apply_filters( 'learn-press/get-graduation-text', $text, $slug );
}

if ( ! function_exists( 'learn_press_is_negative_value' ) ) {
	function learn_press_is_negative_value( $value ) {
		$return = in_array( $value, array( 'no', 'off', 'false', '0' ) ) || ! $value || $value == '' || $value == null;

		return $return;
	}
}

/**
 * Filter to comment reply link to fix bug the link is invalid for
 * lesson or quiz.
 *
 * @param string     $link
 * @param array      $args
 * @param WP_Comment $comment
 * @param WP_Post    $post
 *
 * @return string
 */
function learn_press_comment_reply_link( $link, $args = array(), $comment = null, $post = null ) {

	$post_type = learn_press_get_post_type( $post );

	if ( ! learn_press_is_support_course_item_type( $post_type ) ) {
		return $link;
	}

	$course_item = LP_Global::course_item();

	if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
		$link = sprintf(
			'<a rel="nofollow" class="comment-reply-login" href="%s">%s</a>',
			esc_url_raw( wp_login_url( get_permalink() ) ),
			$args['login_text']
		);
	} elseif ( $course_item ) {
		$onclick = sprintf(
			'return addComment.moveForm( "%1$s-%2$s", "%2$s", "%3$s", "%4$s" )',
			$args['add_below'],
			$comment->comment_ID,
			$args['respond_id'],
			$post->ID
		);

		$link = sprintf(
			"<a rel='nofollow' class='comment-reply-link' href='%s' onclick='%s' aria-label='%s'>%s</a>",
			esc_url_raw(
				add_query_arg(
					array(
						'replytocom' => $comment->comment_ID,
					),
					$course_item->get_permalink()
				)
			) . '#' . $args['respond_id'],
			$onclick,
			esc_attr( sprintf( $args['reply_to_text'], $comment->comment_author ) ),
			$args['reply_text']
		);
	}

	return $link;
}

add_filter( 'comment_reply_link', 'learn_press_comment_reply_link', 10, 4 );

/**
 * Sanitize content of tooltip
 *
 * @param string $tooltip
 * @param bool   $html
 *
 * @return string
 */
function learn_press_sanitize_tooltip( $tooltip, $html = false ) {
	if ( $html ) {
		$tooltip = htmlspecialchars(
			wp_kses(
				html_entity_decode( $tooltip ),
				array(
					'br'     => array(),
					'em'     => array(),
					'strong' => array(),
					'small'  => array(),
					'span'   => array(),
					'ul'     => array(),
					'li'     => array(),
					'ol'     => array(),
					'p'      => array(),
				)
			)
		);
	} else {
		$tooltip = esc_attr( $tooltip );
	}

	return $tooltip;
}

function learn_press_tooltip( $tooltip, $html = false ) {
	$tooltip = learn_press_sanitize_tooltip( $tooltip, $html );
	echo '<span class="learn-press-tooltip" data-tooltip="' . esc_attr( $tooltip ) . '"></span>';
}

/**
 * Get default static pages of LP.
 *
 * @return array
 *
 * @since 3.0.0
 * @deprecated 4.2.3
 */
function learn_press_static_page_ids() {
	$pages = LP_Object_Cache::get( 'static-page-ids', 'learn-press' );

	if ( false === $pages ) {
		$pages = array(
			'checkout'         => learn_press_get_page_id( 'checkout' ),
			'courses'          => learn_press_get_page_id( 'courses' ),
			'profile'          => learn_press_get_page_id( 'profile' ),
			'become_a_teacher' => learn_press_get_page_id( 'become_a_teacher' ),
		);

		foreach ( $pages as $name => $id ) {
			if ( ! get_post( $id ) ) {
				$pages[ $name ] = 0;
			}
		}

		LP_Object_Cache::set( 'static-page-ids', $pages, 'learn-press' );
	}

	return apply_filters( 'learn-press/static-page-ids', $pages );
}

/**
 * Callback function for sorting to array|object by key|prop priority.
 *
 * @param array|object $a
 * @param array|object $b
 *
 * @return int
 * @since 3.0.0
 */
function learn_press_sort_list_by_priority_callback( $a, $b ) {
	$a_priority = null;
	$b_priority = null;

	if ( is_array( $a ) && array_key_exists( 'priority', $a ) ) {
		$a_priority = $a['priority'];
	} elseif ( is_object( $a ) ) {
		if ( is_callable( array( $a, 'get_priority' ) ) ) {
			$a_priority = $a->get_priority();
		} elseif ( property_exists( $a, 'priority' ) ) {
			$a_priority = $a->priority;
		}
	}

	if ( is_array( $b ) && array_key_exists( 'priority', $b ) ) {
		$b_priority = $b['priority'];
	} elseif ( is_object( $b ) ) {
		if ( is_callable( array( $b, 'get_priority' ) ) ) {
			$b_priority = $b->get_priority();
		} elseif ( property_exists( $b, 'priority' ) ) {
			$b_priority = $b->priority;
		}
	}

	if ( $a_priority === $b_priority ) {
		return 0;
	}

	return ( $a_priority < $b_priority ) ? - 1 : 1;
}

/**
 * Localize date with custom format.
 *
 * @param int|bool $timestamp
 * @param string $format
 * @param bool   $gmt
 *
 * @return string
 * @since 3.0.0
 * @deprcated 4.2.6 use LP_Datetime format instead
 */
function learn_press_date_i18n( $timestamp = 0, string $format = '', bool $gmt = false ): string {
	if ( ! $format ) {
		$format = get_option( 'date_format' );
	}

	$date_str = date_i18n( $format, $timestamp, $gmt );
	if ( ! is_string( $date_str ) ) {
		$date_str = '';
	}

	return $date_str;
}

/**
 * Get item types of course support for blocking. Default is lp_lesson
 *
 * @return array
 * @since 3.0.0
 */
function learn_press_get_block_course_item_types() {
	return apply_filters( 'learn-press/block-course-item-types', array( LP_LESSON_CPT, LP_QUIZ_CPT ) );
}

/**
 * Get post type of a post from cache.
 * If there is no data stored in cache then
 * get it from WP API.
 *
 * @param int|WP_Post $post
 *
 * @return string
 * @since 3.1.0
 */
function learn_press_get_post_type( $post ) {
	$post_types = LP_Object_Cache::get( 'post-types', 'learn-press' );

	if ( false === $post_types ) {
		$post_types = array();
	}

	if ( is_object( $post ) ) {
		$post_id = $post->ID;
	} else {
		$post_id = absint( $post );
	}

	if ( empty( $post_types[ $post_id ] ) ) {
		$post_type              = get_post_type( $post_id );
		$post_types[ $post_id ] = $post_type;
		LP_Object_Cache::set( 'post-types', $post_types, 'learn-press' );
	} else {
		$post_type = $post_types[ $post_id ];
	}

	return $post_type;
}

/**
 * Update option to enable shuffle themes for ad.
 *
 * @since 3.2.1
 */
function _learn_press_schedule_enable_shuffle_themes() {
	update_option( 'learn_press_ad_shuffle_themes', 'yes' );
}

add_action( 'learn-press/schedule-enable-shuffle-themes', '_learn_press_schedule_enable_shuffle_themes' );

/**
 * Return localize script data for admin.
 *
 * @return array
 * @since 3.2.6
 * @version 1.0.1
 */
function learn_press_global_script_params(): array {
	$localize = [
		'ajax'       => admin_url( 'admin-ajax.php' ),
		'plugin_url' => LearnPress::instance()->plugin_url(),
		'siteurl'    => home_url(),
		//'current_url' => learn_press_get_current_url(),
		'theme'      => get_stylesheet(),
		'localize'   => array(
			'button_ok'     => __( 'OK', 'learnpress' ),
			'button_cancel' => __( 'Cancel', 'learnpress' ),
			'button_yes'    => __( 'Yes', 'learnpress' ),
			'button_no'     => __( 'No', 'learnpress' ),
		),
		'rest'       => esc_url_raw( rest_url() ),
		'nonce'      => wp_create_nonce( 'wp_rest' ),
		'is_admin'   => current_user_can( ADMIN_ROLE ),
	];

	return apply_filters( 'lp/admin/localize/scripts', $localize );
}

/**
 * Return list types of questions that support answer options.
 *
 * @return array
 * @since 3.3.0
 */
function learn_press_get_question_support_answer_options() {
	$questions = learn_press_get_question_support_feature( 'answer-options' );

	return apply_filters( 'learn-press/questions-support-answer-options', $questions );
}

/**
 * Return list types of question that support a feature.
 *
 * @param string $feature
 *
 * @return array
 * @since 3.3.0
 */
function learn_press_get_question_support_feature( $feature ) {
	$questions = array();
	$types     = LP_Global::get_object_supports( 'question' );

	if ( $types ) {
		foreach ( $types as $type => $features ) {
			if ( array_key_exists( $feature, $features ) ) {
				$questions[] = $type;
			}
		}
	}

	return $questions;
}

/**
 * LP Cookie
 *
 * @param $name
 * @param $namespace
 *
 * @return mixed|null
 */
function learn_press_cookie_get( $name, $namespace = 'LP' ) {
	if ( $namespace ) {
		$cookie = ! empty( $_COOKIE[ $namespace ] ) ? (array) json_decode( LP_Helper::sanitize_params_submitted( stripslashes( $_COOKIE[ $namespace ] ), 'html' ) ) : array();
	} else {
		$cookie = $_COOKIE;
	}

	return $cookie[ $name ] ?? null;
}

/**
 * Get default methods to evaluate course results.
 *
 * @param string $return - Optional. 'keys' will return keys instead of all.
 *
 * @return array
 * @since 3.x.x
 */
function learn_press_course_evaluation_methods( $postid, $return = '', $final_quizz_passing = '' ) {
	$final_quiz_btn = sprintf(
		'<a href="#" class="lp-metabox-get-final-quiz" data-postid="%d" data-loading="%s">%s</a>',
		$postid,
		esc_attr__( 'Loading...', 'learnpress' ),
		esc_html__( 'Get A Passing Grade', 'learnpress' )
	);

	$evaluations_desc = array(
		'evaluate_lesson'     => sprintf(
			'<p>%s<br/>%s</p>',
			__( 'Evaluate by the number of completed lessons per the total number of lessons.', 'learnpress' ),
			__( 'E.g: If a course has 10 lessons and a user completes 5 lessons, then the result is 5/10 (50%).', 'learnpress' )
		),
		'evaluate_final_quiz' => __(
			'Evaluate by the result of the final quiz in the course. You have to add a quiz at the end of the course.',
			'learnpress'
		),
		'evaluate_quiz'       => sprintf(
			'<p>%s<br/>%s</p>',
			__( 'Evaluate by the number of passed quizzes per the total number of quizzes.', 'learnpress' ),
			__(
				'E.g: If the course has 10 quizzes and the user passes 5 quizzes, then the result is 5/10 (50%).',
				'learnpress'
			)
		),
		'evaluate_questions'  => sprintf(
			'<p>%s<br/>%s</p>',
			__( 'Evaluate by the number of correct answers per the total number of questions.', 'learnpress' ),
			__(
				'E.g: If the course has 10 questions and the user corrects 5 questions, then the result is 5/10 (50%).',
				'learnpress'
			)
		),
		'evaluate_mark'       => __( 'Evaluate by the number of achieved scores per the total score of the questions.', 'learnpress' ),
	);

	$methods = apply_filters(
		'learnpress/course-evaluation/methods',
		array(
			'evaluate_lesson'     => sprintf(
				'%s %s',
				__( 'Evaluate via lessons', 'learnpress' ),
				learn_press_quick_tip( $evaluations_desc['evaluate_lesson'], false )
			),
			'evaluate_final_quiz' => sprintf(
				'%s %s %s %s',
				__( 'Evaluate via results of the final quiz', 'learnpress' ),
				learn_press_quick_tip( $evaluations_desc['evaluate_final_quiz'], false ),
				$final_quiz_btn,
				$final_quizz_passing
			),
			'evaluate_quiz'       => sprintf(
				'%s %s',
				__( 'Evaluate via passed quizzes', 'learnpress' ),
				learn_press_quick_tip( $evaluations_desc['evaluate_quiz'], false )
			),
			'evaluate_questions'  => sprintf(
				'%s %s',
				__( 'Evaluate via questions', 'learnpress' ),
				learn_press_quick_tip( $evaluations_desc['evaluate_questions'], false )
			),
			'evaluate_mark'       => sprintf(
				'%s %s',
				__( 'Evaluate via mark', 'learnpress' ),
				learn_press_quick_tip( $evaluations_desc['evaluate_mark'], false )
			),
		),
		$postid
	);

	return apply_filters(
		'learn-press/course-evaluation-methods',
		$return === 'keys' ? array_keys( $methods ) : $methods,
		$return
	);
}

/**
 * Get max retrying quiz allowed.
 *
 * @param int $quiz_id
 * @param int $course_id
 *
 * @return int
 * @since 4.0.0
 */
function learn_press_get_quiz_max_retrying( $quiz_id = 0, $course_id = 0 ) {
	return apply_filters( 'learn-press/max-retry-quiz-allowed', 1, $quiz_id, $course_id );
}

/**
 * Get slug for status of course/lesson/quiz if user
 * completed/finished and graduation is passed.
 *
 * @param string $type
 *
 * @return string
 * @since 4.0.0
 */
function learn_press_user_item_in_progress_slug( $type = '' ) {
	return apply_filters( 'learn-press/user-item-in-progress-slug', 'in-progress', $type );
}

/**
 * Get slug for status of course/lesson/quiz if user
 * completed/finished and result is under-evaluation
 *
 * @param string $item - Optional. Type of item
 *
 * @return string
 * @since 4.0.0
 */
function learn_press_user_item_under_evaluation_slug( $item = '' ) {
	return apply_filters( 'learn-press/user-item-under-evaluation-slug', 'in-progress', $item );
}

function learn_press_is_enrolled_slug( $slug ) {
	return in_array(
		$slug,
		array(
			'in-progress',
			'enrolled',
		)
	);
}

/**
 * @return array
 * @since 4.0.0
 */
function lp_item_course_class( $class = array() ) {
	$classes = array_merge(
		$class,
		array( 'learn-press-courses' )
	);
	echo 'class="' . esc_attr( implode( ' ', apply_filters( 'lp_item_course_class', $classes ) ) ) . '"';
}

//require_once dirname( __FILE__ ) . '/lp-custom-hooks.php';

// Notify update message for LearnPress plugin
/*add_action(
	'in_plugin_update_message-learnpress/learnpress.php',
	function ( $plugin_data ) {
		if ( version_compare( $plugin_data['new_version'], LEARNPRESS_VERSION, '<=' ) ) {
			return;
		}

		echo sprintf(
			'<hr/><h3>%s</h3><div>%s</div>',
			esc_html__( 'Heads up! Please backup before upgrading!', 'learnpress' ),
			esc_html__( 'The latest update includes some substantial changes across different areas of the plugin. We highly recommend you backup your site before upgrading, and make sure you first update in a staging environment', 'learnpress' )
		);
	}
);*/

/**
 * If Elementor Pro set Theme builder type "Archive", will not show content on page "Archive course"
 *
 * @editor tungnx
 * @author nhamdv
 *
 * @since 4.0.6
 * @version 1.0.1
 */
add_filter(
	'elementor/theme/get_location_templates/template_id',
	function ( $theme_template_id ) {
		$elementor_template_type = get_post_meta( $theme_template_id, '_elementor_template_type', true );

		if ( in_array( $elementor_template_type, array( 'archive' ) ) ) {
			if ( LP_PAGE_COURSES === LP_Page_Controller::page_current() && class_exists( 'ElementorPro\Modules\ThemeBuilder\Conditions\Archive' ) ) {
				return false;
			}
		}

		return $theme_template_id;
	}
);
