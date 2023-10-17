<?php
/**
 * LearnPress Core Functions
 * Define common functions for both front-end and back-end
 *
 * @author   ThimPress
 * @package  LearnPress/Functions
 * @version  1.0
 */

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

/**
 * Get instance of a CURD class by type
 *
 * @param string $type
 *
 * @return bool|LP_Course_CURD|LP_User_CURD|LP_Quiz_CURD|LP_Question_CURD
 * @deprecated 4.1.7.2
 */
/*function learn_press_get_curd( $type ) {
	$curds = array(
		'user'     => 'LP_User_CURD',
		'course'   => 'LP_Course_CURD',
		'quiz'     => 'LP_Quiz_CURD',
		'question' => 'LP_Question_CURD',
	);

	$curd = false;

	if ( ! empty( $curds[ $type ] ) && class_exists( $curds[ $type ] ) ) {
		$curd = new $curds[ $type ]();
	}

	return apply_filters( 'learn-press/curd', $curd, $type, $curds );
}*/

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
 * Compares an url with current URL user is viewing
 *
 * @param string $url
 *
 * @return bool
 */
function learn_press_is_current_url( $url ) {
	$current_url = learn_press_get_current_url();

	return ( $current_url && $url ) && strcmp( $current_url, learn_press_sanitize_url( $url ) ) == 0;
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
				<?php echo  wp_kses_post( $links ); ?>
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
		$pages ++;
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
 * Send email notification.
 *
 * @param string $to .
 * @param string $action .
 * @param array  $vars .
 *
 * @return bool
 * @deprecated 4.1.7.2
 */
/*function learn_press_send_mail( $to = '', $action = '', $vars = array() ) {
	$email_settings = LP_Settings::instance();

	if ( ! $email_settings->get( $action . '.enable' ) ) {
		return "The action {$action} doesnt support";
	}

	$user = get_user_by( 'email', $to );

	$vars['log_in'] = apply_filters( 'learn_press_site_url', get_home_url() );

	// Send email.
	$email = new LP_Email();
	$email->set_action( $action );
	$email->parse_email( $vars );
	$email->add_recipient( $to );

	return $email->send();
}*/

/*
 * Send email notification when a course be published
 * @deprecated 4.1.7.2
 */
/*function learn_press_publish_course( $new_status, $old_status, $post ) {
	if ( $old_status == 'pending' && $new_status == 'publish' && $post->post_type == 'lp_course' ) {
		$instructor = get_userdata( $post->post_author );
		$mail_to    = $instructor->user_email;

		learn_press_send_mail(
			$mail_to,
			'published_course',
			apply_filters(
				'learn_press_vars_enrolled_course',
				array(
					'user_name'   => $instructor->display_name,
					'course_name' => $post->post_title,
					'course_link' => get_permalink( $post->ID ),
				),
				$post,
				$instructor
			)
		);
	}
}*/

//add_action( 'transition_post_status', 'learn_press_publish_course', 10, 3 );

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
 * @version 3.0.0
 *
 * @author  ThimPress
 */
function learn_press_currencies() {
	$currencies = array(
		'AFN' => __( 'Afghan afghani', 'learnpress' ),
		'ALL' => __( 'Albanian lek', 'learnpress' ),
		'DZD' => __( 'Algerian dinar', 'learnpress' ),
		'EUR' => __( 'Euro', 'learnpress' ),
		'AOA' => __( 'Angolan kwanza', 'learnpress' ),
		'XCD' => __( 'East Caribbean dollar', 'learnpress' ),
		'ARS' => __( 'Argentine peso', 'learnpress' ),
		'AMD' => __( 'Armenian dram', 'learnpress' ),
		'AWG' => __( 'Aruban florin', 'learnpress' ),
		'AUD' => __( 'Australian dollar', 'learnpress' ),
		'AZN' => __( 'Azerbaijani manat', 'learnpress' ),
		'BSD' => __( 'Bahamian dollar', 'learnpress' ),
		'BHD' => __( 'Bahraini dinar', 'learnpress' ),
		'BDT' => __( 'Bangladeshi taka', 'learnpress' ),
		'BBD' => __( 'Barbadian dollar', 'learnpress' ),
		'BYR' => __( 'Belarusian ruble', 'learnpress' ),
		'BZD' => __( 'Belizean dollar', 'learnpress' ),
		'XOF' => __( 'West African CFA franc', 'learnpress' ),
		'BMD' => __( 'Bermudian dollar', 'learnpress' ),
		'BTN' => __( 'Bhutanese ngultrum', 'learnpress' ),
		'BOB' => __( 'Bolivian boliviano', 'learnpress' ),
		'USD' => __( 'US dollar', 'learnpress' ),
		'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'learnpress' ),
		'BWP' => __( 'Botswana pula', 'learnpress' ),
		'BRL' => __( 'Brazilian real', 'learnpress' ),
		'BND' => __( 'Brunei dollar', 'learnpress' ),
		'BGN' => __( 'Bulgarian lev', 'learnpress' ),
		'MMK' => __( 'Burmese kyat', 'learnpress' ),
		'BIF' => __( 'Burundian franc', 'learnpress' ),
		'KHR' => __( 'Cambodian riel', 'learnpress' ),
		'XAF' => __( 'Central African CFA franc', 'learnpress' ),
		'CAD' => __( 'Canadian dollar', 'learnpress' ),
		'CVE' => __( 'Cape Verdean escudo', 'learnpress' ),
		'KYD' => __( 'Cayman Islands dollar', 'learnpress' ),
		'CLP' => __( 'Chilean peso', 'learnpress' ),
		'CNY' => __( 'Chinese renminbi', 'learnpress' ),
		'COP' => __( 'Colombian peso', 'learnpress' ),
		'KMF' => __( 'Comorian franc', 'learnpress' ),
		'CDF' => __( 'Congolese franc', 'learnpress' ),
		'NZD' => __( 'New Zealand dollar', 'learnpress' ),
		'CRC' => __( 'Costa Rican colón', 'learnpress' ),
		'HRK' => __( 'Croatian kuna', 'learnpress' ),
		'CUC' => __( 'Cuban peso', 'learnpress' ),
		'ANG' => __( 'Netherlands Antilles guilder', 'learnpress' ),
		'CZK' => __( 'Czech koruna', 'learnpress' ),
		'DKK' => __( 'Danish krone', 'learnpress' ),
		'DJF' => __( 'Djiboutian franc', 'learnpress' ),
		'DOP' => __( 'Dominican peso', 'learnpress' ),
		'EGP' => __( 'Egyptian pound', 'learnpress' ),
		'SVC' => __( 'Salvadoran colón', 'learnpress' ),
		'ERN' => __( 'Eritrean nakfa', 'learnpress' ),
		'ETB' => __( 'Ethiopian birr', 'learnpress' ),
		'FKP' => __( 'Falkland Islands pound', 'learnpress' ),
		'FJD' => __( 'Fijian dollar', 'learnpress' ),
		'XPF' => __( 'CFP franc', 'learnpress' ),
		'GMD' => __( 'Gambian dalasi', 'learnpress' ),
		'GEL' => __( 'Georgian lari', 'learnpress' ),
		'GHS' => __( 'Ghanian cedi', 'learnpress' ),
		'GIP' => __( 'Gibraltar pound', 'learnpress' ),
		'GTQ' => __( 'Guatemalan quetzal', 'learnpress' ),
		'GBP' => __( 'British pound', 'learnpress' ),
		'GNF' => __( 'Guinean franc', 'learnpress' ),
		'GYD' => __( 'Guyanese dollar', 'learnpress' ),
		'HTG' => __( 'Haitian gourde', 'learnpress' ),
		'HNL' => __( 'Honduran lempira', 'learnpress' ),
		'HKD' => __( 'Hong Kong dollar', 'learnpress' ),
		'HUF' => __( 'Hungarian forint', 'learnpress' ),
		'ISK' => __( 'Icelandic króna', 'learnpress' ),
		'INR' => __( 'Indian rupee', 'learnpress' ),
		'IDR' => __( 'Indonesian rupiah', 'learnpress' ),
		'IRR' => __( 'Iranian rial', 'learnpress' ),
		'IQD' => __( 'Iraqi dinar', 'learnpress' ),
		'ILS' => __( 'Israeli new sheqel', 'learnpress' ),
		'JMD' => __( 'Jamaican dollar', 'learnpress' ),
		'JPY' => __( 'Japanese yen ', 'learnpress' ),
		'JOD' => __( 'Jordanian dinar', 'learnpress' ),
		'KZT' => __( 'Kazakhstani tenge', 'learnpress' ),
		'KES' => __( 'Kenyan shilling', 'learnpress' ),
		'KPW' => __( 'North Korean won', 'learnpress' ),
		'KWD' => __( 'Kuwaiti dinar', 'learnpress' ),
		'KGS' => __( 'Kyrgyzstani som', 'learnpress' ),
		'KRW' => __( 'South Korean won', 'learnpress' ),
		'LAK' => __( 'Lao kip', 'learnpress' ),
		'LVL' => __( 'Latvian lats', 'learnpress' ),
		'LBP' => __( 'Lebanese pound', 'learnpress' ),
		'LSL' => __( 'Lesotho loti', 'learnpress' ),
		'LRD' => __( 'Liberian dollar', 'learnpress' ),
		'LD'  => __( 'Libyan dinar', 'learnpress' ),
		'CHF' => __( 'Swiss franc', 'learnpress' ),
		'LTL' => __( 'Lithuanian litas', 'learnpress' ),
		'MOP' => __( 'Macanese pataca', 'learnpress' ),
		'MKD' => __( 'Macedonian denar', 'learnpress' ),
		'MGA' => __( 'Malagasy ariary', 'learnpress' ),
		'MWK' => __( 'Malawian kwacha', 'learnpress' ),
		'MYR' => __( 'Malaysian ringgit', 'learnpress' ),
		'MVR' => __( 'Maldivian rufiyaa', 'learnpress' ),
		'MRO' => __( 'Mauritanian ouguiya', 'learnpress' ),
		'MUR' => __( 'Mauritian rupee', 'learnpress' ),
		'MXN' => __( 'Mexican peso', 'learnpress' ),
		'MDL' => __( 'Moldovan leu', 'learnpress' ),
		'MNT' => __( 'Mongolian tugrik', 'learnpress' ),
		'MAD' => __( 'Moroccan dirham', 'learnpress' ),
		'MZN' => __( 'Mozambican metical', 'learnpress' ),
		'NAD' => __( 'Namibian dollar', 'learnpress' ),
		'NPR' => __( 'Nepalese rupee', 'learnpress' ),
		'NIO' => __( 'Nicaraguan córdoba', 'learnpress' ),
		'NGN' => __( 'Nigerian naira', 'learnpress' ),
		'NOK' => __( 'Norwegian krone', 'learnpress' ),
		'OMR' => __( 'Omani rial', 'learnpress' ),
		'PKR' => __( 'Pakistani rupee', 'learnpress' ),
		'PAB' => __( 'Panamanian balboa', 'learnpress' ),
		'PGK' => __( 'Papua New Guinea kina', 'learnpress' ),
		'PYG' => __( 'Paraguayan guarani', 'learnpress' ),
		'PEN' => __( 'Peruvian nuevo sol', 'learnpress' ),
		'PHP' => __( 'Philippine peso', 'learnpress' ),
		'PLN' => __( 'Polish zloty', 'learnpress' ),
		'QAR' => __( 'Qatari riyal', 'learnpress' ),
		'RON' => __( 'Romanian leu', 'learnpress' ),
		'RUB' => __( 'Russian ruble', 'learnpress' ),
		'RWF' => __( 'Rwandan franc', 'learnpress' ),
		'WST' => __( 'Samoan tālā', 'learnpress' ),
		'STD' => __( 'São Tomé and Príncipe dobra', 'learnpress' ),
		'SAR' => __( 'Saudi riyal', 'learnpress' ),
		'RSD' => __( 'Serbian dinar', 'learnpress' ),
		'SCR' => __( 'Seychellois rupee', 'learnpress' ),
		'SLL' => __( 'Sierra Leonean leone', 'learnpress' ),
		'SGD' => __( 'Singapore dollar', 'learnpress' ),
		'SBD' => __( 'Solomon Islands dollar', 'learnpress' ),
		'SOS' => __( 'Somali shilling', 'learnpress' ),
		'ZAR' => __( 'South African rand', 'learnpress' ),
		'LKR' => __( 'Sri Lankan rupee', 'learnpress' ),
		'SHP' => __( 'St. Helena pound', 'learnpress' ),
		'SDG' => __( 'Sudanese pound', 'learnpress' ),
		'SRD' => __( 'Surinamese dollar', 'learnpress' ),
		'SZL' => __( 'Swazi lilangeni', 'learnpress' ),
		'SEK' => __( 'Swedish krona', 'learnpress' ),
		'SYP' => __( 'Syrian pound', 'learnpress' ),
		'TWD' => __( 'New Taiwan dollar', 'learnpress' ),
		'TJS' => __( 'Tajikistani somoni', 'learnpress' ),
		'TZS' => __( 'Tanzanian shilling', 'learnpress' ),
		'THB' => __( 'Thai baht ', 'learnpress' ),
		'TOP' => __( 'Tongan pa’anga', 'learnpress' ),
		'TTD' => __( 'Trinidad and Tobago dollar', 'learnpress' ),
		'TND' => __( 'Tunisian dinar', 'learnpress' ),
		'TRY' => __( 'Turkish lira', 'learnpress' ),
		'TMT' => __( 'Turkmenistani manat', 'learnpress' ),
		'UGX' => __( 'Ugandan shilling', 'learnpress' ),
		'UAH' => __( 'Ukrainian hryvnia', 'learnpress' ),
		'AED' => __( 'United Arab Emirates dirham', 'learnpress' ),
		'UYU' => __( 'Uruguayan peso', 'learnpress' ),
		'UZS' => __( 'Uzbekistani som', 'learnpress' ),
		'VUV' => __( 'Vanuatu vatu', 'learnpress' ),
		'VEF' => __( 'Venezuelan bolivar', 'learnpress' ),
		'VND' => __( 'Vietnamese dong', 'learnpress' ),
		'YER' => __( 'Yemeni rial', 'learnpress' ),
		'ZMK' => __( 'Zambian kwacha', 'learnpress' ),
		'ZWL' => __( 'Zimbabwean dollar', 'learnpress' ),
		'JEP' => __( 'Jersey pound', 'learnpress' ),
		'LYD' => __( 'Libyan dinar', 'learnpress' ),
	);

	asort( $currencies );

	return apply_filters( 'learn-press/currencies', $currencies );
}

/**
 * Get current setting of currency.
 *
 * @return string
 */
function learn_press_get_currency() {
	$currency = apply_filters( 'learn_press_currency', LP_Settings::instance()->get( 'currency', 'USD' ) );

	return apply_filters( 'learn-press/currency', $currency );
}

/**
 * Return list of common symbols of the currencies on the world.
 *
 * @return array
 */
function learn_press_currency_symbols() {
	$symbols = array(
		'AED' => '&#x62f;.&#x625;',
		'AFN' => '&#x60b;',
		'ALL' => 'L',
		'AMD' => 'AMD',
		'ANG' => '&fnof;',
		'AOA' => 'Kz',
		'ARS' => '&#36;',
		'AUD' => '&#36;',
		'AWG' => 'Afl.',
		'AZN' => 'AZN',
		'BAM' => 'KM',
		'BBD' => '&#36;',
		'BDT' => '&#2547;&nbsp;',
		'BGN' => '&#1083;&#1074;.',
		'BHD' => '.&#x62f;.&#x628;',
		'BIF' => 'Fr',
		'BMD' => '&#36;',
		'BND' => '&#36;',
		'BOB' => 'Bs.',
		'BRL' => '&#82;&#36;',
		'BSD' => '&#36;',
		'BTC' => '&#3647;',
		'BTN' => 'Nu.',
		'BWP' => 'P',
		'BYR' => 'Br',
		'BYN' => 'Br',
		'BZD' => '&#36;',
		'CAD' => '&#36;',
		'CDF' => 'Fr',
		'CHF' => '&#67;&#72;&#70;',
		'CLP' => '&#36;',
		'CNY' => '&yen;',
		'COP' => '&#36;',
		'CRC' => '&#x20a1;',
		'CUC' => '&#36;',
		'CUP' => '&#36;',
		'CVE' => '&#36;',
		'CZK' => '&#75;&#269;',
		'DJF' => 'Fr',
		'DKK' => 'DKK',
		'DOP' => 'RD&#36;',
		'DZD' => '&#x62f;.&#x62c;',
		'EGP' => 'EGP',
		'ERN' => 'Nfk',
		'ETB' => 'Br',
		'EUR' => '&euro;',
		'FJD' => '&#36;',
		'FKP' => '&pound;',
		'GBP' => '&pound;',
		'GEL' => '&#x20be;',
		'GGP' => '&pound;',
		'GHS' => '&#x20b5;',
		'GIP' => '&pound;',
		'GMD' => 'D',
		'GNF' => 'Fr',
		'GTQ' => 'Q',
		'GYD' => '&#36;',
		'HKD' => '&#36;',
		'HNL' => 'L',
		'HRK' => 'kn',
		'HTG' => 'G',
		'HUF' => '&#70;&#116;',
		'IDR' => 'Rp',
		'ILS' => '&#8362;',
		'IMP' => '&pound;',
		'INR' => '&#8377;',
		'IQD' => '&#x639;.&#x62f;',
		'IRR' => '&#xfdfc;',
		'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
		'ISK' => 'kr.',
		'JEP' => '&pound;',
		'JMD' => '&#36;',
		'JOD' => '&#x62f;.&#x627;',
		'JPY' => '&yen;',
		'KES' => 'KSh',
		'KGS' => '&#x441;&#x43e;&#x43c;',
		'KHR' => '&#x17db;',
		'KMF' => 'Fr',
		'KPW' => '&#x20a9;',
		'KRW' => '&#8361;',
		'KWD' => '&#x62f;.&#x643;',
		'KYD' => '&#36;',
		'KZT' => '&#8376;',
		'LAK' => '&#8365;',
		'LBP' => '&#x644;.&#x644;',
		'LKR' => '&#xdbb;&#xdd4;',
		'LRD' => '&#36;',
		'LSL' => 'L',
		'LYD' => '&#x644;.&#x62f;',
		'MAD' => '&#x62f;.&#x645;.',
		'MDL' => 'MDL',
		'MGA' => 'Ar',
		'MKD' => '&#x434;&#x435;&#x43d;',
		'MMK' => 'Ks',
		'MNT' => '&#x20ae;',
		'MOP' => 'P',
		'MRU' => 'UM',
		'MUR' => '&#x20a8;',
		'MVR' => '.&#x783;',
		'MWK' => 'MK',
		'MXN' => '&#36;',
		'MYR' => '&#82;&#77;',
		'MZN' => 'MT',
		'NAD' => 'N&#36;',
		'NGN' => '&#8358;',
		'NIO' => 'C&#36;',
		'NOK' => '&#107;&#114;',
		'NPR' => '&#8360;',
		'NZD' => '&#36;',
		'OMR' => '&#x631;.&#x639;.',
		'PAB' => 'B/.',
		'PEN' => 'S/',
		'PGK' => 'K',
		'PHP' => '&#8369;',
		'PKR' => '&#8360;',
		'PLN' => '&#122;&#322;',
		'PRB' => '&#x440;.',
		'PYG' => '&#8370;',
		'QAR' => '&#x631;.&#x642;',
		'RMB' => '&yen;',
		'RON' => 'lei',
		'RSD' => '&#1088;&#1089;&#1076;',
		'RUB' => '&#8381;',
		'RWF' => 'Fr',
		'SAR' => '&#x631;.&#x633;',
		'SBD' => '&#36;',
		'SCR' => '&#x20a8;',
		'SDG' => '&#x62c;.&#x633;.',
		'SEK' => '&#107;&#114;',
		'SGD' => '&#36;',
		'SHP' => '&pound;',
		'SLL' => 'Le',
		'SOS' => 'Sh',
		'SRD' => '&#36;',
		'SSP' => '&pound;',
		'STN' => 'Db',
		'SYP' => '&#x644;.&#x633;',
		'SZL' => 'L',
		'THB' => '&#3647;',
		'TJS' => '&#x405;&#x41c;',
		'TMT' => 'm',
		'TND' => '&#x62f;.&#x62a;',
		'TOP' => 'T&#36;',
		'TRY' => '&#8378;',
		'TTD' => '&#36;',
		'TWD' => '&#78;&#84;&#36;',
		'TZS' => 'Sh',
		'UAH' => '&#8372;',
		'UGX' => 'UGX',
		'USD' => '&#36;',
		'UYU' => '&#36;',
		'UZS' => 'UZS',
		'VEF' => 'Bs F',
		'VES' => 'Bs.S',
		'VND' => '&#8363;',
		'VUV' => 'Vt',
		'WST' => 'T',
		'XAF' => 'CFA',
		'XCD' => '&#36;',
		'XOF' => 'CFA',
		'XPF' => 'Fr',
		'YER' => '&#xfdfc;',
		'ZAR' => '&#82;',
		'ZMW' => 'ZK',
	);

	return apply_filters( 'learn-press/currency-symbols', $symbols );
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
	$currency_symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '';

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

//add_filter( 'learn_press_course_lesson_permalink', 'learn_press_course_lesson_permalink_friendly', 10, 3 );
function learn_press_course_lesson_permalink_friendly( $permalink, $lesson_id, $course_id ) {
	if ( '' != get_option( 'permalink_structure' ) ) {
		if ( preg_match( '!\?lesson=([^\?\&]*)!', $permalink, $matches ) ) {
			$permalink = preg_replace(
				'!/?\?lesson=([^\?\&]*)!',
				'/' . basename( get_permalink( $matches[1] ) ),
				untrailingslashit( $permalink )
			);
		}
	}

	return $permalink;
}

/**
 * @deprecated 4.1.7.3
 */
function learn_press_course_question_permalink_friendly( $permalink, $lesson_id, $course_id ) {
	_deprecated_function( __FUNCTION__, '4.1.7.3' );
	if ( '' != get_option( 'permalink_structure' ) ) {
		if ( preg_match( '!\?lesson=([^\?\&]*)!', $permalink, $matches ) ) {
			$permalink = preg_replace(
				'!/?\?lesson=([^\?\&]*)!',
				'/' . basename( get_permalink( $matches[1] ) ),
				untrailingslashit( $permalink )
			);
		}
	}

	return $permalink;
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

/**
 * Modifies the statement $where to make the search works correct
 *
 * @param string
 *
 * @return string
 * @deprecated 4.1.7.2
 */
//function learn_press_posts_where_statement_search( $where ) {
//	// gets the global query var object
//	global $wp_query, $wpdb;
//
//	/**
//	 * Need to wrap this block into () in order to make it works correctly when filter by specific post type => maybe a bug :)
//	 * from => ( wp_2_posts.post_status = 'publish' OR wp_2_posts.post_status = 'private') OR wp_2_terms.name LIKE '%s%'
//	 * to => ( ( wp_2_posts.post_status = 'publish' OR wp_2_posts.post_status = 'private') OR wp_2_terms.name LIKE '%s%' )
//	 */
//	$a = preg_match( '!(' . $wpdb->posts . '.post_status)!', $where );
//	$b = preg_match( '!(OR\s+' . $wpdb->terms . '.name LIKE \'%' . $wp_query->get( 's' ) . '%\')!', $where );
//
//	if ( $a && $b ) {
//		// append ( to the start of the block
//		$where = preg_replace( '!(' . $wpdb->posts . '.post_status)!', '( $1', $where, 1 );
//
//		// append ) to the end of the block
//		$where = preg_replace(
//			'!(OR\s+' . $wpdb->terms . '.name LIKE \'%' . $wp_query->get( 's' ) . '%\')!',
//			'$1 )',
//			$where
//		);
//	}
//	remove_filter( 'posts_where', 'learn_press_posts_where_statement_search', 99 );
//
//	return $where;
//}

/**
 * Filter post type for search function
 * Only search lpr_course if see the param ref=course in request
 *
 * @param WP_Query $q
 * @deprecated 4.1.7.2
 */
/*function learn_press_filter_search( $q ) {
	if ( $q->is_main_query() && $q->is_search() && ( ! empty( $_REQUEST['ref'] ) && sanitize_text_field( $_REQUEST['ref'] ) == 'course' ) ) {
		$q->set( 'post_type', 'lp_course' );

		add_filter( 'posts_where', 'learn_press_posts_where_statement_search', 99 );
		remove_filter( 'pre_get_posts', 'learn_press_filter_search', 99 );
	}
}*/
//add_filter( 'pre_get_posts', 'learn_press_filter_search', 99 );

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
	function learn_press_is_course() {
		/**
		 * @var WP_Query $wp_query
		 */
		global $wp_query;

		if ( $wp_query->get_queried_object() ) {
			return is_singular( array( LP_COURSE_CPT ) );
		} else {
			return false;
		}
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
		setcookie( $name, '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
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
 * Add all endpoints from settings to the pages.
 * @deprecated 4.2.3
 * @use LP_Query::add_rewrite_endpoints instead
 */
//function learn_press_add_endpoints() {
//	// Must LP_Profile::instance call on init, because it will add action hook to save data on Profile page
//	// If rewrite save data on Profile page, can remove it.
//	//LP_Profile::instance( get_current_user_id() );
//
//	$settings = LP_Settings::instance();
//
//	$endpoints = $settings->get_checkout_endpoints();
//	if ( $endpoints ) {
//		foreach ( $endpoints as $endpoint => $value ) {
//			LearnPress::instance()->query_vars[ $endpoint ] = $value;
//			add_rewrite_endpoint( $value, EP_PAGES );
//		}
//	}
//
//	$endpoints = $settings->get_profile_endpoints();
//	if ( $endpoints ) {
//		foreach ( $endpoints as $endpoint => $value ) {
//			LearnPress::instance()->query_vars[ $endpoint ] = $value;
//			add_rewrite_endpoint( $value, EP_PAGES );
//		}
//	}
//
//	$endpoints = $settings->get( 'quiz_endpoints' );
//	if ( $endpoints ) {
//		foreach ( $endpoints as $endpoint => $value ) {
//			$endpoint                                       = preg_replace( '!_!', '-', $endpoint );
//			LearnPress::instance()->query_vars[ $endpoint ] = $value;
//			add_rewrite_endpoint(
//				$value, /*EP_ROOT | */
//				EP_PAGES
//			);
//		}
//	}
//}

//add_action( 'init', 'learn_press_add_endpoints' );

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
 * @param string $handle
 * @param bool   $hash
 *
 * @return string
 * @deprecated 4.1.7.2
 */
/*function learn_press_get_log_file_path( $handle, $hash = false ) {
	if ( $hash ) {
		$hash = '-' . sanitize_file_name( wp_hash( $handle ) );
	}

	return trailingslashit( LP_LOG_PATH ) . $handle . $hash . '.log';
}*/

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

/*function learn_press_front_scripts() {
	if ( is_admin() ) {
		return;
	}
	$js = array(
		'ajax'        => admin_url( 'admin-ajax.php' ),
		'plugin_url'  => LearnPress::instance()->plugin_url(),
		'siteurl'     => home_url(),
		'current_url' => learn_press_get_current_url(),
		'localize'    => array(
			'button_ok'     => __( 'OK', 'learnpress' ),
			'button_cancel' => __( 'Cancel', 'learnpress' ),
			'button_yes'    => __( 'Yes', 'learnpress' ),
			'button_no'     => __( 'No', 'learnpress' ),
		),
	);
	foreach ( $js as $k => $v ) {
		LP_Assets::add_param( $k, $v, array( 'learn-press-single-course', 'learn-press-global' ), 'LP_Settings' );
	}
}

add_action( 'wp_print_scripts', 'learn_press_front_scripts' );*/

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

/**
 * Replace the spacing with the + (plus) char.
 *
 * @param string $string
 *
 * @return string
 * @deprecated 4.1.7.2
 */
/*function _learn_press_urlencode( $string ) {
	return preg_replace( '/\s/', '+', $string );
}*/

/**
 * Point the archive post type link to course page if current
 * post type is course and the page for displaying course is
 * setup.
 *
 * @param string $link
 * @param string $post_type
 *
 * @return string
 * @deprecated 4.1.7.2
 */
/*function learn_press_post_type_archive_link( $link, $post_type ) {
	if ( $post_type == LP_COURSE_CPT && learn_press_get_page_id( 'courses' ) ) {
		$link = learn_press_get_page_link( 'courses' );
	}

	return $link;
}*/
//add_filter( 'post_type_archive_link', 'learn_press_post_type_archive_link', 10, 2 );

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

/**
 * Get current time to user for calculate remaining time of quiz.
 *
 * @return int
 * @deprecated 4.1.7.2
 */
/*function learn_press_get_current_time() {
	$current_time = apply_filters( 'learn_press_get_current_time', 0 );

	if ( $current_time > 0 ) {
		return $current_time;
	}

	$a = current_time( 'timestamp' );
	$b = time();
	$c = current_time( 'mysql' );
	$d = strtotime( $c );

	if ( $d == $a ) {
		return $a;
	} else {
		return $b;
	}
}*/

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
 * Get timezone offset from wp settings.
 *
 * @return float|int
 * @since 3.0.0
 * @deprecated 4.1.7.2
 */
/*function learn_press_timezone_offset() {
	if ( $tz = get_option( 'timezone_string' ) ) {
		$timezone = new DateTimeZone( $tz );

		return $timezone->getOffset( new DateTime( 'now' ) );
	} else {
		return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
	}
}*/

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
 * Get default static pages of LP.
 *
 * @param bool $name - Optional. TRUE will return name only.
 *
 * @return array
 *
 * @since 3.0.0
 */
function learn_press_static_pages( $name = false ) {
	$pages = apply_filters(
		'learn-press/static-pages',
		array(
			'checkout'         => _x( 'Checkout', 'static-page-name', 'learnpress' ),
			'courses'          => _x( 'Courses', 'static-page-name', 'learnpress' ),
			'profile'          => _x( 'Profile', 'static-page-name', 'learnpress' ),
			'become_a_teacher' => _x( 'Become a Teacher', 'static-page-name', 'learnpress' ),
		)
	);

	if ( $name ) {
		return array_keys( $pages );
	}

	return $pages;
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
 * Add post type of a post into cache
 *
 * @param int|array $id
 * @param string    $type
 *
 * @since 3.1.0
 * @deprecated 4.1.7.2
 */
/*function learn_press_cache_add_post_type( $id, $type = '' ) {
	if ( false === ( $post_types = LP_Object_Cache::get( 'post-types', 'learn-press' ) ) ) {
		$post_types = array();
	}

	if ( func_num_args() == 1 && is_array( $id ) ) {
		$post_types = $post_types + $id;
	} else {
		$post_types[ $id ] = $type;
	}

	LP_Object_Cache::set( 'post-types', $post_types, 'learn-press' );
}*/

/**
 * @deprecated 4.1.7.2
 */
/*function learn_press_has_option( $name ) {
	global $wpdb;

	$query = $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s", $name );

	return $wpdb->get_var( $query ) > 0;
}*/

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
 * @deprecated 4.1.7.2
 */
/*function learn_press_show_log() {
	if ( trim( LP_Request::get( 'show_log' ) ) === md5( AUTH_KEY ) ) {
		call_user_func_array( 'learn_press_debug', func_get_args() );
	}
}*/

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
 * Get url for setup cron job on server.
 *
 * @return string
 * @since 3.3.0
 * @deprecated 4.1.7.2
 */
/*function learn_press_get_cron_url() {
	$nonce = get_option( 'learnpress_cron_url_nonce' );

	if ( ! $nonce ) {
		$nonce = md5( microtime( true ) );
		update_option( 'learnpress_cron_url_nonce', $nonce );
	}
	$url = add_query_arg(
		array(
			'lp-ajax' => 'cron',
			'sid'     => $nonce,
		),
		get_home_url()
	);

	return $url;
}*/

/**
 * Get courses expired.
 *
 * @return array
 * @since 3.3.0
 * @deprecated 4.1.7.2
 */
/*function learn_press_get_expired_courses() {
	global $wpdb;

	$query = $wpdb->prepare(
		"
			SELECT X.*
			FROM(
			SELECT ui.*
				FROM {$wpdb->learnpress_user_items} ui
				LEFT JOIN {$wpdb->learnpress_user_items} uix
					ON ui.item_id = uix.item_id
						AND ui.user_id = uix.user_id
						AND ui.user_item_id < uix.user_item_id
				WHERE uix.user_item_id IS NULL
			) X
			INNER JOIN {$wpdb->users} u ON u.ID = X.user_id
			INNER JOIN {$wpdb->posts} p ON p.ID = X.item_id
			WHERE X.item_type = %s
				AND X.status = %s
				#AND expiration_time_gmt <= UTC_TIMESTAMP()
				AND expiration_time <= UTC_TIMESTAMP()
			LIMIT 0, 10
		",
		LP_COURSE_CPT,
		'enrolled'
	);

}*/

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
 * Helper function to output html for rendering a 'circle progress bar'
 *
 * @param int    $percent
 * @param int    $width
 * @param int    $border
 * @param string $color
 *
 * @since 3.3.0
 * @deprecated 4.1.7.2
 */
/*function learn_press_circle_progress_html( $percent = 0, $width = 32, $border = 4, $color = '' ) {
	$radius        = $width / 2;
	$r             = ( $width - $border ) / 2;
	$circumference = $r * 2 * pi();
	$offset        = $circumference - $percent / 100 * $circumference;

	printf(
		'<svg class="circle-progress-bar" width="%d" height="%d">
		<circle class="circle-progress-bar__circle"
				stroke="%s"
				stroke-width="%d"
				style="stroke-dasharray:%s %s; stroke-dashoffset:%s;"
				fill="transparent"
				r="%d" cx="%d" cy="%d"></circle>
	</svg>',
		$width,
		$width,
		$color,
		$border,
		$circumference,
		$circumference,
		$offset,
		$r,
		$radius,
		$radius
	);
}*/

/**
 * @deprecated 4.1.7.2
 */
/*function learn_press_is_page( $page_name ) {
	$page_id = learn_press_get_page_id( $page_name );

	return $page_id && is_page( $page_id );
}*/

/**
 * Get end-date from start date with a duration.
 *
 * @param string|int $duration
 * @param string|int $start
 *
 * @return false|string
 * @since 3.x.x
 * @deprecated 4.1.7.2
 */
/*function learn_press_date_end_from( $duration, $start = '' ) {
	$format = 'Y-m-d H:i:s';

	if ( ! $start ) {
		$start = time();
	} elseif ( ! is_numeric( $start ) ) {
		$start = strtotime( $start );
	}

	// is LP duration format, e.g: 10 weeks
	if ( preg_match( '/^[0-9]+ [a-z]+$/', $duration ) ) {
		$duration = ( new LP_Duration( $duration ) )->get();
	} elseif ( ! is_numeric( $duration ) ) {
		// 10 days 5 hours ...
		$duration = strtotime( $duration, 0 );
	}

	return date( $format, $start + $duration );
}*/

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
	$course_tip     = '<span class="learn-press-tip">%s</span>';
	$final_quiz_btn = '<a href="#" class="lp-metabox-get-final-quiz" data-postid="' . $postid . '" data-loading="' . esc_attr__(
		'Loading...',
		'learnpress'
	) . '">' . esc_html__( 'Get A Passing Grade', 'learnpress' ) . '</a>';

	$course_desc = array(
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
			'evaluate_lesson'     => __(
				'Evaluate via lessons',
				'learnpress'
			) . learn_press_quick_tip( $course_desc['evaluate_lesson'], false ),
			'evaluate_final_quiz' => __( 'Evaluate via results of the final quiz', 'learnpress' ) . sprintf(
				$course_tip,
				$course_desc['evaluate_final_quiz']
			) . $final_quiz_btn . $final_quizz_passing,
			'evaluate_quiz'       => __( 'Evaluate via passed quizzes', 'learnpress' ) . sprintf(
				$course_tip,
				$course_desc['evaluate_quiz']
			),
			'evaluate_questions'  => __( 'Evaluate via questions', 'learnpress' ) . sprintf(
				$course_tip,
				$course_desc['evaluate_questions']
			),
			'evaluate_mark'       => __( 'Evaluate via mark', 'learnpress' ) . sprintf(
				$course_tip,
				$course_desc['evaluate_mark']
			),
		)
	);

	return apply_filters(
		'learn-press/course-evaluation-methods',
		$return === 'keys' ? array_keys( $methods ) : $methods,
		$return
	);
}

/**
 * Convert time from GMT to local.
 *
 * @param string|int|LP_Datetime $gmt_time
 * @param string                 $format
 *
 * @return false|int|string
 * @since 4.0.0
 * @deprecated 4.1.7.2
 */
/*function learn_press_time_from_gmt( $gmt_time, $format = 'Y-m-d H:i:s' ) {
	if ( is_string( $gmt_time ) ) {
		$gmt_time = strtotime( $gmt_time );
	} elseif ( $gmt_time instanceof LP_Datetime ) {
		$gmt_time = strtotime( $gmt_time . '' );
	}

	$current_time = $gmt_time + get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;

	if ( $format ) {
		return date( $format, $current_time );
	}

	return $current_time;
}*/

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
 * Get max retrying course allowed.
 *
 * @param int $course_id
 *
 * @return int
 * @since 4.0.0
 * @deprecated 4.1.7.2
 */
/*function learn_press_get_course_max_retrying( $course_id ) {
	return apply_filters( 'learn-press/max-retry-course-allowed', 1, $course_id );
}*/

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


/**
 * Disable auto update
 *
 * @param $update
 * @param $item
 *
 * @return false
 * @author hungkv
 */
/*function learnpress_disable_auto_update( $update, $item ) {
	$plugins = array( // Plugins to  auto-update
		'learnpress',
	);
	// Auto-update specified plugins
	if ( in_array( $item->slug, $plugins ) ) {
		return false;
	}
}
add_filter( 'auto_update_plugin', 'learnpress_disable_auto_update', 10, 2 );*/

add_action(
	'in_plugin_update_message-learnpress/learnpress.php',
	function ( $plugin_data ) {
		if ( version_compare( $plugin_data['new_version'], LEARNPRESS_VERSION, '<=' ) ) {
			return;
		}
		?>
		<hr/>
		<h3>
			<?php echo esc_html__( 'Heads up! Please backup before upgrading!', 'learnpress' ); ?>
		</h3>
		<div>
			<?php echo esc_html__( 'The latest update includes some substantial changes across different areas of the plugin. We highly recommend you backup your site before upgrading, and make sure you first update in a staging environment', 'learnpress' ); ?>
		</div>
		<?php
	}
);

// If profile content don't have shortcode profile.
/**
 * @deprecated 4.2.2.4
 */
function lp_add_shortcode_profile() {
	global $post;

	if ( LP_Page_Controller::is_page_profile() && is_object( $post ) ) {
		if ( ! has_shortcode( $post->post_content, 'learn_press_profile' ) ) {
			$post->post_content .= '<!-- wp:shortcode -->[learn_press_profile]<!-- /wp:shortcode -->';
			wp_update_post( $post );
		}
	}
}

//add_action( 'template_redirect', 'lp_add_shortcode_profile' );

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
	function( $theme_template_id ) {
		$elementor_template_type = get_post_meta( $theme_template_id, '_elementor_template_type', true );

		if ( in_array( $elementor_template_type, array( 'archive' ) ) ) {
			if ( LP_PAGE_COURSES === LP_Page_Controller::page_current() && class_exists( 'ElementorPro\Modules\ThemeBuilder\Conditions\Archive' ) ) {
				return false;
			}
		}

		return $theme_template_id;
	}
);
