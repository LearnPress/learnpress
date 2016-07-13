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

function learn_press_plugin_url( $sub_dir = '' ) {
	return LP_PLUGIN_URL . ( $sub_dir ? "{$sub_dir}" : '' );
}

/**
 * Get the plugin path.
 *
 * @param string $sub_dir
 *
 * @return string
 */
function learn_press_plugin_path( $sub_dir = '' ) {
	return LP_PLUGIN_PATH . ( $sub_dir ? "{$sub_dir}" : '' );
}

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

function learn_press_uniqid( $prefix = '' ) {
	$hash = str_replace( '.', '', microtime( true ) . uniqid() );
	return apply_filters( 'learn_press_generate_unique_hash', $prefix . $hash, $prefix );
}

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
 * List all registered question types into dropdown
 *
 * @param array
 *
 * @return string
 */
function learn_press_dropdown_question_types( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'name'     => 'learn-press-dropdown-question-types',
			'id'       => '',
			'class'    => '',
			'selected' => '',
			'echo'     => true
		)
	);
	if ( !$args['id'] ) {
		$args['id'] = $args['name'];
	}
	$args['class'] = 'lp-dropdown-question-types' . ( $args['class'] ? ' ' . $args['class'] : '' );
	$types         = learn_press_question_types();
	$output        = sprintf( '<select name="%s" id="%s" class="%s"%s>', $args['name'], $args['id'], $args['class'], $args['selected'] ? 'data-selected="' . $args['selected'] . '"' : '' );
	foreach ( $types as $slug => $name ) {
		$output .= sprintf( '<option value="%s"%s>%s</option>', $slug, selected( $slug == $args['selected'], true, false ), $name );
	}
	$output .= '</select>';
	if ( $args['echo'] ) {
		echo $output;
	}
	return $output;
}

function learn_press_email_formats_dropdown( $args = array() ) {
	$args    = wp_parse_args(
		$args,
		array(
			'name'     => 'learn-press-dropdown-email-formats',
			'id'       => '',
			'class'    => '',
			'selected' => '',
			'echo'     => true
		)
	);
	$formats = array(
		'plain_text' => __( 'Plain text', 'learnpress' ),
		'html'       => __( 'HTML', 'learnpress' ),
		'multipart'  => __( 'Multipart', 'learnpress' )
	);
	$output  = sprintf( '<select name="%s" id="%s" class="%s" %s>', $args['name'], $args['id'], $args['class'], '' );
	foreach ( $formats as $name => $text ) {
		$output .= sprintf( '<option value="%s" %s>%s</option>', $name, selected( $args['selected'] == $name, true, false ), $text ) . "\n";
	}
	$output .= '</select>';

	if ( $args['echo'] ) echo $output;
	return $output;
}

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

function learn_press_is_current_url( $url ) {
	return strcmp( learn_press_get_current_url(), learn_press_sanitize_url( $url ) ) == 0;
}

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

function learn_press_question_name_from_slug( $slug ) {
	$types = learn_press_question_types();

	$name = !empty( $types[$slug] ) ? $types[$slug] : '';
	return apply_filters( 'learn_press_question_name_from_slug', $name, $slug );
}

function learn_press_section_item_types() {
	$types = array(
		'lp_lesson' => __( 'Lesson', 'learnpress' ),
		'lp_quiz'   => __( 'Quiz', 'learnpress' )
	);
	return apply_filters( 'learn_press_section_item_types', $types );
}

/**
 * Add more custom rewrite tags
 */
function learn_press_add_rewrite_tags() {

	add_rewrite_tag( '%lesson%', '([^&]+)' );
	add_rewrite_tag( '%question%', '([^&]+)' );
	add_rewrite_tag( '%user%', '([^/]*)' );

	do_action( 'learn_press_add_rewrite_tags' );
}

add_action( 'init', 'learn_press_add_rewrite_tags', 1000, 0 );

/**
 * Add more custom rewrite rules
 */
function learn_press_add_rewrite_rules() {
	$rewrite_prefix = get_option( 'learn_press_permalink_structure' );
	// lesson
	$course_type = 'lp_course';
	$post_types  = get_post_types( array( 'name' => $course_type ), 'objects' );
	$slug        = $post_types[$course_type]->rewrite['slug'];

	add_rewrite_rule(
		apply_filters( 'learn_press_lesson_rewrite_rule', '^' . $slug . '/([^/]*)/?([^/]*)?/?' ),
		apply_filters( 'learn_press_lesson_rewrite_rule_redirect', 'index.php?' . $course_type . '=$matches[1]&lesson=$matches[2]' ),
		'top'
	);

	// question
	$quiz_type   = LP()->quiz_post_type;
	$post_types  = get_post_types( array( 'name' => $quiz_type ), 'objects' );
	$slug        = $post_types[$quiz_type]->rewrite['slug'];
	$current_uri = learn_press_get_current_url();
	if ( ( $quiz_endpoint = LP()->settings->get( 'quiz_endpoints.results' ) ) && preg_match( '/\/(' . $quiz_endpoint . ')\/([0-9]+)?/', $current_uri, $matches ) ) {
		$rewrite_redirect = 'index.php?' . $quiz_type . '=$matches[1]';
		if ( !empty( $matches[1] ) ) {
			if ( !empty( $matches[2] ) ) {
				$rewrite_redirect .= '&' . $matches[1] . '=' . $matches[2];
			} else {
				$rewrite_redirect .= '&' . $matches[1] . '=0';
			}
		}
		add_rewrite_rule(
			apply_filters( 'learn_press_quiz_results_rewrite_rule', '^' . $slug . '/([^/]*)/([^/]*)/?' ),
			apply_filters( 'learn_press_quiz_results_rewrite_rule_redirect', $rewrite_redirect ),
			'top'
		);

	} else {
		add_rewrite_rule(
			apply_filters( 'learn_press_question_rewrite_rule', '^' . $slug . '/([^/]*)/([^/]*)/?' ),
			apply_filters( 'learn_press_question_rewrite_rule_redirect', 'index.php?' . $quiz_type . '=$matches[1]&question=$matches[2]' ),
			'top'
		);
	}
	if ( $profile_id = learn_press_get_page_id( 'profile' ) ) {
		add_rewrite_rule(
			'^' . $rewrite_prefix . get_post_field( 'post_name', $profile_id ) . '/([^/]*)/?([^/]*)/?([^/]*)/?([^/]*)/?([^/]*)/?',
			'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&id=$matches[3]&paged=$matches[4]',
			'top'
		);
		/*
		add_rewrite_rule(
			'^' . get_post_field( 'post_name', $profile_id ) . '/([^/]*)/?([^/]*)/?([^/]*)/?',
			'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&id=$matches[3]',
			'top'
		);
		/*
		add_rewrite_rule(
			'^' . get_post_field( 'post_name', $profile_id ) . '/([^/]*)/?([^/]*)/?([^/]*)/?([^/]*)/?([^/]*)/?',
			'index.php?page_id=' . $profile_id . '&user=$matches[1]&view=$matches[2]&id=$matches[3]&section=$matches[4]&paged=$matches[5]',
			'top'
		);*/
	}
	do_action( 'learn_press_add_rewrite_rules' );
}

add_action( 'init', 'learn_press_add_rewrite_rules', 1000, 0 );

function learn_press_update_permalink_structure() {
	global $pagenow;
	if ( $pagenow != 'options-permalink.php' ) {
		return;
	}
	if ( strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' ) {
		return;
	}
	$rewrite_prefix      = '';
	$permalink_structure = !empty( $_REQUEST['permalink_structure'] ) ? $_REQUEST['permalink_structure'] : '';
	if ( $permalink_structure ) {
		$segs = explode( '/', $permalink_structure );
		if ( sizeof( $segs ) ) {
			foreach ( $segs as $seg ) {
				if ( strpos( $seg, '%' ) !== false || $seg == 'archives' ) {
					break;
				}
				$rewrite_prefix[] = $seg;
			}
		}
		$rewrite_prefix = array_filter( $rewrite_prefix );
		if ( sizeof( $rewrite_prefix ) ) {
			$rewrite_prefix = join( '/', $rewrite_prefix ) . '/';
		} else {
			$rewrite_prefix = '';
		}
	}
	update_option( 'learn_press_permalink_structure', $rewrite_prefix );
}

add_action( 'init', 'learn_press_update_permalink_structure' );
/**
 * This function parse query vars and put into request
 */
function learn_press_parse_query_vars_to_request() {
	global $wp_query, $wp;
	if ( isset( $wp_query->query['user'] ) ) {
		/*if ( !get_option( 'permalink_structure' ) ) {
			$wp_query->query_vars['user']     = !empty( $_REQUEST['user'] ) ? $_REQUEST['user'] : null;
			$wp_query->query_vars['tab']      = !empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : null;
			$wp_query->query_vars['order_id'] = !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : null;
			$wp_query->query['user']          = !empty( $_REQUEST['user'] ) ? $_REQUEST['user'] : null;
			$wp_query->query['tab']           = !empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : null;
			$wp_query->query['order_id']      = !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : null;
		} else {
			list( $username, $tab, $id ) = explode( '/', $wp_query->query['user'] );
			$wp_query->query_vars['user']     = $username;
			$wp_query->query_vars['tab']      = $tab;
			$wp_query->query_vars['order_id'] = $id;
			$wp_query->query['user']          = $username;
			$wp_query->query['tab']           = $tab;
			$wp_query->query['order_id']      = $id;
		}*/
	}
	global $wpdb;
	// if lesson name is passed, find it's ID and put into request
	/*if ( !empty( $wp_query->query_vars['lesson'] ) ) {
		if ( $lesson_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s", $wp_query->query_vars['lesson'], LP()->lesson_post_type ) ) ) {
			$_REQUEST['lesson'] = $lesson_id;
			$_GET['lesson']     = $lesson_id;
			$_POST['lesson']    = $lesson_id;
		}
	}*/
	// if question name is passed, find it's ID and put into request
	if ( !empty( $wp_query->query_vars['question'] ) ) {
		if ( $question_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s", $wp_query->query_vars['question'], LP()->question_post_type ) ) ) {
			$_REQUEST['question'] = $question_id;
			$_GET['question']     = $question_id;
			$_POST['question']    = $question_id;
		}
	}

}

add_action( 'wp', 'learn_press_parse_query_vars_to_request' );

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

	return apply_filters( 'learn_press_get_product_terms', $terms, $course_id, $taxonomy, $args );
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

function learn_press_get_post_by_name( $name, $single = true, $type = null ) {
	global $wpdb;
	$query = $wpdb->prepare( "
		SELECT *
		FROM {$wpdb->posts}
		WHERE post_name = %s
		", $name );
	if ( $type ) {
		$query .= " AND post_type IN ('" . join( "','", $type ) . "')";
	}

	$posts = $wpdb->get_results( $query );
	if ( $posts && $single ) {
		return $posts[0];
	}
	return $posts;
}

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
		return defined( 'DOING_AJAX' );
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
	return LP_Settings::instance()->get( "{$name}_page_id", false );
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

/**
 * Function update course for user
 *
 * @param $user_id   int User ID
 * @param $course_id int Course ID
 *
 * @return bool
 */
function learn_press_update_user_course( $user_id, $course_id ) {
	if ( !$user_id || !$course_id ) {
		return false;
	}
	$course   = get_user_meta( $user_id, '_lpr_user_course', true );
	$course[] = apply_filters( 'learn_press_update_user_course_id', $course_id );
	$course   = apply_filters( 'learn_press_update_user_course', array_unique( $course ) );

	update_user_meta( $user_id, '_lpr_user_course', $course );

	$course_user = get_user_meta( $user_id, '_lpr_course_user', true );

	if ( !$course_user ) {
		$course_user = array();
	}
	if ( !in_array( $user_id, $course_id ) ) {
		array_push( $course_user, $user_id );
	}
	update_post_meta( $course_id, '_lpr_course_user', $course_user );

	// Remove item from user wishlist
	learn_press_update_wish_list( $user_id, $course_id );

	return true;
}


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

add_action( 'pre_get_posts', 'learn_press_pre_get_items', 10 );
function learn_press_pre_get_items( $query ) {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	global $post_type;
	global $pagenow;
	global $wpdb;

	if ( current_user_can( LP()->teacher_role ) && is_admin() && $pagenow == 'edit.php' ) {
		if ( in_array( $post_type, array( 'lp_course', LP()->lesson_post_type, LP()->quiz_post_type, LP()->question_post_type ) ) ) {
			$items = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts
					WHERE post_type = %s
					AND post_author = %d",
					$post_type,
					get_current_user_id()
				)
			);

			if ( count( $items ) == 0 ) {
				$query->set( 'post_type', 'lpr_empty' );
			} else {
				$query->set( 'post__in', $items );
			}
			add_filter( 'views_edit-' . $post_type . '', 'learn_press_restrict_items', 10 );

			return;
		}
	}
}

function _is_false_value( $value ) {
	if ( is_numeric( $value ) ) {
		return $value == 0;
	} elseif ( is_string( $value ) ) {
		return ( empty( $value ) || is_null( $value ) || in_array( $value, array( 'no', 'off', 'false' ) ) );
	}
	return !!$value;
}

/**
 * This helper function is useful in case a meta is not exists in database
 * but we need to set default value for it before get it value
 *
 * @param $check
 * @param $object_id
 * @param $meta_key
 * @param $single
 *
 * @return string
 */
function learn_press_set_default_post_meta( $check, $object_id, $meta_key, $single ) {

	switch ( $meta_key ) {
		// quiz
		case '_lp_show_result':
		case '_lp_show_check_answer':
		case '_lp_show_hint':
			// course
		case '_lp_payment':
		case '_lp_required_enroll':
		case '_lp_course_result':
			// lesson
		case '_lp_preview':
			remove_filter( 'get_post_metadata', 'learn_press_set_default_post_meta', 999 );
			if ( !get_post_meta( $object_id, $meta_key, $single ) ) {
				$_check    = false;
				$post_type = get_post_type( $object_id );
				if ( $post_type == 'lp_quiz' && in_array( $meta_key, array( '_lp_show_result', '_lp_show_check_answer', '_lp_show_hint' ) ) ) {
					if ( $meta_key == '_lp_show_hint' ) {
						$_check = 'yes';
					} else {
						$_check = 'no';
					}
				} elseif ( $post_type == 'lp_course' && in_array( $meta_key, array( '_lp_payment', '_lp_required_enroll', '_lp_course_result' ) ) ) {
					if ( $meta_key == '_lp_course_result' ) {
						$_check = 'evaluate_lesson';
					} else if ( $meta_key == '_lp_required_enroll' ) {
						$_check = 'yes';
					} else {
						$_check = 'no';
					}
				} elseif ( $post_type == 'lp_lesson' ) {
					$_check = 'no';
				}
				if ( $_check ) {
					$check = $_check;
					update_post_meta( $object_id, $meta_key, $check );
					update_meta_cache( 'post', array( $object_id ) );
				}
			}
			add_filter( 'get_post_metadata', 'learn_press_set_default_post_meta', 999, 4 );
	}
	return $check;
}

add_filter( 'get_post_metadata', 'learn_press_set_default_post_meta', 999, 4 );
/**
 * @param $views
 *
 * @return mixed
 */
function learn_press_restrict_items( $views ) {
	$post_type = get_query_var( 'post_type' );
	$new_views = array(
		'all'     => __( 'All', 'learnpress' ),
		'publish' => __( 'Published', 'learnpress' ),
		'private' => __( 'Private', 'learnpress' ),
		'pending' => __( 'Pending Review', 'learnpress' ),
		'future'  => __( 'Scheduled', 'learnpress' ),
		'draft'   => __( 'Draft', 'learnpress' ),
		'trash'   => __( 'Trash', 'learnpress' ),
	);

	$url = 'edit.php';

	foreach ( $new_views as $view => $name ) {

		$query = array(
			'post_type' => $post_type
		);

		if ( $view == 'all' ) {
			$query['all_posts'] = 1;
			$class              = ( get_query_var( 'all_posts' ) == 1 || ( get_query_var( 'post_status' ) == '' && get_query_var( 'author' ) == '' ) ) ? ' class="current"' : '';

		} else {
			$query['post_status'] = $view;
			$class                = ( get_query_var( 'post_status' ) == $view ) ? ' class="current"' : '';
		}

		$result = new WP_Query( $query );

		if ( $result->found_posts > 0 ) {

			$views[$view] = sprintf(
				'<a href="%s"' . $class . '>' . __( $name, 'learnpress' ) . ' <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( $query, $url ) ),
				$result->found_posts
			);

		} else {

			unset( $views[$view] );

		}

	}
	// remove view 'mine'
	unset( $views['mine'] );

	return $views;
}


// processing registration

// add instructor registration button in register page and admin bar
function learn_press_edit_registration() {
	?>
	<p>
		<label for="become_teacher">
			<input type="checkbox" name="become_teacher" id="become_teacher">
			<?php _e( 'Want to be an instructor?', 'learnpress' ) ?>
		</label>
	</p>

	<?php
}

// process instructor registration button
function learn_press_registration_save( $user_id ) {
	if ( isset( $_POST['become_teacher'] ) ) {
		//update_user_meta( $user_id, '_lpr_be_teacher', $_POST['become_teacher'] );
		$new_user = new WP_User( $user_id );
		$new_user->set_role( LP()->teacher_role );
	}
}

/*
 * Get LearnPress profile page ID
 */
function learn_press_get_profile_page_id() {
	$args  = array(
		'meta_key'    => '_lpr_is_profile_page',
		'meta_value'  => '1',
		'post_type'   => 'page',
		'post_status' => 'publish',
	);
	$query = new WP_Query( $args );
	if ( $query->post_count == 0 ) {
		return 0;
	}

	return $query->posts[0]->ID;
}

/*
 * Check LeanrPress profile method
 */
function learn_press_has_profile_method() {
	$lpr_profile = get_option( '_lpr_settings_general', array() );
	if ( isset( $lpr_profile['set_page'] ) && $lpr_profile['set_page'] == 'lpr_profile' ) {
		return true;
	}

	return false;
}

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
if ( learn_press_has_profile_method() ) {
	add_filter( 'learn_press_instructor_profile_link', 'learn_press_get_profile_link', 10, 3 );
}
function learn_press_get_profile_link( $link, $user_id, $course_id ) {
	///_deprecated_function( __FUNCTION__, '1.0', 'learn_press_course_profile_link');
	return learn_press_course_profile_link( $course_id );
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
 * Set meta data start date for published course
 */
add_action( 'transition_post_status', 'learn_press_set_start_date', 10, 3 );
function learn_press_set_start_date( $new_status, $old_status, $post ) {
	if ( $old_status != 'publish' && $new_status == 'publish' ) {
		$time        = strtotime( get_post( $post )->post_modified );
		$format_time = date( "m/d/Y", $time );
		update_post_meta( $post, '_lpr_course_start_date', $format_time );
	}
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
add_action( 'all_admin_notices', 'learn_press_course_tabs' );
function learn_press_course_tabs() {
	if ( !is_admin() ) {
		return;
	}
	$admin_tabs = apply_filters(
		'learn_press_admin_tabs_info',
		array(

			10 => array(
				"link" => "edit.php?post_type=lp_course",
				"name" => __( "Courses", "learn_press" ),
				"id"   => "edit-lp_course",
			),

			20 => array(
				"link" => "edit-tags.php?taxonomy=course_category&post_type=lp_course",
				"name" => __( "Categories", "learn_press" ),
				"id"   => "edit-course_category",
			),
			30 => array(
				"link" => "edit-tags.php?taxonomy=course_tag&post_type=lp_course",
				"name" => __( "Tags", "learn_press" ),
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

	$email_settings = LP_Settings::instance( 'emails' );// get_option( '_lpr_settings_emails' );


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
function learn_press_get_passed_courses( $user_id ) {

	$user = learn_press_get_current_user();
	return $user->get_finished_courses();

	$pid = get_user_meta( $user_id, '_lpr_course_finished', true );
	if ( !$pid ) {
		$pid = array( 0 );
	}
	$arr_query = array(
		'post_type'           => 'lp_course',
		'post__in'            => $pid,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'posts_per_page'      => - 1
	);
	$my_query  = new WP_Query( $arr_query );

	return $my_query;
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

add_filter( 'pre_get_posts', 'learn_press_pre_get_post', 0 );
function learn_press_pre_get_post( $q ) {
	if ( is_admin() ) {
		return $q;
	}
	if ( $q->is_main_query() && $q->is_page() && ( $q->queried_object && $q->queried_object->ID == ( $page_id = learn_press_get_page_id( 'courses' ) ) && $page_id ) ) {
		$q->set( 'post_type', 'lp_course' );
		$q->set( 'page', '' );
		$q->set( 'pagename', '' );

		// Fix conditional Functions
		$q->is_archive           = true;
		$q->is_post_type_archive = true;
		$q->is_singular          = false;
		$q->is_page              = false;
	}

	return $q;
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
	$link    = apply_filters( 'learn_press_get_page_link', get_permalink( $page_id ), $page_id, $key );
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
		$notify_message  = sprintf( __( 'The user <a href="%s">%s</a> want to be a teacher.', 'learnpress' ), admin_url( 'user-edit.php?user_id=' . $user->id ), $user->user_login ) . "\r\n";

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

	// append ( to the start of the block
	$where = preg_replace( '!(' . $wpdb->posts . '.post_status)!', '( $1', $where, 1 );

	// appdn ) to the end of the block
	$where = preg_replace( '!(OR\s+' . $wpdb->terms . '.name LIKE \'%' . $wp_query->get( 's' ) . '%\')!', '$1 )', $where );

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
 * Controls WP displays the courses in a page which setup to display on homepage
 *
 * @param $q
 */
function learn_press_pre_get_posts( $q ) {
	// We only want to affect the main query and not in admin
	if ( !$q->is_main_query() || is_admin() ) {
		return;
	}
	if ( ( learn_press_is_courses() || learn_press_is_course_taxonomy() ) && $limit = absint( LP()->settings->get( 'archive_course_limit' ) ) ) {
		$q->set( 'posts_per_page', $limit );
	}
	if ( $q->get( 'post_type' ) == 'lp_course' && is_single() ) {
		$course_name       = $q->get( 'lp_course' );
		$course_name_parts = explode( '/', $course_name );
		$course_item       = null;
		if ( sizeof( $course_name_parts ) > 1 ) {
			$q->set( 'lp_course', $course_name_parts[0] );
			$course_item = $course_name_parts[1];
		}
		if ( !$course_item ) {
			foreach ( array( 'lesson', 'quiz' ) as $item_type ) {
				$q->get( $item_type );
				if ( $q->get( $item_type ) ) {
					$course_item = $q->get( $item_type );
					break;
				}
			}
		}
		// If we have the item's name in course permalink url, get it
		if ( $course_item ) {
			// grab the ID of the item
			if ( preg_match( '!^([0-9]+)-!', $course_item, $matches ) ) {
				$item_id   = absint( $matches[1] );
				$item_name = str_replace( $matches[0], '', $course_item );
				$_post     = get_post( $item_id );//learn_press_get_post_by_name( $course_name_parts[1], true, array( 'lp_lesson', 'lp_quiz') );
				if ( $_post ) {
					if ( $_post->post_type == 'lp_lesson' ) {
						$q->set( 'lesson', $item_name );
						$q->set( 'course-item', 'lesson' );

						$_REQUEST['lesson']      = $item_name;
						$_REQUEST['lesson_id']   = $_post->ID;
						$_REQUEST['course-item'] = 'lesson';

					} elseif ( $_post->post_type == 'lp_quiz' ) {
						$q->set( 'quiz', $item_name );
						$q->set( 'course-item', 'quiz' );

						$_REQUEST['quiz']        = $item_name;
						$_REQUEST['quiz_id']     = $_post->ID;
						$_REQUEST['course-item'] = 'quiz';

					}
				}
				do_action( 'learn_press_parse_query_to_request', $course_item );
			} else {
				learn_press_404_page();
			}
		}
	}


	// Fix for verbose page rules
	if ( $GLOBALS['wp_rewrite']->use_verbose_page_rules && isset( $q->queried_object_id ) && $q->queried_object_id === learn_press_get_page_id( 'courses' ) ) {
		$q->set( 'post_type', 'lp_course' );
		$q->set( 'page', '' );
		$q->set( 'pagename', '' );

		// Fix conditional Functions
		$q->is_archive           = true;
		$q->is_post_type_archive = true;
		$q->is_singular          = false;
		$q->is_page              = false;

	}


	// Fix for endpoints on the homepage
	if ( $q->is_home() && 'page' == get_option( 'show_on_front' ) && get_option( 'page_on_front' ) != $q->get( 'page_id' ) ) {
		$_query = wp_parse_args( $q->query );
		/*if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->query_vars ) ) ) {
			$q->is_page     = true;
			$q->is_home     = false;
			$q->is_singular = true;

			$q->set( 'page_id', get_option('page_on_front') );
		}*/
	}

	// When orderby is set, WordPress shows posts. Get around that here.
	if ( $q->is_home() && 'page' == get_option( 'show_on_front' ) && get_option( 'page_on_front' ) == learn_press_get_page_id( 'courses' ) ) {
		$_query = wp_parse_args( $q->query );
		if ( empty( $_query ) || !array_diff( array_keys( $_query ), array( 'preview', 'page', 'paged', 'cpage', 'orderby' ) ) ) {
			$q->is_page = true;
			$q->is_home = false;
			$q->set( 'page_id', get_option( 'page_on_front' ) );
			$q->set( 'post_type', 'lp_course' );
		}
	}

	if ( $q->is_page() && 'page' == get_option( 'show_on_front' ) && $q->get( 'page_id' ) == learn_press_get_page_id( 'courses' ) && learn_press_get_page_id( 'courses' ) ) {
		$q->set( 'post_type', 'lp_course' );
		$q->set( 'page_id', '' );
		if ( isset( $q->query['paged'] ) ) {
			$q->set( 'paged', $q->query['paged'] );
		}

		global $wp_post_types;

		$course_page = get_post( learn_press_get_page_id( 'courses' ) );

		$wp_post_types['lp_course']->ID         = $course_page->ID;
		$wp_post_types['lp_course']->post_title = $course_page->post_title;
		$wp_post_types['lp_course']->post_name  = $course_page->post_name;
		$wp_post_types['lp_course']->post_type  = $course_page->post_type;
		$wp_post_types['lp_course']->ancestors  = get_ancestors( $course_page->ID, $course_page->post_type );

		$q->is_singular          = false;
		$q->is_post_type_archive = true;
		$q->is_archive           = true;
		$q->is_page              = true;

	} elseif ( !$q->is_post_type_archive( 'lp_course' ) && !$q->is_tax( get_object_taxonomies( 'lp_course' ) ) ) {
		return;
	}

}

add_action( 'pre_get_posts', 'learn_press_pre_get_posts' );

function learn_press_init() {
	if ( LP()->settings->get( 'instructor_registration' ) == 'yes' ) {
		add_action( 'register_form', 'learn_press_edit_registration' );
		add_action( 'user_register', 'learn_press_registration_save', 10, 1 );
	}
}

add_action( 'init', 'learn_press_init' );

/**
 * Gets all statuses that order supported
 *
 * @return array
 */
/*function learn_press_get_order_statuses() {
	$order_statuses = array(
		'lp-pending'    => _x( 'Pending Payment', 'Order status', 'learnpress' ),
		'lp-processing' => _x( 'Processing', 'Order status', 'learnpress' ),
		'lp-completed'  => _x( 'Completed', 'Order status', 'learnpress' ),
	);
	return apply_filters( 'learn_press_order_statuses', $order_statuses );
}*/

function is_learnpress() {
	return apply_filters( 'is_learnpress', ( learn_press_is_course_archive() || learn_press_is_course_taxonomy() || learn_press_is_course() || learn_press_is_quiz() || learn_press_is_search() ) ? true : false );
}

if ( !function_exists( 'learn_press_is_search' ) ) {
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
		return ( is_post_type_archive( 'lp_course' ) || ( learn_press_get_page_id( 'course' ) && is_page( learn_press_get_page_id( 'course' ) ) ) ) ? true : false;
	}
}


if ( !function_exists( 'learn_press_is_course_taxonomy' ) ) {

	/**
	 * Returns true when viewing a course taxonomy archive.
	 *
	 * @return bool
	 */
	function learn_press_is_course_taxonomy() {
		return is_tax( get_object_taxonomies( 'lp_course' ) );
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
		return is_tax( 'course_category', $term );
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
		return is_tax( 'course_tag', $term );
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
		return is_singular( array( LP()->quiz_post_type ) );
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
function learn_press_add_notice( $message, $type = 'notice' ) {
	if ( $message === false ) {
		return false;
	}
	$notices = LP_Session::get( 'notices' );
	if ( empty( $notices ) ) {
		$notices = array(
			'success' => array(),
			'error'   => array(),
			'notice'  => array()
		);
	}

	$notices[$type][] = $message;

	LP_Session::set( 'notices', $notices );
}

/**
 * Clear all notices in queue
 *
 */
function learn_press_clear_notices() {
	LP_Session::set( 'notices', null );
	do_action( 'learn_press_clear_notices' );
}

/**
 * Display all notices from queue and clear queue if required
 *
 * @param bool|true $clear
 */
function learn_press_print_notices( $clear = true ) {
	if ( $notices = LP_Session::get( 'notices' ) ) {

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

function learn_press_get_notices( $clear = false ) {
	ob_start();
	learn_press_print_notices( $clear );
	return ob_get_clean();
}

function _learn_press_print_notices( $content ) {
	ob_start();
	learn_press_print_notices();
	$notices = ob_get_clean();
	$content = $notices . $content;

	if ( current_filter() != 'the_content' ) {
		echo $content;
	}
	return $content;
}

add_action( 'learn_press_before_single_course_summary', '_learn_press_print_notices', 0 );
add_filter( 'the_content', '_learn_press_print_notices', 1000 );

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

function learn_press_parse_request() {
	global $wp;

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

	return trailingslashit( LP_LOG_PATH ) . $handle . '-' . sanitize_file_name( function_exists('wp_hash') ? wp_hash( $handle ) : md5($handle) ) . '.log';
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
	echo '<script type="text/javascript">var LearnPress_Settings = ' . json_encode( $js ) . '</script>';
}

add_action( 'wp_print_scripts', 'learn_press_front_scripts' );

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
		$user = get_user_by( 'id', $user_id );
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
	}
	$args = array_map( '_learn_press_urlencode', $args );
	if ( get_option( 'permalink_structure' ) && learn_press_get_page_id( 'profile' ) ) {
		$url = learn_press_get_page_link( 'profile' ) . join( "/", array_values( $args ) );
	} else {
		$url = add_query_arg( $args, learn_press_get_page_link( 'profile' ) );
	}
	return $url;
}

function _learn_press_urlencode( $string ) {
	return preg_replace( '/\s/', '+', $string );
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

add_filter( 'template_include', 'learn_press_search_template', 69 );

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
	foreach ( $items as $item_id => $item ) {
		$course = learn_press_get_course( $item['course_id'] );
		if ( !$course ) {
			continue;
		}
		if ( $user->has( 'enrolled-course', $course->id ) ) {
			continue;
		}
		$user->enroll( $course->id );
	}
}

add_action( 'learn_press_order_status_completed', 'learn_press_auto_enroll_user_to_courses' );
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
					'desc'    => __( 'Check this option to enable user can purchase multiple course at one time', 'learnpress' ),
					'id'      => $class->get_field_name( 'enable_cart' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'Add to cart redirect', 'learnpress' ),
					'desc'    => __( 'Redirect to checkout immediately after add course to cart', 'learnpress' ),
					'id'      => $class->get_field_name( 'redirect_after_add' ),
					'default' => 'yes',
					'type'    => 'checkbox'
				),
				array(
					'title'   => __( 'AJAX add to cart', 'learnpress' ),
					'desc'    => __( 'Using AJAX to add course to the cart', 'learnpress' ),
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
		update_option( 'learn_press_no_checkout_free_course', 'yes' );
	}
}

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
