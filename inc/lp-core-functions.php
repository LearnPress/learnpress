<?php
/**
 * LearnPress Core Functions
 * Define common functions for both front-end and back-end
 *
 * @author   ThimPress
 * @package  LearnPress/Functions
 * @version  1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get the LearnPress plugin url
 *
 * @param string $sub_dir
 *
 * @return string
 */
function learn_press_plugin_url( $sub_dir = '' ) {
	return LP()->plugin_url( $sub_dir );
}

/**
 * Get the LearnPress plugin path.
 *
 * @param string $sub_dir
 *
 * @return string
 */
function learn_press_plugin_path( $sub_dir = '' ) {
	return LP()->plugin_path( $sub_dir );
}

/**
 * Includes file base on LearnPress path
 *
 * @param        $file
 * @param string $folder
 * @param bool   $include_once
 *
 * @return bool
 */
function learn_press_include( $file, $folder = 'inc', $include_once = true ) {
	if ( file_exists( $include = learn_press_plugin_path( "{$folder}/{$file}" ) ) ) {
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
	//Just get the headers if we can or else use the SERVER global
	if ( function_exists( 'apache_request_headers' ) ) {
		$headers = apache_request_headers();
	} else {
		$headers = $_SERVER;
	}
	//Get the forwarded IP if it exists
	if ( array_key_exists( 'X-Forwarded-For', $headers ) &&
		(
			filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ||
			filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) )
	) {
		$the_ip = $headers['X-Forwarded-For'];
	} elseif (
		array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) &&
		(
			filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ||
			filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 )
		)
	) {
		$the_ip = $headers['HTTP_X_FORWARDED_FOR'];
	} else {
		$the_ip = $_SERVER['REMOTE_ADDR'];
	}

	return esc_sql( $the_ip );
}

/**
 * Generate an unique string
 *
 * @param string $prefix
 *
 * @return mixed|void
 */
function learn_press_uniqid( $prefix = '' ) {
	$hash = str_replace( '.', '', microtime( true ) . uniqid() );
	return apply_filters( 'learn_press_generate_unique_hash', $prefix . $hash, $prefix );
}

/**
 * Check to see if an endpoint is showing in current URL
 *
 * @param bool $endpoint
 *
 * @return bool
 */
function learn_press_is_endpoint_url( $endpoint = false ) {
	global $wp;

	$endpoints = array();

	if ( $endpoint !== false ) {
		if ( !isset( $endpoints[$endpoint] ) ) {
			return false;
		} else {
			$endpoint_var = $endpoints[$endpoint];
		}

		return isset( $wp->query_vars[$endpoint_var] );
	} else {
		foreach ( $endpoints as $key => $value ) {
			if ( isset( $wp->query_vars[$key] ) ) {
				return true;
			}
		}

		return false;
	}
}

/**
 * Get current URL user is viewing
 *
 * @return string
 */
function learn_press_get_current_url() {
	static $current_url;
	if ( !$current_url ) {
		$url = add_query_arg( '', '' );
		if ( !preg_match( '!^https?!', $url ) ) {
			$segs1 = explode( '/', get_site_url() );
			$segs2 = explode( '/', $url );
			if ( $removed = array_intersect( $segs1, $segs2 ) ) {
				$segs2       = array_diff( $segs2, $removed );
				$current_url = get_site_url() . '/' . join( '/', $segs2 );
			}
		}
	}
	return learn_press_sanitize_url( $current_url );
}

/**
 * Compares an url with current URL user is viewing
 *
 * @param $url
 *
 * @return bool
 */
function learn_press_is_current_url( $url ) {
	return strcmp( learn_press_get_current_url(), learn_press_sanitize_url( $url ) ) == 0;
}

/**
 * Remove unneeded characters in an URL
 *
 * @param      $url
 * @param bool $trailingslashit
 *
 * @return string
 */
function learn_press_sanitize_url( $url, $trailingslashit = true ) {
	if ( $url ) {
		preg_match( '!(https?://)?(.*)!', $url, $matches );
		$url_without_http = $matches[2];
		$url_without_http = preg_replace( '![/]+!', '/', $url_without_http );
		$url              = $matches[1] . $url_without_http;
		return $trailingslashit ? trailingslashit( $url ) : untrailingslashit( $url );
	}
	return $url;
}

/**
 * Get all types of question supported
 *
 * @return mixed
 */
function learn_press_question_types() {
	return LP_Question_Factory::get_types();
}

/**
 * Get human name of question's type by slug
 *
 * @param $slug
 *
 * @return mixed|void
 */
function learn_press_question_name_from_slug( $slug ) {
	$types = learn_press_question_types();
	$name  = !empty( $types[$slug] ) ? $types[$slug] : '';
	return apply_filters( 'learn_press_question_name_from_slug', $name, $slug );
}

/**
 * Get the post types which supported to insert into course's section
 *
 * @return mixed|void
 */
function learn_press_section_item_types() {
	$types = array(
		'lp_lesson' => __( 'Lesson', 'learnpress' ),
		'lp_quiz'   => __( 'Quiz', 'learnpress' )
	);
	return apply_filters( 'learn_press_section_item_types', $types );
}

/**
 * Enqueue js code to print out
 *
 * @param string $code
 * @param bool   $script_tag - wrap code between <script> tag
 */
function learn_press_enqueue_script( $code, $script_tag = false ) {
	global $learn_press_queued_js, $learn_press_queued_js_tag;

	if ( $script_tag ) {
		if ( empty( $learn_press_queued_js_tag ) ) {
			$learn_press_queued_js_tag = '';
		}
		$learn_press_queued_js_tag .= "\n" . $code . "\n";
	} else {
		if ( empty( $learn_press_queued_js ) ) {
			$learn_press_queued_js = '';
		}

		$learn_press_queued_js .= "\n" . $code . "\n";
	}
}

function learn_press_get_course_terms( $course_id, $taxonomy, $args = array() ) {
	if ( !taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	// Support ordering by parent
	if ( !empty( $args['orderby'] ) && in_array( $args['orderby'], array( 'name_num', 'parent' ) ) ) {
		$fields  = isset( $args['fields'] ) ? $args['fields'] : 'all';
		$orderby = $args['orderby'];

		// Unset for wp_get_post_terms
		unset( $args['orderby'] );
		unset( $args['fields'] );

		$terms = wp_get_post_terms( $course_id, $taxonomy, $args );

		switch ( $orderby ) {
			case 'name_num' :
				usort( $terms, '_learn_press_get_course_terms_name_num_usort_callback' );
				break;
			case 'parent' :
				usort( $terms, '_learn_press_get_course_terms_parent_usort_callback' );
				break;
		}

		switch ( $fields ) {
			case 'names' :
				$terms = wp_list_pluck( $terms, 'name' );
				break;
			case 'ids' :
				$terms = wp_list_pluck( $terms, 'term_id' );
				break;
			case 'slugs' :
				$terms = wp_list_pluck( $terms, 'slug' );
				break;
		}
	} elseif ( !empty( $args['orderby'] ) && $args['orderby'] === 'menu_order' ) {
		// wp_get_post_terms doesn't let us use custom sort order
		$args['include'] = wp_get_post_terms( $course_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( empty( $args['include'] ) ) {
			$terms = array();
		} else {
			// This isn't needed for get_terms
			unset( $args['orderby'] );

			// Set args for get_terms
			$args['menu_order'] = isset( $args['order'] ) ? $args['order'] : 'ASC';
			$args['hide_empty'] = isset( $args['hide_empty'] ) ? $args['hide_empty'] : 0;
			$args['fields']     = isset( $args['fields'] ) ? $args['fields'] : 'names';

			// Ensure slugs is valid for get_terms - slugs isn't supported
			$args['fields'] = $args['fields'] === 'slugs' ? 'id=>slug' : $args['fields'];
			$terms          = get_terms( $taxonomy, $args );
		}
	} else {
		$terms = wp_get_post_terms( $course_id, $taxonomy, $args );
	}

	return apply_filters( 'learn_press_get_course_terms', $terms, $course_id, $taxonomy, $args );
}

function _learn_press_get_course_terms_name_num_usort_callback( $a, $b ) {
	if ( $a->name + 0 === $b->name + 0 ) {
		return 0;
	}
	return ( $a->name + 0 < $b->name + 0 ) ? - 1 : 1;
}

function _learn_press_get_course_terms_parent_usort_callback( $a, $b ) {
	if ( $a->parent === $b->parent ) {
		return 0;
	}
	return ( $a->parent < $b->parent ) ? 1 : - 1;
}

function learn_press_get_post_by_name( $name, $type, $single = true ) {
	$post_names = LP_Cache::get_post_names( false, array() );
	$post       = false;
	if ( !empty( $post_names[$type][$name] ) ) {
		$post = get_post( $post_names[$type][$name] );
	}
	if ( !$post ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->posts}
			WHERE 1 AND post_name = %s
		", $name );

		$query .= " AND post_type IN ('" . $type . "' )";
		if ( empty( $post_names[$type] ) ) {
			$post_names[$type] = array();
		}
		if ( $post = $wpdb->get_row( $query ) ) {
			//wp_cache_set( $post->ID, $post, 'posts' );
		}

		$post_names[$type][$name] = $post ? $post->ID : 0;
		LP_Cache::set_post_names( $post_names );
	}
	return $post ? get_post( $post_names[$type][$name] ) : false;
}

function learn_press_get_current_course() {
	return LP()->global['course'];
}

function learn_press_get_course_item_object( $post_type ) {
	switch ( $post_type ) {
		case 'lp_quiz':
			$class = 'LP_Quiz';
			break;
		case 'lp_lesson':
			$class = 'LP_Lesson';
			break;
		case 'lp_question':
			$class = 'LP_Question';
	}
}

//get_post_meta()

/**
 * Print out js code in the queue
 */
function learn_press_print_script() {
	global $learn_press_queued_js, $learn_press_queued_js_tag;
	if ( !empty( $learn_press_queued_js ) ) {
		?>
		<!-- LearnPress JavaScript -->
		<script type="text/javascript">jQuery(function ($) {
				<?php
				// Sanitize
				$learn_press_queued_js = wp_check_invalid_utf8( $learn_press_queued_js );
				$learn_press_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $learn_press_queued_js );
				$learn_press_queued_js = str_replace( "\r", '', $learn_press_queued_js );

				echo $learn_press_queued_js;
				?>
			});
		</script>
		<?php
		unset( $learn_press_queued_js );
	}

	if ( !empty( $learn_press_queued_js_tag ) ) {
		echo $learn_press_queued_js_tag;
	}
}

add_action( 'wp_footer', 'learn_press_print_script' );
add_action( 'admin_footer', 'learn_press_print_script' );

/**
 * @param string $str
 * @param int    $lines
 */
function learn_press_email_new_line( $lines = 1, $str = "\r\n" ) {
	echo str_repeat( $str, $lines );
}

if ( !function_exists( 'is_ajax' ) ) {

	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 *
	 * @access public
	 * @return bool
	 */
	function is_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

/**
 * Get page id from admin settings page
 *
 * @param string $name
 *
 * @return int
 */
function learn_press_get_page_id( $name ) {
	return apply_filters( 'learn_press_get_page_id', LP_Settings::instance()->get( "{$name}_page_id", false ), $name );
}

/**
 * display the seconds in time format h:i:s
 *
 * @param        $seconds
 * @param string $separator
 *
 * @return string
 */
function learn_press_seconds_to_time( $seconds, $separator = ':' ) {
	return sprintf( "%02d%s%02d%s%02d", floor( $seconds / 3600 ), $separator, ( $seconds / 60 ) % 60, $separator, $seconds % 60 );
}

/**
 * Create an empty post object
 *
 * @version 1.0
 *
 * @param mixed
 *
 * @return mixed
 */
function learn_press_post_object( $defaults = false ) {
	static $post_object = false;
	if ( !$post_object ) {
		global $wpdb;
		$post_object = new stdClass();
		foreach ( $wpdb->get_col( "DESC " . $wpdb->posts, 0 ) as $column_name ) {
			$post_object->{$column_name} = null;
		}
	}
	settype( $defaults, 'array' );
	foreach ( get_object_vars( $post_object ) as $k => $v ) {
		if ( array_key_exists( $k, $defaults ) ) {
			$post_object->{$k} = $defaults[$k];
		} else {
			$post_object->{$k} = '';
		}
	}
	return $post_object;
}

/***********************************************/
/***** =================================== *****/
/***** THE FUNCTIONS ABOVE FOR VERSION 1.0 *****/
/***** =================================== *****/
/***********************************************/

/* nav */
if ( !function_exists( 'learn_press_course_paging_nav' ) ) :

	/**
	 * Display navigation to next/previous set of posts when applicable.
	 *
	 * @param array
	 */
	function learn_press_course_paging_nav( $args = array() ) {
		learn_press_paging_nav(
			array(
				'num_pages'     => $GLOBALS['wp_query']->max_num_pages,
				'wrapper_class' => 'navigation pagination'
			)
		);
	}

endif;

/* nav */
if ( !function_exists( 'learn_press_paging_nav' ) ) :

	/**
	 * Display navigation to next/previous set of posts when applicable.
	 *
	 * @param array
	 */
	function learn_press_paging_nav( $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'num_pages'     => 0,
				'paged'         => get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1,
				'wrapper_class' => 'learn-press-pagination',
				'base'          => false
			)
		);
		if ( $args['num_pages'] < 2 ) {
			return;
		}
		$paged        = $args['paged'];
		$pagenum_link = html_entity_decode( $args['base'] === false ? get_pagenum_link() : $args['base'] );

		$query_args = array();
		$url_parts  = explode( '?', $pagenum_link );

		if ( isset( $url_parts[1] ) ) {
			wp_parse_str( $url_parts[1], $query_args );
		}

		$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
		$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

		$format = $GLOBALS['wp_rewrite']->using_index_permalinks() && !strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format .= $GLOBALS['wp_rewrite']->using_permalinks() ? user_trailingslashit( 'page/%#%', 'paged' ) : '?paged=%#%';

		// Set up paginated links.
		$links = paginate_links( array(
			'base'      => $pagenum_link,
			'format'    => $format,
			'total'     => $args['num_pages'],
			'current'   => max( 1, $paged ),
			'mid_size'  => 1,
			'add_args'  => array_map( 'urlencode', $query_args ),
			'prev_text' => __( '<', 'learnpress' ),
			'next_text' => __( '>', 'learnpress' ),
			'type'      => 'list'
		) );

		if ( $links ) :
			?>
			<div class="<?php echo $args['wrapper_class']; ?>">
				<?php echo $links; ?>
			</div>
			<!-- .pagination -->
			<?php
		endif;
	}

endif;

function learn_press_get_num_pages( $total, $limit = 10 ) {
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

// processing registration


/*
 * Get LeanrPress current profile permalink
 */

function learn_press_get_current_profile_link() {
	if ( !learn_press_has_profile_method() ) {
		return;
	}
	global $wp_rewrite;
	if ( empty( $wp_rewrite->permalink_structure ) ) {
		return;
	}
	$current_user = wp_get_current_user();
	$link         = home_url( "/profile/$current_user->user_login" );

	return $link;
}

/*
 * Get LeanrPress profile permalink
 */
function learn_press_get_profile_link( $link, $user_id, $course_id ) {
	///_deprecated_function( __FUNCTION__, '1.0', 'learn_press_course_profile_link');
	return learn_press_course_profile_link( $course_id );
}

function learn_press_get_course_duration_support() {
	return apply_filters(
		'learn_press_course_duration_support',
		array(
			'minute' => __( 'Minute(s)' ),
			'hour'   => __( 'Hour(s)' ),
			'day'    => __( 'Day(s)' ),
			'week'   => __( 'Week(s)' )
		)
	);
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
			'week'   => 3600 * 24 * 7
		);
		$a1 = $a1 * $b[$a2];
	}
	return $a1;
}

/**
 * Return profile link of an user from a course
 *
 * @param int $course_id
 *
 * @return mixed|void
 */
function learn_press_course_profile_link( $course_id = 0 ) {
	$link = null;
	if ( !$course_id ) {
		$course_id = get_the_ID();
	}
	$course_author = false;
	if ( get_post( $course_id ) == 'lp_course' && $course_author = get_post_field( 'post_author', $course_id ) ) {
		$link = learn_press_user_profile_link( $course_author );
	}
	return apply_filters( 'learn_press_course_profile_link', $link, $course_id, $course_author );
}

/*
 * Add searching post by taxonomies
 */
add_action( 'pre_get_posts', 'learn_press_query_taxonomy' );
function learn_press_query_taxonomy( $q ) {
	// We only want to affect the main query
	if ( !$q->is_main_query() ) {
		return;
	}
	if ( is_search() ) {
		add_filter( 'posts_where', 'learn_press_add_tax_search' );
		add_filter( 'posts_join', 'learn_press_join_term' );
		add_filter( 'posts_groupby', 'learn_press_tax_groupby' );
		add_filter( 'wp', 'remove_query_tax' );
	}
}

function learn_press_join_term( $join ) {
	global $wp_query, $wpdb;

	if ( !empty( $wp_query->query_vars['s'] ) && !is_admin() ) {
		$join .= "LEFT JOIN $wpdb->term_relationships ON $wpdb->posts.ID = $wpdb->term_relationships.object_id ";
		$join .= "LEFT JOIN $wpdb->term_taxonomy ON $wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id ";
		$join .= "LEFT JOIN $wpdb->terms ON $wpdb->term_taxonomy.term_id = $wpdb->terms.term_id ";
	}

	return $join;
}

function learn_press_add_tax_search( $where ) {
	global $wp_query, $wpdb;

	if ( !empty( $wp_query->query_vars['s'] ) && !is_admin() ) {
		$escaped_s = esc_sql( $wp_query->query_vars['s'] );
		$where .= "OR $wpdb->terms.name LIKE '%{$escaped_s}%'";
	}

	return $where;
}

function learn_press_tax_groupby( $groupby ) {
	global $wpdb;
	$groupby = "{$wpdb->posts}.ID";

	return $groupby;
}

function remove_query_tax() {
	remove_filter( 'posts_where', 'learn_press_add_tax_search' );
	remove_filter( 'posts_join', 'learn_press_join_term' );
	remove_filter( 'posts_groupby', 'learn_press_tax_groupby' );
}

/*
 * Course tabs
 */
add_action( 'all_admin_notices', 'learn_press_admin_course_tabs' );
function learn_press_admin_course_tabs() {
	if ( !is_admin() ) {
		return;
	}
	$admin_tabs = apply_filters(
		'learn_press_admin_tabs_info',
		array(

			10 => array(
				"link" => "edit.php?post_type=lp_course",
				"name" => __( "Courses", "learnpress" ),
				"id"   => "edit-lp_course",
			),

			20 => array(
				"link" => "edit-tags.php?taxonomy=course_category&post_type=lp_course",
				"name" => __( "Categories", "learnpress" ),
				"id"   => "edit-course_category",
			),
			30 => array(
				"link" => "edit-tags.php?taxonomy=course_tag&post_type=lp_course",
				"name" => __( "Tags", "learnpress" ),
				"id"   => "edit-course_tag",
			),

		)
	);
	ksort( $admin_tabs );
	$tabs = array();
	foreach ( $admin_tabs as $key => $value ) {
		array_push( $tabs, $key );
	}
	$pages              = apply_filters(
		'learn_press_admin_tabs_on_pages',
		array( 'edit-lp_course', 'edit-course_category', 'edit-course_tag', 'lp_course' )
	);
	$admin_tabs_on_page = array();
	foreach ( $pages as $page ) {
		$admin_tabs_on_page[$page] = $tabs;
	}


	$current_page_id = get_current_screen()->id;
	$current_user    = wp_get_current_user();
	if ( !in_array( 'administrator', $current_user->roles ) ) {
		return;
	}
	if ( !empty( $admin_tabs_on_page[$current_page_id] ) && count( $admin_tabs_on_page[$current_page_id] ) ) {
		echo '<h2 class="nav-tab-wrapper lp-nav-tab-wrapper">';
		foreach ( $admin_tabs_on_page[$current_page_id] as $admin_tab_id ) {

			$class = ( $admin_tabs[$admin_tab_id]["id"] == $current_page_id ) ? "nav-tab nav-tab-active" : "nav-tab";
			echo '<a href="' . admin_url( $admin_tabs[$admin_tab_id]["link"] ) . '" class="' . $class . ' nav-tab-' . $admin_tabs[$admin_tab_id]["id"] . '">' . $admin_tabs[$admin_tab_id]["name"] . '</a>';
		}
		echo '</h2>';
	}
}

add_action( 'admin_footer', 'learn_press_show_menu' );
function learn_press_show_menu() {
	if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'lp_course' ) {
		?>
		<script type="text/javascript">
			jQuery(window).load(function ($) {
				<?php
				if ( isset ( $_GET['taxonomy'] ) ) {
				?>
				jQuery("body").removeClass("sticky-menu");
				jQuery("#toplevel_page_learn_press").addClass('wp-has-current-submenu wp-menu-open').removeClass('wp-not-current-submenu');
				jQuery("#toplevel_page_learn_press > a").addClass('wp-has-current-submenu wp-menu-open').removeClass('wp-not-current-submenu');
				<?php
				}
				?>
				jQuery("#toplevel_page_learn_press .wp-first-item").addClass('current');
			});
		</script>
		<?php
	}
}

/**
 * Send email notification.
 *
 * @param string $to
 * @param string $action
 * @param array  $vars
 *
 * @return mixed
 */
function learn_press_send_mail( $to, $action, $vars ) {

	$email_settings = LP_Settings::instance( 'emails' );
	if ( !$email_settings->get( $action . '.enable' ) ) {
		return "The action {$action} doesnt support";
	}
	$user = get_user_by( 'email', $to );
	if ( in_array( 'administrator', $user->roles ) ) {
		//return;
	}
	// Set default template vars.
	$vars['log_in'] = apply_filters( 'learn_press_site_url', get_home_url() );

	// Send email.
	$email = new LP_Email();
	$email->set_action( $action );
	$email->parse_email( $vars );
	$email->add_recipient( $to );

	return $email->send();
}

/*
 * Send email notification when a course be published
 */
function learn_press_publish_course( $new_status, $old_status, $post ) {
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
					'course_link' => get_permalink( $post->ID )
				),
				$post,
				$instructor
			)
		);
	}
}

add_action( 'transition_post_status', 'learn_press_publish_course', 10, 3 );

/**
 * @param $user_id
 *
 * @return WP_Query
 */
function learn_press_get_enrolled_courses( $user_id ) {
	return LP()->get_user( $user_id )->get( 'enrolled-courses' );
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
		'posts_per_page'      => - 1
	);
	$my_query  = new WP_Query( $arr_query );

	return $my_query;
}


function learn_press_currency_positions() {
	return apply_filters(
		'learn_press_currency_positions',
		array(
			'left'             => __( 'Left', 'learnpress' ),
			'right'            => __( 'Right', 'learnpress' ),
			'left_with_space'  => __( 'Left with space', 'learnpress' ),
			'right_with_space' => __( 'Right with space', 'learnpress' )

		)
	);
}

/**
 * get the list of currencies with code and name
 *
 * @author  ThimPress
 * @return  array
 */
function learn_press_get_payment_currencies() {
	$currencies = array(
		'AED' => 'United Arab Emirates Dirham (د.إ)',
		'AUD' => 'Australian Dollars ($)',
		'BDT' => 'Bangladeshi Taka (৳&nbsp;)',
		'BRL' => 'Brazilian Real (R$)',
		'BGN' => 'Bulgarian Lev (лв.)',
		'CAD' => 'Canadian Dollars ($)',
		'CLP' => 'Chilean Peso ($)',
		'CNY' => 'Chinese Yuan (¥)',
		'COP' => 'Colombian Peso ($)',
		'CZK' => 'Czech Koruna (Kč)',
		'DKK' => 'Danish Krone (kr.)',
		'DOP' => 'Dominican Peso (RD$)',
		'EUR' => 'Euros (€)',
		'HKD' => 'Hong Kong Dollar ($)',
		'HRK' => 'Croatia kuna (Kn)',
		'HUF' => 'Hungarian Forint (Ft)',
		'ISK' => 'Icelandic krona (Kr.)',
		'IDR' => 'Indonesia Rupiah (Rp)',
		'INR' => 'Indian Rupee (Rs.)',
		'NPR' => 'Nepali Rupee (Rs.)',
		'ILS' => 'Israeli Shekel (₪)',
		'JPY' => 'Japanese Yen (¥)',
		'KIP' => 'Lao Kip (₭)',
		'KRW' => 'South Korean Won (₩)',
		'MYR' => 'Malaysian Ringgits (RM)',
		'MXN' => 'Mexican Peso ($)',
		'NGN' => 'Nigerian Naira (₦)',
		'NOK' => 'Norwegian Krone (kr)',
		'NZD' => 'New Zealand Dollar ($)',
		'PYG' => 'Paraguayan Guaraní (₲)',
		'PHP' => 'Philippine Pesos (₱)',
		'PLN' => 'Polish Zloty (zł)',
		'GBP' => 'Pounds Sterling (£)',
		'RON' => 'Romanian Leu (lei)',
		'RUB' => 'Russian Ruble (руб.)',
		'SGD' => 'Singapore Dollar ($)',
		'ZAR' => 'South African rand (R)',
		'SEK' => 'Swedish Krona (kr)',
		'CHF' => 'Swiss Franc (CHF)',
		'TWD' => 'Taiwan New Dollars (NT$)',
		'THB' => 'Thai Baht (฿)',
		'TRY' => 'Turkish Lira (₺)',
		'USD' => 'US Dollars ($)',
		'VND' => 'Vietnamese Dong (₫)',
		'EGP' => 'Egyptian Pound (EGP)'
	);

	return apply_filters( 'learn_press_get_payment_currencies', $currencies );
}

function learn_press_get_currency() {
	$currencies     = learn_press_get_payment_currencies();
	$currency_codes = array_keys( $currencies );
	$currency       = reset( $currency_codes );

	return apply_filters( 'learn_press_currency', LP_Settings::instance( 'general' )->get( 'currency', $currency ) );
}

function learn_press_get_currency_symbol( $currency = '' ) {
	if ( !$currency ) {
		$currency = learn_press_get_currency();
	}

	switch ( $currency ) {
		case 'AED' :
			$currency_symbol = 'د.إ';
			break;
		case 'AUD' :
		case 'CAD' :
		case 'CLP' :
		case 'COP' :
		case 'HKD' :
		case 'MXN' :
		case 'NZD' :
		case 'SGD' :
		case 'USD' :
			$currency_symbol = '&#36;';
			break;
		case 'BDT':
			$currency_symbol = '&#2547;&nbsp;';
			break;
		case 'BGN' :
			$currency_symbol = '&#1083;&#1074;.';
			break;
		case 'BRL' :
			$currency_symbol = '&#82;&#36;';
			break;
		case 'CHF' :
			$currency_symbol = '&#67;&#72;&#70;';
			break;
		case 'CNY' :
		case 'JPY' :
		case 'RMB' :
			$currency_symbol = '&yen;';
			break;
		case 'CZK' :
			$currency_symbol = '&#75;&#269;';
			break;
		case 'DKK' :
			$currency_symbol = 'kr.';
			break;
		case 'DOP' :
			$currency_symbol = 'RD&#36;';
			break;
		case 'EGP' :
			$currency_symbol = 'EGP';
			break;
		case 'EUR' :
			$currency_symbol = '&euro;';
			break;
		case 'GBP' :
			$currency_symbol = '&pound;';
			break;
		case 'HRK' :
			$currency_symbol = 'Kn';
			break;
		case 'HUF' :
			$currency_symbol = '&#70;&#116;';
			break;
		case 'IDR' :
			$currency_symbol = 'Rp';
			break;
		case 'ILS' :
			$currency_symbol = '&#8362;';
			break;
		case 'INR' :
			$currency_symbol = 'Rs.';
			break;
		case 'ISK' :
			$currency_symbol = 'Kr.';
			break;
		case 'KIP' :
			$currency_symbol = '&#8365;';
			break;
		case 'KRW' :
			$currency_symbol = '&#8361;';
			break;
		case 'MYR' :
			$currency_symbol = '&#82;&#77;';
			break;
		case 'NGN' :
			$currency_symbol = '&#8358;';
			break;
		case 'NOK' :
			$currency_symbol = '&#107;&#114;';
			break;
		case 'NPR' :
			$currency_symbol = 'Rs.';
			break;
		case 'PHP' :
			$currency_symbol = '&#8369;';
			break;
		case 'PLN' :
			$currency_symbol = '&#122;&#322;';
			break;
		case 'PYG' :
			$currency_symbol = '&#8370;';
			break;
		case 'RON' :
			$currency_symbol = 'lei';
			break;
		case 'RUB' :
			$currency_symbol = '&#1088;&#1091;&#1073;.';
			break;
		case 'SEK' :
			$currency_symbol = '&#107;&#114;';
			break;
		case 'THB' :
			$currency_symbol = '&#3647;';
			break;
		case 'TRY' :
			$currency_symbol = '&#8378;';
			break;
		case 'TWD' :
			$currency_symbol = '&#78;&#84;&#36;';
			break;
		case 'UAH' :
			$currency_symbol = '&#8372;';
			break;
		case 'VND' :
			$currency_symbol = '&#8363;';
			break;
		case 'ZAR' :
			$currency_symbol = '&#82;';
			break;
		default :
			$currency_symbol = $currency;
			break;
	}

	return apply_filters( 'learn_press_currency_symbol', $currency_symbol, $currency );
}

function learn_press_get_page_link( $key ) {
	$page_id = LP()->settings->get( $key . '_page_id' );
	if ( get_post_status( $page_id ) == 'publish' ) {
		$link = apply_filters( 'learn_press_get_page_link', get_permalink( $page_id ), $page_id, $key );
	} else {
		$link = '';
	}
	return apply_filters( 'learn_press_get_page_' . $key . '_link', $link, $page_id );
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


function learn_press_seconds_to_weeks( $secs ) {
	$secs = (int) $secs;
	if ( $secs === 0 ) {
		return false;
	}
	// variables for holding values
	$mins  = 0;
	$hours = 0;
	$days  = 0;
	$weeks = 0;
	// calculations
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
	// format result
	$result = '';
	if ( $weeks ) {
		$result .= $weeks . ' ' . __( 'week(s)', 'learnpress' ) . ' ';
	}

	if ( $days ) {
		$result .= $days . ' ' . __( 'day(s)', 'learnpress' ) . ' ';
	}

	if ( !$weeks ) {
		if ( $hours ) {
			$result .= $hours . ' ' . __( 'hour(s)', 'learnpress' ) . ' ';

		}
		if ( $mins ) {
			$result .= $mins . ' ' . __( 'min(s)', 'learnpress' ) . ' ';
		}
	}
	$result = rtrim( $result );

	return $result;
}

add_action( 'learn_press_frontend_action_retake_course', array( 'LP_AJAX', 'retake_course' ) );

function learn_press_get_query_var( $var ) {
	global $wp_query;

	$return = null;
	if ( !empty( $wp_query->query_vars[$var] ) ) {
		$return = $wp_query->query_vars[$var];
	} elseif ( !empty( $_REQUEST[$var] ) ) {
		$return = $_REQUEST[$var];
	}

	return apply_filters( 'learn_press_query_var', $return, $var );
}

///////////////////////////////


function learn_press_course_lesson_permalink_friendly( $permalink, $lesson_id, $course_id ) {

	if ( '' != get_option( 'permalink_structure' ) ) {
		if ( preg_match( '!\?lesson=([^\?\&]*)!', $permalink, $matches ) ) {
			$permalink = preg_replace( '!/?\?lesson=([^\?\&]*)!', '/' . basename( get_permalink( $matches[1] ) ), untrailingslashit( $permalink ) );
		}
	}

	return $permalink;
}

function learn_press_course_question_permalink_friendly( $permalink, $lesson_id, $course_id ) {

	if ( '' != get_option( 'permalink_structure' ) ) {
		if ( preg_match( '!\?lesson=([^\?\&]*)!', $permalink, $matches ) ) {
			$permalink = preg_replace( '!/?\?lesson=([^\?\&]*)!', '/' . basename( get_permalink( $matches[1] ) ), untrailingslashit( $permalink ) );
		}
	}

	return $permalink;
}

add_filter( 'learn_press_course_lesson_permalink', 'learn_press_course_lesson_permalink_friendly', 10, 3 );


function learn_press_user_maybe_is_a_teacher( $user = null ) {
	if ( !$user ) {
		$user = learn_press_get_current_user();
	} else if ( is_numeric( $user ) ) {
		$user = learn_press_get_user( $user );
	}
	if ( !$user ) {
		return false;
	}
	$role = in_array( 'administrator', $user->user->roles ) ? 'administrator' : false;
	if ( !$role ) {
		$role = in_array( 'lp_teacher', $user->user->roles ) ? 'lp_teacher' : false;
	}
	return apply_filters( 'learn_press_user_maybe_is_a_teacher', $role, $user->id );
}

function learn_press_process_become_a_teacher_form( $args = null ) {
	$user   = learn_press_get_current_user();
	$error  = false;
	$return = array(
		'result' => 'success'
	);

	if ( !$error ) {

		$args = wp_parse_args(
			$args,
			array(
				'name'  => null,
				'email' => null,
				'phone' => null
			)
		);

		$return['message'] = array();

		if ( !$args['name'] ) {
			$return['message'][] = learn_press_get_message( __( 'Please enter your name', 'learnpress' ), 'error' );
			$error               = true;
		}

		if ( !$args['email'] ) {
			$return['message'][] = learn_press_get_message( __( 'Please enter your email address', 'learnpress' ), 'error' );
			$error               = true;
		}
	}
	if ( !$error ) {
		$to_email        = array( get_option( 'admin_email' ) );
		$message_headers = '';
		$subject         = __( 'Please moderate', 'learnpress' );
		$notify_message  = sprintf( __( 'The user <a href="%s">%s</a> wants to be a teacher.', 'learnpress' ), admin_url( 'user-edit.php?user_id=' . $user->id ), $user->user_login ) . "\r\n";

		$notify_message .= sprintf( __( 'Name: %s', 'learnpress' ), $args['name'] ) . "\r\n";
		$notify_message .= sprintf( __( 'Email: %s', 'learnpress' ), $args['email'] ) . "\r\n";
		$notify_message .= sprintf( __( 'Phone: %s', 'learnpress' ), $args['phone'] ) . "\r\n";
		$notify_message .= wp_specialchars_decode( sprintf( __( 'Accept: %s', 'learnpress' ), wp_nonce_url( admin_url( 'user-edit.php?user_id=' . $user->id ) . '&action=accept-to-be-teacher', 'accept-to-be-teacher' ) ) ) . "\r\n";
		$args = array(
			$to_email,
			( $subject ),
			$notify_message,
			$message_headers
		);

		@call_user_func_array( 'wp_mail', $args );
		$return['message'][] = learn_press_get_message( __( 'Your request has been sent! We will get in touch with you soon!', 'learnpress' ) );

		set_transient( 'learn_press_become_teacher_sent_' . $user->id, 'yes', HOUR_IN_SECONDS * 2 );
	}
	$return['result'] = $error ? 'error' : 'success';
	return $return;
}

function learn_press_become_teacher_sent( $user_id = 0 ) {
	$sent = learn_press_user_maybe_is_a_teacher( $user_id );
	if ( !$sent ) {
		$sent = get_transient( 'learn_press_become_teacher_sent_' . $user_id ) == 'yes';
	}
	return $sent;
}

function _learn_press_translate_user_roles( $translations, $text, $context, $domain ) {

	$plugin_domain = 'learnpress';

	$roles = array(
		'Instructor',
	);

	if (
		$context === 'User role'
		&& in_array( $text, $roles )
		&& $domain !== $plugin_domain
	) {
		return translate_with_gettext_context( $text, $context, $plugin_domain );
	}

	return $translations;
}

add_filter( 'gettext_with_context', '_learn_press_translate_user_roles', 10, 4 );

/**
 * @param mixed
 * @param string
 * @param string
 */
function learn_press_output_file( $data, $file, $path = null ) {
	if ( !$path ) {
		$path = LP_PLUGIN_PATH;
	}
	ob_start();
	print_r( $data );
	$output = ob_get_clean();
	file_put_contents( $path . '/' . $file, $output );
}

/**
 * Modifies the statement $where to make the search works correct
 *
 * @param string
 *
 * @return string
 */
function learn_press_posts_where_statement_search( $where ) {
	//gets the global query var object
	global $wp_query, $wpdb;

	/**
	 * Need to wrap this block into () in order to make it works correctly when filter by specific post type => maybe a bug :)
	 * from => ( wp_2_posts.post_status = 'publish' OR wp_2_posts.post_status = 'private') OR wp_2_terms.name LIKE '%s%'
	 * to => ( ( wp_2_posts.post_status = 'publish' OR wp_2_posts.post_status = 'private') OR wp_2_terms.name LIKE '%s%' )
	 */
	$a = preg_match( '!(' . $wpdb->posts . '.post_status)!', $where );
	$b = preg_match( '!(OR\s+' . $wpdb->terms . '.name LIKE \'%' . $wp_query->get( 's' ) . '%\')!', $where );

	if ( $a && $b ) {
		// append ( to the start of the block
		$where = preg_replace( '!(' . $wpdb->posts . '.post_status)!', '( $1', $where, 1 );

		// append ) to the end of the block
		$where = preg_replace( '!(OR\s+' . $wpdb->terms . '.name LIKE \'%' . $wp_query->get( 's' ) . '%\')!', '$1 )', $where );
	}
	remove_filter( 'posts_where', 'learn_press_posts_where_statement_search', 99 );

	return $where;
}

/**
 * Filter post type for search function
 * Only search lpr_course if see the param ref=course in request
 *
 * @param $q
 */
function learn_press_filter_search( $q ) {
	if ( $q->is_main_query() && $q->is_search() && ( !empty( $_REQUEST['ref'] ) && $_REQUEST['ref'] == 'course' ) ) {
		$q->set( 'post_type', 'lp_course' );
		add_filter( 'posts_where', 'learn_press_posts_where_statement_search', 99 );

		remove_filter( 'pre_get_posts', 'learn_press_filter_search', 99 );
	}
}

add_filter( 'pre_get_posts', 'learn_press_filter_search', 99 );

/**
 * Convert an object|array to json format and send it to the browser
 *
 * @param $data
 */
function learn_press_send_json( $data ) {
	echo '<!-- LP_AJAX_START -->';
	@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
	echo wp_json_encode( $data );
	echo '<!-- LP_AJAX_END -->';
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		wp_die();
	else
		die;
}

/**
 * Get data from request
 *
 * @param string
 * @param mixed
 * @param mixed
 *
 * @return mixed
 */
function learn_press_get_request( $key, $default = null, $hash = null ) {
	$return = $default;
	if ( $hash ) {
		if ( !empty( $hash[$key] ) ) {
			$return = $hash[$key];
		}
	} else {
		if ( !empty( $_POST[$key] ) ) {
			$return = $_POST[$key];
		} elseif ( !empty( $_GET[$key] ) ) {
			$return = $_GET[$key];
		} elseif ( !empty( $_REQUEST[$key] ) ) {
			$return = $_REQUEST[$key];
		}
	}
	return $return;
}


/**
 * @return mixed|void
 */
function is_learnpress() {
	return apply_filters( 'is_learnpress', ( learn_press_is_course_archive() || learn_press_is_course_taxonomy() || learn_press_is_course() || learn_press_is_quiz() || learn_press_is_search() ) ? true : false );
}

if ( !function_exists( 'learn_press_is_search' ) ) {
	/**
	 * @return bool
	 */
	function learn_press_is_search() {
		return array_key_exists( 's', $_REQUEST ) && array_key_exists( 'ref', $_REQUEST ) && $_REQUEST['ref'] == 'course';
	}
}

if ( !function_exists( 'learn_press_is_courses' ) ) {

	/**
	 * Returns true when viewing the course type archive.
	 *
	 * @return bool
	 */
	function learn_press_is_courses() {
		return learn_press_is_course_archive();
	}
}


if ( !function_exists( 'learn_press_is_course_archive' ) ) {

	/**
	 * Returns true when viewing the course type archive.
	 *
	 * @return bool
	 */
	function learn_press_is_course_archive() {
		$is_courses  = defined( 'LEARNPRESS_IS_COURSES' ) && LEARNPRESS_IS_COURSES;
		$is_tag      = defined( 'LEARNPRESS_IS_TAG' ) && LEARNPRESS_IS_TAG;
		$is_category = defined( 'LEARNPRESS_IS_CATEGORY' ) && LEARNPRESS_IS_CATEGORY;
		return ( ( $is_courses || $is_category || $is_tag ) || is_post_type_archive( 'lp_course' ) || ( learn_press_get_page_id( 'course' ) && is_page( learn_press_get_page_id( 'course' ) ) ) ) ? true : false;
	}
}

if ( !function_exists( 'learn_press_is_course_taxonomy' ) ) {

	/**
	 * Returns true when viewing a course taxonomy archive.
	 *
	 * @return bool
	 */
	function learn_press_is_course_taxonomy() {
		return ( defined( 'LEARNPRESS_IS_TAX' ) && LEARNPRESS_IS_TAX ) || is_tax( get_object_taxonomies( 'lp_course' ) );
	}
}


if ( !function_exists( 'learn_press_is_course_category' ) ) {

	/**
	 * Returns true when viewing a course category.
	 *
	 * @param  string
	 *
	 * @return bool
	 */
	function learn_press_is_course_category( $term = '' ) {
		return ( defined( 'LEARNPRESS_IS_CATEGORY' ) && LEARNPRESS_IS_CATEGORY ) || is_tax( 'course_category', $term );
	}
}


if ( !function_exists( 'learn_press_is_course_tag' ) ) {

	/**
	 * Returns true when viewing a course tag.
	 *
	 * @param  string
	 *
	 * @return bool
	 */
	function learn_press_is_course_tag( $term = '' ) {
		return ( defined( 'LEARNPRESS_IS_TAG' ) && LEARNPRESS_IS_TAG ) || is_tax( 'course_tag', $term );
	}
}

if ( !function_exists( 'learn_press_is_course' ) ) {

	/**
	 * Returns true when viewing a single course.
	 *
	 * @return bool
	 */
	function learn_press_is_course() {
		return is_singular( array( 'lp_course' ) );
	}
}

if ( !function_exists( 'learn_press_is_quiz' ) ) {

	/**
	 * Returns true when viewing a single quiz.
	 *
	 * @return bool
	 */
	function learn_press_is_quiz() {
		return is_singular( array( LP_QUIZ_CPT ) );
	}
}

if ( !function_exists( 'learn_press_is_profile' ) ) {

	/**
	 * Returns true when viewing profile page.
	 *
	 * @return bool
	 */
	function learn_press_is_profile() {
		$profile = learn_press_get_page_id( 'profile' );
		return is_page( $profile ) || apply_filters( 'learn_press_is_profile', false ) ? true : false;
	}
}

/**
 * Return true if user is in checking out page
 *
 * @return bool
 */
function learn_press_is_checkout() {
	return is_page( learn_press_get_page_id( 'checkout' ) ) || apply_filters( 'learn_press_is_checkout', false ) ? true : false;
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
 */
function learn_press_add_notice( $message, $type = 'updated' ) {
	LP_Admin_Notice::add( $message, $type );
}

/**
 * Set user's cookie
 *
 * @param      $name
 * @param      $value
 * @param int  $expire
 * @param bool $secure
 */
function learn_press_setcookie( $name, $value, $expire = 0, $secure = false ) {
	if ( !headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );
	}
}

/**
 * Display all notices from queue and clear queue if required
 *
 * @param bool|true $clear
 */
function learn_press_print_notices( $clear = true ) {
	if ( $notices = learn_press_session_get( 'notices' ) ) {
		// Allow to reorder the position of notices
		$notice_types = apply_filters( 'learn_press_notice_types', array( 'error', 'success', 'notice' ) );

		foreach ( $notice_types as $notice_type ) {
			if ( !empty( $notices[$notice_type] ) ) {
				learn_press_get_template( "notices/{$notice_type}.php", array(
					'messages' => $notices[$notice_type]
				) );
			}
		}

		// clear queue if required
		if ( $clear ) {
			learn_press_clear_notices();
		}
	}
}

//add_filter( 'the_content', '_learn_press_print_notices', 1000 );

/**
 * Filter the login url so third-party can be customize
 *
 * @param null $redirect
 *
 * @return mixed
 */
function learn_press_get_login_url( $redirect = null ) {
	return apply_filters( 'learn_press_login_url', wp_login_url( $redirect ) );
}

function _learn_press_get_login_url( $url ) {
	if ( $profile_page = learn_press_get_page_link( 'profile' ) ) {
		$a   = parse_url( $url );
		$url = $profile_page . ( !empty( $a['query'] ) ? '?' . $a['query'] : '' );
	}
	return $url;
}

add_filter( 'learn_press_login_url', '_learn_press_get_login_url', 10 );

function learn_press_get_endpoint_url( $name, $value, $url ) {
	if ( !$url )
		$url = get_permalink();

	// Map endpoint to options
	$name = isset( LP()->query_vars[$name] ) ? LP()->query_vars[$name] : $name;

	if ( get_option( 'permalink_structure' ) ) {
		if ( strstr( $url, '?' ) ) {
			$query_string = '?' . parse_url( $url, PHP_URL_QUERY );
			$url          = current( explode( '?', $url ) );
		} else {
			$query_string = '';
		}
		$url = trailingslashit( $url ) . ( $name ? $name . '/' : '' ) . $value . $query_string;

	} else {
		$url = add_query_arg( $name, $value, $url );
	}

	return apply_filters( 'learn_press_get_endpoint_url', esc_url( $url ), $name, $value, $url );
}

function learn_press_add_endpoints() {
	if ( $endpoints = LP()->settings->get( 'checkout_endpoints' ) ) foreach ( $endpoints as $endpoint => $value ) {
		$endpoint                   = preg_replace( '!_!', '-', $endpoint );
		LP()->query_vars[$endpoint] = $value;

		add_rewrite_endpoint( $value, EP_ROOT | EP_PAGES );
	}

	if ( $endpoints = LP()->settings->get( 'profile_endpoints' ) ) foreach ( $endpoints as $endpoint => $value ) {
		$endpoint                   = preg_replace( '!_!', '-', $endpoint );
		LP()->query_vars[$endpoint] = $value;
		add_rewrite_endpoint( $value, EP_ROOT | EP_PAGES );
	}

	if ( $endpoints = LP()->settings->get( 'quiz_endpoints' ) ) foreach ( $endpoints as $endpoint => $value ) {
		$endpoint                   = preg_replace( '!_!', '-', $endpoint );
		LP()->query_vars[$endpoint] = $value;
		add_rewrite_endpoint( $value, EP_ROOT | EP_PAGES );
	}
}

add_action( 'init', 'learn_press_add_endpoints' );

function learn_press_is_yes( $value ) {
	return ( $value === 1 ) || ( $value === '1' ) || ( $value == 'yes' ) || ( $value == true ) || ( $value == 'on' );
}

/**
 * @param $value
 *
 * @return bool
 */
function _is_false_value( $value ) {
	if ( is_numeric( $value ) ) {
		return $value == 0;
	} elseif ( is_string( $value ) ) {
		return ( empty( $value ) || is_null( $value ) || in_array( $value, array( 'no', 'off', 'false' ) ) );
	}
	return !!$value;
}

function learn_press_do_parse_request( $parse, $q, $vars ) {
	// lesson
	$course_type = 'lp_course';
	$post_types  = get_post_types( '', 'objects' );

	$slug = preg_replace( '!^/!', '', $post_types[$course_type]->rewrite['slug'] );

	$current_url  = learn_press_get_current_url();
	$query_string = str_replace( trailingslashit( get_site_url() ), '', $current_url );
	if ( preg_match( '!^' . $slug . '/([^/]*)/?(.*)?!', $query_string, $matches ) ) {
		if ( !empty( $matches[2] ) ) {

		}
	}
	return true;
}

//add_filter('parse_request', 'learn_press_do_parse_request', 100, 3);
function learn_press_parse_request() {
	global $wp, $wp_rewrite;

	//echo '<div style="display:none;">';
	//print_r( $wp_rewrite );
	//print_r( $wp );
	if ( !empty( $wp->query_vars['lp_course'] ) && strpos( $wp->query_vars['lp_course'], '/' ) !== false ) {
		flush_rewrite_rules();
	}
	//if ( !empty( $_REQUEST['clean-cache'] ) ) {
	///
	//}
	//echo '</div>';


	if ( !empty( $wp->query_vars['course-query-string'] ) ) {
		$segments = explode( '/', $wp->query_vars['course-query-string'] );
		$segments = array_filter( $segments );
		if ( $segments ) {
			$ids   = array();
			$names = array();
			foreach ( $segments as $segment ) {
				if ( preg_match( '/^([0-9]+)/', $segment ) ) {
					$post_args = explode( '-', $segment, 2 );
					$ids[]     = absint( $post_args[0] );
					$names[]   = $post_args[1];
				}
			}

			if ( sizeof( $ids ) ) {
				global $wpdb;
				$ids_format   = array_fill( 0, sizeof( $ids ), '%d' );
				$names_format = array_fill( 0, sizeof( $names ), '%s' );

				$query = $wpdb->prepare( "
					SELECT ID, post_name, post_type
					FROM {$wpdb->posts}
					WHERE ID IN(" . join( ',', $ids_format ) . ")
						AND post_name IN(" . join( ',', $names_format ) . ")
					ORDER BY FIELD(ID, " . join( ',', $ids_format ) . ")
				", array_merge( $ids, $names, $ids ) );
				if ( $items = $wpdb->get_results( $query ) ) {
					$support_types = learn_press_course_get_support_item_types();
					foreach ( $items as $item ) {
						if ( in_array( $item->post_type, $support_types ) ) {
							$wp->query_vars[$item->post_type] = $item->ID;
						}
					}
				}
			}
		}
	}

	// Map query vars to their keys, or get them if endpoints are not supported
	foreach ( LP()->query_vars as $key => $var ) {
		if ( isset( $_GET[$var] ) ) {
			$wp->query_vars[$key] = $_GET[$var];
		} elseif ( isset( $wp->query_vars[$var] ) ) {
			$wp->query_vars[$key] = $wp->query_vars[$var];
		}
	}
}

add_action( 'parse_request', 'learn_press_parse_request' );

function learn_press_reset_auto_increment( $table ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->prefix}$table AUTO_INCREMENT = %d", 1 ) );
}

/**
 * @param $handle
 *
 * @return string
 */
function learn_press_get_log_file_path( $handle ) {
	return trailingslashit( LP_LOG_PATH ) . $handle . '-' . sanitize_file_name( wp_hash( $handle ) ) . '.log';
}

/**
 * Get the cart object in checkout page
 *
 * @return mixed|null|void
 */
function learn_press_get_checkout_cart() {
	return apply_filters( 'learn_press_checkout_cart', LP()->cart );
}

function learn_press_front_scripts() {
	if ( is_admin() ) {
		return;
	}
	$js = array(
		'ajax'        => admin_url( 'admin-ajax.php' ),
		'plugin_url'  => LP()->plugin_url(),
		'siteurl'     => home_url(),
		'current_url' => learn_press_get_current_url(),
		'localize'    => array(
			'button_ok'     => __( 'OK', 'learnpress' ),
			'button_cancel' => __( 'Cancel', 'learnpress' ),
			'button_yes'    => __( 'Yes', 'learnpress' ),
			'button_no'     => __( 'No', 'learnpress' )
		)
	);
	LP_Assets::add_var( 'LP_Settings', wp_json_encode( $js ), array( 'learn-press-single-course', 'learn-press-global' ) );
}

add_action( 'wp_print_scripts', 'learn_press_front_scripts' );

/**
 * Auto insert new item to user_items table when user viewing a lesson
 *
 * @param $located
 * @param $template_name
 * @param $template_path
 *
 * @return mixed
 */
function learn_press_add_user_item_if_needed( $located, $template_name, $template_path ) {
	if ( $template_name == 'single-course/content-item-lp_lesson.php' ) {
		$items       = LP_Cache::get_item_statuses( false, array() );
		$user_id     = get_current_user_id();
		$course_id   = get_the_ID();
		$course_item = LP()->global['course-item'];
		if ( $course_id && $course_item && !empty( $items[$user_id . '-' . $course_id . '-' . $course_id] ) ) {
			$key = sprintf( '%d-%d-%d', $user_id, $course_id, $course_item->ID );
			if ( empty( $items[$key] ) || $items[$key] == '' ) {
				global $wpdb;
				$parent_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_item_id FROM {$wpdb->prefix}learnpress_user_items WHERE user_id = %d AND item_id = %d", $user_id, $course_id ) );
				learn_press_update_user_item_field(
					array(
						'user_id'    => $user_id,
						'item_id'    => $course_item->ID,
						'start_time' => current_time( 'mysql' ),
						'item_type'  => get_post_type( $course_item->ID ),
						'ref_type'   => LP_COURSE_CPT,
						'status'     => get_post_type( $course_item->ID ) == LP_LESSON_CPT ? 'started' : 'viewed',
						'ref_id'     => $course_id,
						'parent_id'  => $parent_id
					)
				);
			}
		}
	}
	return $located;
}

//add_filter( 'learn_press_locate_template', 'learn_press_add_user_item_if_needed', 10, 3 );

function learn_press_set_user_timezone() {
	?>
	<script type="text/javascript">
		(function (factory) {
			if (typeof define === 'function' && define.amd) {
				// AMD (Register as an anonymous module)
				define(['jquery'], factory);
			} else if (typeof exports === 'object') {
				// Node/CommonJS
				module.exports = factory(require('jquery'));
			} else {
				// Browser globals
				factory(jQuery);
			}
		}(function ($) {

			var pluses = /\+/g;

			function encode(s) {
				return config.raw ? s : encodeURIComponent(s);
			}

			function decode(s) {
				return config.raw ? s : decodeURIComponent(s);
			}

			function stringifyCookieValue(value) {
				return encode(config.json ? JSON.stringify(value) : String(value));
			}

			function parseCookieValue(s) {
				if (s.indexOf('"') === 0) {
					// This is a quoted cookie as according to RFC2068, unescape...
					s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
				}

				try {
					// Replace server-side written pluses with spaces.
					// If we can't decode the cookie, ignore it, it's unusable.
					// If we can't parse the cookie, ignore it, it's unusable.
					s = decodeURIComponent(s.replace(pluses, ' '));
					return config.json ? JSON.parse(s) : s;
				} catch (e) {
				}
			}

			function read(s, converter) {
				var value = config.raw ? s : parseCookieValue(s);
				return $.isFunction(converter) ? converter(value) : value;
			}

			var config = $.cookie = function (key, value, options) {

				// Write

				if (arguments.length > 1 && !$.isFunction(value)) {
					options = $.extend({}, config.defaults, options);

					if (typeof options.expires === 'number') {
						var days = options.expires, t = options.expires = new Date();
						t.setMilliseconds(t.getMilliseconds() + days * 864e+5);
					}

					return (document.cookie = [
						encode(key), '=', stringifyCookieValue(value),
						options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
						options.path ? '; path=' + options.path : '',
						options.domain ? '; domain=' + options.domain : '',
						options.secure ? '; secure' : ''
					].join(''));
				}

				// Read

				var result = key ? undefined : {},
				// To prevent the for loop in the first place assign an empty array
				// in case there are no cookies at all. Also prevents odd result when
				// calling $.cookie().
					cookies = document.cookie ? document.cookie.split('; ') : [],
					i = 0,
					l = cookies.length;

				for (; i < l; i++) {
					var parts = cookies[i].split('='),
						name = decode(parts.shift()),
						cookie = parts.join('=');

					if (key === name) {
						// If second argument (value) is a function it's a converter...
						result = read(cookie, value);
						break;
					}

					// Prevent storing a cookie that we couldn't decode.
					if (!key && (cookie = read(cookie)) !== undefined) {
						result[name] = cookie;
					}
				}

				return result;
			};

			config.defaults = {};

			$.removeCookie = function (key, options) {
				// Must not alter options, thus extending a fresh object...
				$.cookie(key, '', $.extend({}, options, {expires: -1}));
				return !$.cookie(key);
			};

		}));
		jQuery.cookie('timezone', new Date().getTimezoneOffset());
	</script>
	<?php
}

add_action( 'admin_head', 'learn_press_set_user_timezone' );

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

function learn_press_sanitize_json( $string ) {

	echo json_encode( $string );
	return $string;
}

function learn_press_get_current_profile_tab() {
	global $wp_query;
	$current = '';
	if ( !empty( $_REQUEST['tab'] ) ) {
		$current = $_REQUEST['tab'];
	} else if ( !empty( $wp_query->query_vars['tab'] ) ) {
		$current = $wp_query->query_vars['tab'];
	} else {
		if ( $tabs = learn_press_user_profile_tabs() ) {
			$tab_keys = array_keys( $tabs );
			$current  = reset( $tab_keys );
		}
	}
	return $current;
}

function learn_press_user_profile_link( $user_id = 0, $tab = null ) {
	if ( !$user_id ) {
		$user = get_user_by( 'id', get_current_user_id() );
	} else {
		if ( is_numeric( $user_id ) ) {
			$user = get_user_by( 'id', $user_id );
		} else {
			$user = get_user_by( 'login', $user_id );
		}
	}

	if ( !$user ) {
		return '';
	}
	global $wp_query;
	$page_id = !empty( $wp_query->queried_object_id ) ? $wp_query->queried_object_id : ( !empty( $wp_query->query_vars['page_id'] ) ? $wp_query->query_vars['page_id'] : - 1 );
	$args    = array(
		'user' => $user->user_login
	);
	if ( $tab ) {
		$args['tab'] = $tab;
	} else {
		$args['tab'] = learn_press_get_current_profile_tab();
	}
	$args         = array_map( '_learn_press_urlencode', $args );
	$profile_link = learn_press_get_page_link( 'profile' );
	if ( $profile_link ) {
		if ( get_option( 'permalink_structure' ) /*&& learn_press_get_page_id( 'profile' )*/ ) {
			$url = $profile_link . join( "/", array_values( $args ) );
		} else {
			$url = add_query_arg( $args, $profile_link );
		}
	} else {
		$url = get_author_posts_url( $user_id );
	}
	return apply_filters( 'learn_press_user_profile_link', $url, $user_id, $tab );
}

function _learn_press_urlencode( $string ) {
	return preg_replace( '/\s/', '+', $string );
}

function learn_press_post_type_archive_link( $link, $post_type ) {
	if ( $post_type == LP_COURSE_CPT && learn_press_get_page_id( 'courses' ) ) {
		$link = learn_press_get_page_link( 'courses' );
	}
	return $link;
}

add_filter( 'post_type_archive_link', 'learn_press_post_type_archive_link', 10, 2 );

function learn_press_single_term_title( $prefix = '', $display = true ) {
	$term = get_queried_object();

	if ( !$term )
		return;

	if ( learn_press_is_course_category() ) {
		$term_name = apply_filters( 'single_course_category_title', $term->name );
	} elseif ( learn_press_is_course_tag() ) {
		$term_name = apply_filters( 'single_course_tag_title', $term->name );
	} elseif ( learn_press_is_course_taxonomy() ) {
		$term_name = apply_filters( 'single_course_term_title', $term->name );
	} else {
		return single_term_title( $prefix, $display );
	}

	if ( empty( $term_name ) )
		return single_term_title( $prefix, $display );

	if ( $display )
		echo $prefix . $term_name;
	else
		return $prefix . $term_name;
}

/**
 * @param $template
 *
 * @return string
 */
function learn_press_search_template( $template ) {
	if ( !empty( $_REQUEST['ref'] ) && ( $_REQUEST['ref'] == 'course' ) ) {
		$template = learn_press_locate_template( 'archive-course.php' );
	}
	return $template;
}

//add_filter( 'template_include', 'learn_press_search_template', 69 );

function learn_press_redirect_search() {
	if ( learn_press_is_search() ) {
		$search_page = learn_press_get_page_id( 'search' );
		if ( !is_page( $search_page ) ) {
			global $wp_query;
			wp_redirect( add_query_arg( 's', $wp_query->query_vars['s'], get_the_permalink( $search_page ) ) );
			exit();
		}
	}
}

function learn_press_get_subtabs_course() {
	$subtabs = array(
		'all'       => __( 'All', 'learnpress' ),
		'learning'  => __( 'Learning', 'learnpress' ),
		'purchased' => __( 'Purchased', 'learnpress' ),
		'finished'  => __( 'Finished', 'learnpress' ),
		'own'       => __( 'Own', 'learnpress' )
	);

	$subtabs = apply_filters( 'learn_press_profile_tab_courses_subtabs', $subtabs );
	return $subtabs;
}

add_action( 'learn_press_order_status_completed', 'learn_press_auto_enroll_user_to_courses' );
function learn_press_auto_enroll_user_to_courses( $order_id ) {
	if ( LP()->settings->get( 'disable_auto_enroll' ) == 'yes' ) {
		return;
	}
	if ( !$order = learn_press_get_order( $order_id ) ) {
		return;
	}
	if ( !$items = $order->get_items() ) {
		return;
	}
	if ( !$user = $order->get_user() ) {
		return;
	}
	$return = 0;
	foreach ( $items as $item_id => $item ) {
		$course = learn_press_get_course( $item['course_id'] );
		if ( !$course ) {
			continue;
		}
		if ( $user->has( 'enrolled-course', $course->id ) ) {
			continue;
		}
		// error. this scripts will create new order each course item
		// $return = $user->enroll( $course->id, $order_id );
		$return = learn_press_update_user_item_field( array(
			'user_id'    => learn_press_get_current_user_id(),
			'item_id'    => $course->id,
			'start_time' => current_time( 'mysql' ),
			'status'     => 'enrolled',
			'end_time'   => '0000-00-00 00:00:00',
			'ref_id'     => $course->id,
			'item_type'  => 'lp_course',
			'ref_type'   => 'lp_order',
			'parent_id'  => $user->get_course_history_id( $course->id )
		) );


	}
	return $return;
}

/**
 * Return true if enable cart
 *
 * @return bool
 */
function learn_press_is_enable_cart() {
	return defined( 'LP_ENABLE_CART' ) && LP_ENABLE_CART == true;//
	// apply_filters( 'learn_press_enable_cart', LP()->settings->get( 'enable_cart' ) == 'yes' );
}

function learn_press_get_checkout_course_( $field = null ) {

	$purchase_course = LP()->session->get( 'purchase-course' );
	$purchase_course = apply_filters( 'learn_press_get_checkout_course', $purchase_course );

	if ( $purchase_course && array_key_exists( $field, $purchase_course ) ) {
		$return = apply_filters( 'learn_press_get_checkout_course_field', $purchase_course[$field], $field, $purchase_course );
		$return = apply_filters( 'learn_press_get_checkout_course_field_' . $field, $purchase_course[$field], $purchase_course );
		return $return;
	}
	return $purchase_course;
}

/**
 * Request handler for purchase course action
 *
 * @param $course_id
 */
function learn_press_purchase_course_handler( $course_id ) {
	return LP()->cart->purchase_course_handler( $course_id );
}

/**
 * Redirect back to course if the order have one course in that
 *
 * @param $results
 * @param $order_id
 *
 * @return mixed
 */
function _learn_press_checkout_success_result( $results, $order_id ) {
	if ( $results['result'] == 'success' ) {
		if ( $order = learn_press_get_order( $order_id ) ) {
			$items              = $order->get_items();
			$enrolled_course_id = learn_press_auto_enroll_user_to_courses( $order_id );
			$course_id          = 0;
			if ( sizeof( $items ) == 1 ) {
				$item                = reset( $items );
				$course_id           = $item['course_id'];
				$results['redirect'] = get_the_permalink( $course_id );
			}
			if ( !$course_id ) {
				$course_id = $enrolled_course_id;
			}

			if ( $course = learn_press_get_course( $course_id ) ) {
				learn_press_add_message( sprintf( __( 'Congrats! You\'ve enrolled course "%s".', 'learnpress' ), $course->get_title() ) );
			}
		}
	}
	return $results;
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

//add_action( 'init', 'learn_press_redirect_search' );

if ( defined( 'LP_ENABLE_CART' ) && LP_ENABLE_CART ) {
	add_filter( 'learn_press_checkout_settings', '_learn_press_cart_settings', 10, 2 );
	function _learn_press_cart_settings( $settings, $class ) {
		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => __( 'Cart', 'learnpress' ),
					'type'  => 'title'
				),
				array(
					'title'   => __( 'Enable cart', 'learnpress' ),
					'desc'    => __( 'Check this option to enable user purchase multiple courses at one time.', 'learnpress' ),
					'id'      => $class->get_field_name( 'enable_cart' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Add to cart redirect', 'learnpress' ),
					'desc'    => __( 'Redirect to checkout immediately after adding course to cart.', 'learnpress' ),
					'id'      => $class->get_field_name( 'redirect_after_add' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'AJAX add to cart', 'learnpress' ),
					'desc'    => __( 'Using AJAX to add course to cart.', 'learnpress' ),
					'id'      => $class->get_field_name( 'ajax_add_to_cart' ),
					'default' => 'no',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Cart page', 'learnpress' ),
					'id'      => $class->get_field_name( 'cart_page_id' ),
					'default' => '',
					'type'    => 'pages-dropdown'
				)
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
			$located = learn_press_locate_template( 'single-course/enroll-button-new.php', $template_path, $default_path );
		}
		return $located;
	}

	if ( get_option( 'learn_press_no_checkout_free_course' ) !== 'yes' ) {
		///update_option( 'learn_press_no_checkout_free_course', 'yes' );
	}
}

/**
 * Return TRUE debug mode is ON
 *
 * @return boolean
 */
function learn_press_debug_enable() {
	if ( defined( 'LP_DEBUG' ) ) {
		return LP_DEBUG;
	}
	define( 'LP_DEBUG', LP()->settings->get( 'debug' ) == 'yes' ? true : false );
	return learn_press_debug_enable();
}

/**
 * Returns checkout url from setting
 *
 * @return mixed|null|void
 */
function learn_press_get_checkout_url() {
	$checkout_url = learn_press_get_page_link( 'checkout' );
	return apply_filters( 'learn_press_get_checkout_url', $checkout_url );
}

/**
 * @return mixed|null|void
 */
function learn_press_checkout_needs_payment() {
	return LP()->cart->needs_payment();
}

/**
 * Return plugin basename
 *
 * @param $filepath
 *
 * @return string
 */
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

	$file = preg_replace( '#^' . preg_quote( $sp_plugin_dir, '#' ) . '/|^' . preg_quote( $plugin_dir, '#' ) . '/|^' . preg_quote( $mu_plugin_dir, '#' ) . '/#', '', $file );
	$file = trim( $file, '/' );
	return strtolower( $file );
}

function learn_press_update_log( $version, $data ) {
	$logs = get_option( 'learn_press_update_logs' );
	if ( !$logs ) {
		$logs = array( $version => $data );
	} else {
		$logs[$version] = $data;
	}
	update_option( 'learn_press_update_logs', $logs );
}

/*
add_action( 'wp_default_scripts', 'learn_press_default_scripts' );
add_action( 'wp_default_styles', 'learn_press_default_scripts' );
function learn_press_default_scripts( $wp_scripts ) {
	$wp_scripts->default_dirs[] = '/wp-content/plugins/learnpress/assets/';
}*/

// Debugging
include_once "debug.php";

function learn_press_debug() {
	$args = func_get_args();
	$arg  = true;
	echo '<pre>';
	if ( $args ) foreach ( $args as $arg ) {
		print_r( $arg );
	}
	echo '</pre>';
	if ( $arg === true ) {
		die();
	}
}

if ( !function_exists( 'learn_press_profile_localize_script' ) ) {

	/**
	 * Translate javascript text
	 */
	function learn_press_profile_localize_script( $assets ) {
		$translate = array(
			'confirm_cancel_order' => array(
				'message' => __( 'Are you sure you want to cancel order?', 'learnpress' ),
				'title'   => __( 'Cancel Order', 'learnpress' )
			)
		);
		LP_Assets::add_localize( $translate );
	}

}
add_action( 'learn_press_enqueue_scripts', 'learn_press_profile_localize_script' );

add_action( 'init', 'learn_press_cancel_order_process' );
if ( !function_exists( 'learn_press_cancel_order_process' ) ) {
	function learn_press_cancel_order_process() {
		if ( empty( $_REQUEST['cancel-order'] ) || empty( $_REQUEST['lp-nonce'] ) || !wp_verify_nonce( $_REQUEST['lp-nonce'], 'cancel-order' ) || is_admin() ) {
			return;
		}

		$order_id = absint( $_REQUEST['cancel-order'] );
		$order    = learn_press_get_order( $order_id );
		$user     = learn_press_get_current_user();

		$url = learn_press_user_profile_link( $user->id, LP()->settings->get( 'profile_endpoints.profile-orders' ) );
		if ( !$order ) {
			learn_press_add_message( sprintf( __( 'Order number <strong>%s</strong> not found', 'learnpress' ), $order_id ), 'error' );
		} else if ( $order->has_status( 'pending' ) ) {
			$order->update_status( 'cancelled' );
			$order->add_note( __( 'Order cancelled by customer', 'learnpress' ) );

			// set updated message
			learn_press_add_message( sprintf( __( 'Order number <strong>%s</strong> has been cancelled', 'learnpress' ), $order->get_order_number() ) );
			$url = $order->get_cancel_order_url( true );
		} else {
			learn_press_add_message( sprintf( __( 'Order number <strong>%s</strong> can not cancelled', 'learnpress' ), $order->get_order_number() ), 'error' );
		}
		wp_safe_redirect( $url );
		exit();
	}
}