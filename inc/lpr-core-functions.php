<?php
/**
 * LearnPress Core Functions
 *
 * Common functions for both front-end and back-end
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Get current IP of the user
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

add_action( 'wp_ajax_load_curriculum_template', 'load_curriculum_template' );
function load_curriculum_template() {
	global $post;
	$user_id = get_current_user_id();

	if ( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], 'user' . $user_id ) ) {
		$id = intval( $_POST['id'] );

		if ( get_post_type( $id ) != LPR_COURSE_CPT ) {
			echo __( 'Invalid ID', 'learn_press' );
			die();
		}
		load_template( LPR_PLUGIN_URL . '/templates/single-course-feature.php' );
	} else {
		echo __( 'Unable to take the course', 'learn_press' );
	}

	die();
}

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
	 */
	function learn_press_course_paging_nav() {
		if ( $GLOBALS['wp_query']->max_num_pages < 2 ) {
			return;
		}
		$paged        = get_query_var( 'paged' ) ? intval( get_query_var( 'paged' ) ) : 1;
		$pagenum_link = html_entity_decode( get_pagenum_link() );

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
			'total'     => $GLOBALS['wp_query']->max_num_pages,
			'current'   => $paged,
			'mid_size'  => 1,
			'add_args'  => array_map( 'urlencode', $query_args ),
			'prev_text' => __( '<', 'learn_press' ),
			'next_text' => __( '>', 'learn_press' ),
			'type'      => 'list'
		) );

		if ( $links ) :
			?>
			<div class="navigation pagination">
				<?php echo $links; ?>
			</div>
			<!-- .pagination -->
		<?php
		endif;
	}

endif;

/**
 * Function Insert or update Order
 *
 * @param $order_data array post data structure
 * @param $order_meta array post meta.
 */

function learn_press_update_order( $order_data, $order_meta, $purchased_items ) {
	$date                = current_time( 'mysql' );
	$order_data_defaults = array(
		'ID'          => 0, //Order ID
		'post_author' => '0', //Buyer ID
		'post_parent' => '0', //Course ID
		'post_date'   => $date, //Course ID
		'post_type'   => 'lpr_order',
		'post_status' => 'publish',
		'ping_status' => 'closed',
		'post_title'  => __( 'Order on ', 'learn_press' ) . ' ' . date( "l jS F Y h:i:s A", strtotime( $date ) )
	);
	$order_data_defaults = apply_filters( 'learn_press_update_order_data', $order_data_defaults );
	$order_data          = wp_parse_args( $order_data, $order_data_defaults );
	$purchased_items     = wp_parse_args( $purchased_items,
		apply_filters( 'learn_press_update_order_purchased_items',
			array(
				array(
					'course_id' => 0 //Total price
				)
			)
		)
	);
	$order_meta          = wp_parse_args( $order_meta,
		apply_filters( 'learn_press_update_order_meta',
			array(
				'lpr_cost'        => 0, //Total price
				'lpr_methods'     => 'paypal', //Payment methods
				'lpr_items'       => $purchased_items,
				'lpr_status'      => '0',
				'lpr_information' => ''
			)
		)
	);
	if ( $order_data['ID'] ) {
		wp_update_post( $order_data );
		$order_id = $order_data['ID'];
	} else {
		$order_id = wp_insert_post( $order_data );
	}
	foreach ( $order_meta as $meta_key => $meta_value ) {
		update_post_meta( $order_id, $meta_key, $meta_value );
	}
}

/**
 * Update Order status
 *
 * @param $order_id int Order ID
 * @param $status   int Order status. -1: cancel,0:on-hold 1: pending, 2: completed, -2: refund
 *
 * @return bool
 */
function learn_press_update_order_status( $order_id, $status = '' ) {
	if ( $status ) {
		wp_update_post(
			array( 'ID' => $order_id, 'post_status' => 'publish' )
		);

		if ( update_post_meta( $order_id, '_learn_press_transaction_status', $status ) ) {
			do_action( 'learn_press_update_order_status', $status, $order_id );
			return true;
		} else {
			return false;
		}
	}
	return false;
}

/**
 * Function get order information
 *
 * @param $oder_id int Order ID
 *
 * @return array
 */
//function learn_press_get_order( $oder_id ) {
//	if ( !$oder_id ) {
//		return false;
//	}
//	$data       = get_post( $oder_id );
//	$order_meta = apply_filters( 'learn_press_get_order_meta',
//		array(
//			'lpr_cost'        => get_post_meta( $oder_id, 'lpr_cost', true ), //Total price
//			'lpr_methods'     => get_post_meta( $oder_id, 'lpr_methods', true ), //Payment methods
//			'lpr_items'       => get_post_meta( $oder_id, 'lpr_items', true ),
//			'lpr_status'      => get_post_meta( $oder_id, 'lpr_status', true ),
//			'lpr_information' => get_post_meta( $oder_id, 'lpr_information', true ),
//		)
//	);
//	$data->meta = $order_meta;
//
//	return $data;
//}

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

/**
 * Processing co-teacher
 */
add_action( 'admin_head-post.php', 'learn_press_process_teacher' );
function learn_press_process_teacher() {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	global $post;
	$post_id = $post->ID;

	if ( current_user_can( 'lpr_teacher' ) ) {
		if ( $post->post_author == get_current_user_id() ) {
			return;
		}
		$courses = apply_filters( 'learn_press_valid_courses', array() );
		$lessons = apply_filters( 'learn_press_valid_lessons', array() );
		$quizzes = apply_filters( 'learn_press_valid_quizzes', array() );
		if ( in_array( $post_id, $courses ) ) {
			return;
		}
		if ( in_array( $post_id, $lessons ) ) {
			return;
		}
		if ( in_array( $post_id, $quizzes ) ) {
			return;
		}
		wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
	}
}

add_action( 'pre_get_posts', 'learn_press_pre_get_items', 10 );
function learn_press_pre_get_items( $query ) {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	global $post_type;
	global $pagenow;
	global $wpdb;
	if ( current_user_can( 'lpr_teacher' ) && is_admin() && $pagenow == 'edit.php' ) {
		if ( in_array( $post_type, array( 'lpr_course', 'lpr_lesson', 'lpr_quiz', 'lpr_question' ) ) ) {
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

/**
 * @param $views
 *
 * @return mixed
 */
function learn_press_restrict_items( $views ) {
	$post_type = get_query_var( 'post_type' );
	$new_views = array(
		'all'     => __( 'All', 'learn_press' ),
		'publish' => __( 'Published', 'learn_press' ),
		'private' => __( 'Private', 'learn_press' ),
		'pending' => __( 'Pending Review', 'learn_press' ),
		'future'  => __( 'Scheduled', 'learn_press' ),
		'draft'   => __( 'Draft', 'learn_press' ),
		'trash'   => __( 'Trash', 'learn_press' ),
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
				'<a href="%s"' . $class . '>' . __( $name, 'learn_press' ) . ' <span class="count">(%d)</span></a>',
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
add_action( 'register_form', 'learn_press_edit_registration' );
function learn_press_edit_registration() {
	?>
	<p>
		<label for="become_teacher"><?php _e( 'Want to be an instructor?', 'learn_press' ) ?>
			<input type="checkbox" name="become_teacher" id="become_teacher">
		</label>
	</p>

<?php
}

// process instructor registration button
add_action( 'user_register', 'learn_press_registration_save', 10, 1 );
function learn_press_registration_save( $user_id ) {
	if ( isset( $_POST['become_teacher'] ) ) {
		//update_user_meta( $user_id, '_lpr_be_teacher', $_POST['become_teacher'] );
		$new_user = new WP_User( $user_id );
		$new_user->set_role( 'lpr_teacher' );
	}
}

// remove author metabox from teachers in editor screen.
add_action( 'admin_head-post-new.php', 'learn_press_remove_author_box' );
add_action( 'admin_head-post.php', 'learn_press_remove_author_box' );
function learn_press_remove_author_box() {
	if ( current_user_can( 'lpr_teacher' ) ) {
		remove_meta_box( 'authordiv', 'lpr_course', 'normal' );
		remove_meta_box( 'authordiv', 'lpr_lesson', 'normal' );
		remove_meta_box( 'authordiv', 'lpr_quiz', 'normal' );
		remove_meta_box( 'authordiv', 'lpr_question', 'normal' );
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
	add_filter( 'learn_press_instructor_profile_link', 'learn_press_get_profile_link', 10, 2 );
}
function learn_press_get_profile_link( $link, $course_id ) {
	$course     = get_post( $course_id );
	$user_id    = $course->post_author;
	$user_login = get_the_author_meta( 'user_login', $user_id );
	$link       = home_url( "/profile/$user_login" );
	return $link;
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
 * Notify an administrator with pending courses
 */
add_action( 'admin_menu', 'learn_press_notify_new_course' );

function learn_press_notify_new_course() {

	global $menu;
	$current_user = wp_get_current_user();
	if ( !in_array( 'administrator', $current_user->roles ) ) {
		return;
	}
	$count_courses = wp_count_posts( 'lpr_course' );
	$awaiting_mod  = $count_courses->pending;
	$menu[3][0] .= " <span class='awaiting-mod count-$awaiting_mod'><span class='pending-count'>" . number_format_i18n( $awaiting_mod ) . "</span></span>";

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
	if ( !is_admin() )
		return;
	$admin_tabs = apply_filters(
		'learn_press_admin_tabs_info',
		array(

			10 => array(
				"link" => "edit.php?post_type=lpr_course",
				"name" => __( "Courses", "learn_press" ),
				"id"   => "edit-lpr_course",
			),

			20 => array(
				"link" => "edit-tags.php?taxonomy=course_category&post_type=lpr_course",
				"name" => __( "Categories", "learn_press" ),
				"id"   => "edit-course_category",
			),
			30 => array(
				"link" => "edit-tags.php?taxonomy=course_tag&post_type=lpr_course",
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
		array( 'edit-lpr_course', 'edit-course_category', 'edit-course_tag', 'lpr_course' )
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
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $admin_tabs_on_page[$current_page_id] as $admin_tab_id ) {

			$class = ( $admin_tabs[$admin_tab_id]["id"] == $current_page_id ) ? "nav-tab nav-tab-active" : "nav-tab";
			echo '<a href="' . admin_url( $admin_tabs[$admin_tab_id]["link"] ) . '" class="' . $class . ' nav-tab-' . $admin_tabs[$admin_tab_id]["id"] . '">' . $admin_tabs[$admin_tab_id]["name"] . '</a>';
		}
		echo '</h2>';
	}
}

add_action( 'admin_footer', 'learn_press_show_menu' );
function learn_press_show_menu() {
	if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'lpr_course' ) {
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


/*
 * Rewrite url
 */
add_action( 'init', 'learn_press_add_rewrite_tag' );
function learn_press_add_rewrite_tag() {

	add_rewrite_tag( '%user%', '([^/]*)' );
	flush_rewrite_rules();
}


add_filter( 'page_rewrite_rules', 'learn_press_add_rewrite_rule' );
function learn_press_add_rewrite_rule( $rewrite_rules ) {
	// The most generic page rewrite rule is at end of the array
	// We place our rule one before that
	end( $rewrite_rules );
	$last_pattern     = key( $rewrite_rules );
	$last_replacement = array_pop( $rewrite_rules );
	$page_id          = learn_press_get_profile_page_id();
	$rewrite_rules += array(
		'^profile/([^/]*)' => 'index.php?page_id=' . $page_id . '&user=$matches[1]',
		$last_pattern      => $last_replacement
	);
	return $rewrite_rules;
}

/*
 * Editing permalink notification when using LearnPress profile
 */
add_action( 'admin_notices', 'learn_press_edit_permalink' );
add_action( 'network_admin_notices', 'learn_press_edit_permalink' );
function learn_press_edit_permalink() {

	// Setting up notification
	$check = get_option( '_lpr_ignore_setting_up' );
	if ( !$check && current_user_can( 'manage_options' ) ) {
		echo '<div id="lpr-setting-up" class="updated"><p>';
		echo sprintf(
			__( '<strong>LearnPress is almost ready</strong>. <a class="lpr-set-up" href="%s">Setting up</a> something right now is a good idea. That\'s better than you <a class="lpr-ignore lpr-set-up">ignore</a> the message.', 'learn_press' ),
			esc_url( add_query_arg( array( 'page' => 'learn_press_settings' ), admin_url( 'options-general.php' ) ) )
		);
		echo '</p></div>';
	}

	// Add notice if no rewrite rules are enabled
	global $wp_rewrite;
	if ( learn_press_has_profile_method() ) {
		if ( empty( $wp_rewrite->permalink_structure ) ) {
			echo '<div class="fade error"><p>';
			echo sprintf(
				__( '<strong>LearnPress Profile is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'learn_press' ),
				admin_url( 'options-permalink.php' )
			);
			echo '</p></div>';
		}
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

	$email_settings = LPR_Settings::instance( 'emails' );// get_option( '_lpr_settings_emails' );


	if ( !$email_settings->get( $action . '.enable' ) ) {
		return "The action {$action} doesnt support";
	}
	$user = get_user_by( 'email', $to );
	if ( in_array( 'administrator', $user->roles ) ) {
		//return;
	}
	// Set default template vars.
	$vars['site_link'] = apply_filters( 'learn_press_site_url', get_home_url() );

	// Send email.
	$email = new LPR_Email();
	$email->set_action( $action );
	$email->parse_email( $vars );
	$email->add_recipient( $to );

	return $email->send();
}

/*
 * Send email notification when a course be published
 */
function learn_press_publish_course( $new_status, $old_status, $post ) {
	if ( $old_status == 'pending' && $new_status == 'publish' && $post->post_type == 'lpr_course' ) {
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
	$pid = get_user_meta( $user_id, '_lpr_user_course', true );
	if ( !$pid ) {
		$pid = array( 0 );
	}
	$arr_query = array(
		'post_type'           => 'lpr_course',
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
function learn_press_get_passed_courses( $user_id ) {
	$pid = get_user_meta( $user_id, '_lpr_course_finished', true );
	if ( !$pid ) {
		$pid = array( 0 );
	}
	$arr_query = array(
		'post_type'           => 'lpr_course',
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
		'post_type'           => 'lpr_course',
		'post_author'         => $user_id,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'posts_per_page'      => - 1
	);
	$my_query  = new WP_Query( $arr_query );
	return $my_query;
}

/**
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_quizzes( $course_id ) {
	$quizzes    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) foreach ( $curriculum as $lesson_quiz_s ) {
		if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
			foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
				if ( get_post_type( $lesson_quiz ) == 'lpr_quiz' ) {
					$quizzes[] = $lesson_quiz;
				}
			}
		}
	}
	return $quizzes;
}

/**
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_lessons( $course_id ) {
	$lessons    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) foreach ( $curriculum as $lesson_quiz_s ) {
		if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
			foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
				if ( get_post_type( $lesson_quiz ) == 'lpr_lesson' ) {
					$lessons[] = $lesson_quiz;
				}
			}
		}
	}
	return $lessons;
}

add_filter( 'template_include', 'learn_press_template_loader' );
function learn_press_template_loader( $template ) {

	$file = '';
	if ( ( $page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && is_page( $page_id ) ) {
		if ( !learn_press_user_can_view_order( !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : 0 ) ) {
			learn_press_404_page();
		}
		global $post;
		$post->post_content = '[learn_press_confirm_order]';
	} else if ( is_post_type_archive( 'lpr_course' ) || ( ( $page_id = learn_press_get_page_id( 'courses' ) ) && is_page( $page_id ) ) || ( is_tax('course_category')) ) {
		$file   = 'archive-course.php';
		$find[] = $file;
		//$find[] = learn_press_plugin_path( 'templates/' ) . $file;
        //$find[] = learn_press_locate_template( 'archive-course.php' );
        $find[] = 'learnpress/' . $file;
	} else if ( get_post_type() == 'lpr_course' ) {
        $file   = 'single-course.php';
        $find[] = $file;
        //$find[] = learn_press_plugin_path( 'templates/' ) . $file;
        $find[] = 'learnpress/' . $file;
    } else if ( get_post_type() == 'lpr_quiz' ) {
        $file   = 'single-quiz.php';
        $find[] = $file;
        //$find[] = learn_press_plugin_path( 'templates/' ) . $file;
        $find[] = 'learnpress/' . $file;
    }

	if ( $file ) {
        //print_r($find);
		$template = locate_template( array_unique( $find ) );
		if ( !$template ) {
			$template = learn_press_plugin_path( 'templates/' ) . $file;
		}
	}
	return $template;
}

add_filter( 'pre_get_posts', 'learn_press_pre_get_post' );
function learn_press_pre_get_post( $q ) {
	if ( is_admin() ) return $q;
	global $post_type;

	if ( $q->is_page() && ( $q->get( 'page_id' ) == ( $page_id = learn_press_get_page_id( 'courses' ) ) && $page_id ) ) {


		$q->set( 'post_type', 'lpr_course' );
		$q->set( 'page_id', '' );
		if ( isset( $q->query['paged'] ) ) {
			$q->set( 'paged', $q->query['paged'] );
		}

		global $wp_post_types;

		$courses_page = get_post( $page_id );

		$wp_post_types['lpr_course']->ID         = $courses_page->ID;
		$wp_post_types['lpr_course']->post_title = $courses_page->post_title;
		$wp_post_types['lpr_course']->post_name  = $courses_page->post_name;
		$wp_post_types['lpr_course']->post_type  = $courses_page->post_type;
		$wp_post_types['lpr_course']->ancestors  = get_ancestors( $courses_page->ID, $courses_page->post_type );

		$q->is_singular          = false;
		$q->is_post_type_archive = true;
		$q->is_archive           = true;
		$q->is_page              = true;
	}
	return $q;
}

function learn_press_bbpress_is_active() {
	if ( !function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	return class_exists( 'bbPress' ) && is_plugin_active( 'bbpress/bbpress.php' );
}

function learn_press_buddypress_is_active() {
	if ( !function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	return class_exists( 'BuddyPress' ) && is_plugin_active( 'buddypress/bp-loader.php' );
}

function learn_press_get_web_hooks() {
	$web_hooks = empty( $GLOBALS['learn_press']['web_hooks'] ) ? array() : (array) $GLOBALS['learn_press']['web_hooks'];
	return apply_filters( 'learn_press_get_web_hooks', $web_hooks );
}


function learn_press_register_web_hook( $key, $param ) {
	if ( !$key ) return;
	if ( empty ( $GLOBALS['learn_press']['web_hooks'] ) ) $GLOBALS['learn_press']['web_hooks'] = array();
	$GLOBALS['learn_press']['web_hooks'][$key] = $param;
	do_action( 'learn_press_register_web_hook', $key, $param );
}

function learn_press_get_web_hook( $key ) {
	$web_hooks = learn_press_get_web_hooks();
	$web_hook  = empty( $web_hooks[$key] ) ? false : $web_hooks[$key];
	return apply_filters( 'learn_press_get_web_hook', $web_hook, $key );
}

function learn_press_process_web_hooks() {
	// Grab registered web_hooks
	$web_hooks           = learn_press_get_web_hooks();
	$web_hooks_processed = false;
	// Loop through them and init callbacks

	foreach ( $web_hooks as $key => $param ) {
		if ( !empty( $_REQUEST[$param] ) ) {
			$web_hooks_processed           = true;
			$request_scheme                = is_ssl() ? 'https://' : 'http://';
			$requested_web_hook_url        = untrailingslashit( $request_scheme . $_SERVER['HTTP_HOST'] ) . $_SERVER['REQUEST_URI']; //REQUEST_URI includes the slash
			$parsed_requested_web_hook_url = parse_url( $requested_web_hook_url );
			$required_web_hook_url         = add_query_arg( $param, '1', trailingslashit( get_site_url() ) ); //add the slash to make sure we match
			$parsed_required_web_hook_url  = parse_url( $required_web_hook_url );
			$web_hook_diff                 = array_diff_assoc( $parsed_requested_web_hook_url, $parsed_required_web_hook_url );

			if ( empty( $web_hook_diff ) ) { //No differences in the requested webhook and the required webhook
				do_action( 'learn_press_web_hook_' . $param, $_REQUEST );
			} else {

			}
			break; //we can stop processing here... no need to continue the foreach since we can only handle one webhook at a time
		}
	}
	if ( $web_hooks_processed ) {
		do_action( 'learn_press_web_hooks_processed' );
		wp_die( __( 'LearnPress webhook process Complete', 'it-l10n-ithemes-exchange' ), __( 'iThemes Exchange Webhook Process Complete', 'it-l10n-ithemes-exchange' ), array( 'response' => 200 ) );
	}
}

add_action( 'wp', 'learn_press_process_web_hooks' );


function learn_press_woo_is_active() {
	if ( !function_exists( 'is_plugin_active' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	return class_exists( 'WC_Install' ) && is_plugin_active( 'woocommerce/woocommerce.php' );
}

function learn_press_currency_positions() {
	return apply_filters(
		'learn_press_currency_positions',
		array(
			'left'             => __( 'Left', 'learn_press' ),
			'right'            => __( 'Right', 'learn_press' ),
			'left_with_space'  => __( 'Left with space', 'learn_press' ),
			'right_with_space' => __( 'Right with space', 'learn_press' )

		)
	);
}


/**
 * get the list of currencies with code and name
 *
 * @author  TuNN
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
	return apply_filters( 'learn_press_currency', LPR_Settings::instance( 'general' )->get( 'currency', $currency ) );
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
	$page_id = learn_press_settings( $key );
	return get_permalink( $page_id );
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

function learn_press_pages_dropdown( $name, $selected = false, $args = array() ) {
	$id     = null;
	$class  = null;
	$css    = null;
	$before = null;
	$after  = null;
	$echo   = true;
	is_array( $args ) && extract( $args );

	$args    = array(
		'name'             => $name,
		'id'               => $id,
		'sort_column'      => 'menu_order',
		'sort_order'       => 'ASC',
		'show_option_none' => __( 'Select Page', 'learn_press' ),
		'class'            => $class,
		'echo'             => false,
		'selected'         => $selected
	);
	$output  = wp_dropdown_pages( $args );
	$replace = "";
	if ( $class ) {
		$replace .= ' class="' . $class . '"';
	}
	if ( $css ) {
		$replace .= ' style="' . $css . '"';
	}

	$replace .= " data-placeholder='" . __( 'Select a page&hellip;', 'learn_press' ) . "' id=";
	$output = str_replace( ' id=', $replace, $output );
	if ( $before ) {
		$before_output = array();
		foreach ( $before as $v => $l ) {
			$before_output[] = sprintf( '<option value="%s">%s</option>', $v, $l );
		}
		$before_output = join( "\n", $before_output );
		$output        = preg_replace( '!(<option class=".*" value="[0-9]+".*>.*</option>)!', $before_output . "\n$1", $output, 1 );
	}
	if ( $echo ) echo $output;
	return $output;
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
		$result .= "{$weeks} week(s) ";
	}

	if ( $days ) {
		$result .= "{$days} day(s) ";
	}

	if ( !$weeks ) {
		if ( $hours ) {
			$result .= "{$hours} hour(s) ";
		}
		if ( $mins ) {
			$result .= "{$mins} min(s) ";
		}
	}
	$result = rtrim( $result );
	return $result;
}

add_action( 'learn_press_frontend_action_retake_course', array( 'LPR_AJAX', 'retake_course' ) );

function learn_press_edit_admin_bar() {
	global $wp_admin_bar;
	$current_user = wp_get_current_user();
	if ( learn_press_has_profile_method() ) {
		$course_profile                   = array();
		$course_profile['id']             = 'course_profile';
		$course_profile['parent']         = 'user-actions';
		$course_profile['title']          = __( 'View Course Profile', 'learn_press' );
		$course_profile['href']           = learn_press_get_current_profile_link();
		$course_profile['meta']['target'] = '_blank';
		$wp_admin_bar->add_menu( $course_profile );
	}
	// add `be teacher` link
	if ( in_array( 'lpr_teacher', $current_user->roles ) || in_array( 'administrator', $current_user->roles ) ) {
		return;
	}
	$be_teacher           = array();
	$be_teacher['id']     = 'be_teacher';
	$be_teacher['parent'] = 'user-actions';
	$be_teacher['title']  = __( 'Become An Instructor', 'learn_press' );
	$be_teacher['href']   = '#';
	$wp_admin_bar->add_menu( $be_teacher );
}

add_action( 'admin_bar_menu', 'learn_press_edit_admin_bar' );


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
function learn_press_custom_rewrite_tag() {
	add_rewrite_tag( '%lesson%', '([^&]+)' );
	add_rewrite_tag( '%section%', '([^&]+)' );
}

function learn_press_custom_rewrite_rule() {
	$post_types = get_post_types( array( 'name' => 'lpr_course' ), 'objects' );
	$slug       = $post_types['lpr_course']->rewrite['slug'];

	//add_rewrite_rule('^courses/([^/]*)/([^/]*)/?','index.php','top');
	//add_rewrite_rule('^'.$slug.'/([^/]*)/([^/]*)/?','index.php?lpr_course=$matches[1]&lesson=$matches[2]','top');
	add_rewrite_rule( '^' . $slug . '/([^/]*)/([^/]*)/?([^/]*)?/?', 'index.php?lpr_course=$matches[1]&lesson=$matches[2]&section=$matches[3]', 'top' );

}

add_action( 'init', 'learn_press_custom_rewrite_tag', 1000, 0 );
add_action( 'init', 'learn_press_custom_rewrite_rule', 1000, 0 );

function learn_press_parse_query_vars_to_request() {
	global $wp_query;
	if ( empty( $wp_query->query_vars['post_type'] ) ) return;
	if ( $wp_query->query_vars['post_type'] != 'lpr_course' ) return;
	if ( !empty( $wp_query->query_vars['lesson'] ) ) {
		$post  = null;
		$posts = get_posts(
			array(
				'name'        => $wp_query->query_vars['lesson'],
				'post_type'   => 'lpr_lesson',
				'post_status' => 'publish',
				'numberposts' => 1
			)
		);
		if ( $posts ) {
			$post = $posts[0];
		}
		if ( $post ) {
			$_REQUEST['lesson'] = $post->ID;
			$_GET['lesson']     = $post->ID;
			$_POST['lesson']    = $post->ID;
		}
	}
	if ( !empty( $wp_query->query_vars['section'] ) ) {
		$_REQUEST['section'] = $wp_query->query_vars['section'];
		$_GET['section']     = $wp_query->query_vars['section'];
		$_POST['section']    = $wp_query->query_vars['section'];
	}
}

add_action( 'wp', 'learn_press_parse_query_vars_to_request' );

function learn_press_course_lesson_permalink_friendly( $permalink, $lesson_id, $course_id ) {

	if ( '' != get_option( 'permalink_structure' ) ) {
		if ( preg_match( '!\?lesson=([^\?\&]*)!', $permalink, $matches ) ) {
			$permalink = preg_replace( '!\?lesson=([^\?\&]*)!', basename( get_permalink( $matches[1] ) ), $permalink );
		}
	}
	return $permalink;
}

add_filter( 'learn_press_course_lesson_permalink', 'learn_press_course_lesson_permalink_friendly', 10, 3 );


function learn_press_text_image( $text = null, $args = array() ) {
	$width      = 200;
	$height     = 150;
	$font_size  = 1;
	$background = 'FFFFFF';
	$color      = '000000';
    $padding     = 20;
	extract( $args );

	// Output to browser
	if ( empty( $_REQUEST['debug'] ) ) header( 'Content-Type: image/png' );
	/*
    $uniqid = md5( serialize( array( 'width' => $width, 'height' => $height, 'text' => $text, 'background' => $background, 'color' => $color ) ) );
    @mkdir( LPR_PLUGIN_PATH . '/cache' );
    $cache = LPR_PLUGIN_PATH . '/cache/' . $uniqid . '.cache';
    if( file_exists( $cache ) ){
        readfile( $cache );
        die();
    }*/

	$im = imagecreatetruecolor( $width, $height );

	list( $r, $g, $b ) = sscanf( "#{$background}", "#%02x%02x%02x" );
	$background = imagecolorallocate( $im, $r, $g, $b );

	list( $r, $g, $b ) = sscanf( "#{$color}", "#%02x%02x%02x" );
	$color = imagecolorallocate( $im, $r, $g, $b );

	// Set the background to be white
	imagefilledrectangle( $im, 0, 0, $width, $height, $background );

	// Path to our font file
	$font = LPR_PLUGIN_PATH . '/assets/fonts/Sumana-Regular.ttf';
	$x    = $width;
	$loop = 0;
	do {
		// First we create our bounding box for the first text
		$bbox = imagettfbbox( $font_size, 0, $font, $text );
		// This is our cordinates for X and Y
		$x = $bbox[0] + ( imagesx( $im ) / 2 ) - ( $bbox[4] / 2 );
		$y = $bbox[1] + ( imagesy( $im ) / 2 ) - ( $bbox[5] / 2 );
		$font_size ++;
		if ( $loop ++ > 100 ) break;
	} while ( $x > $padding );
	// Write it
	imagettftext( $im, $font_size, 0, $x - 5, $y, $color, $font, $text );
	imagepng( $im );
	//readfile( $cache );
	imagedestroy( $im );
}
