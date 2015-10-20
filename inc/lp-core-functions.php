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
		wp_die( __( 'Sorry! You don\'t have permission to do this action', 'learn_press' ), 403 );
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
		if ( in_array( $post_type, array( LP()->course_post_type, LP()->lesson_post_type, LP()->quiz_post_type, LP()->question_post_type, LP()->assignment_post_type ) ) ) {
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
function learn_press_edit_registration() {
	?>
	<p>
		<label for="become_teacher">
			<input type="checkbox" name="become_teacher" id="become_teacher">
			<?php _e( 'Want to be an instructor?', 'learn_press' ) ?>
		</label>
	</p>

	<?php
}

// process instructor registration button
function learn_press_registration_save( $user_id ) {
	if ( isset( $_POST['become_teacher'] ) ) {
		//update_user_meta( $user_id, '_lpr_be_teacher', $_POST['become_teacher'] );
		$new_user = new WP_User( $user_id );
		$new_user->set_role( 'lpr_teacher' );
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
	if ( is_null( $user_id ) ) {
		$course  = get_post( $course_id );
		$user_id = $course->post_author;
	}
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
		array( 'edit-lpr_course', 'edit-course_category', 'edit-course_tag', LP()->course_post_type )
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
	if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == LP()->course_post_type ) {
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
				wp_kses(
					__( '<strong>LearnPress Profile is almost ready</strong>. You must <a href="%s">update your permalink structure</a> to something other than the default for it to work.', 'learn_press' ),
					array(
						'a'      => array(
							'href' => array()
						),
						'strong' => array()
					)
				),
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
	if ( $old_status == 'pending' && $new_status == 'publish' && $post->post_type == LP()->course_post_type ) {
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
		'post_type'           => LP()->course_post_type,
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
		'post_type'           => LP()->course_post_type,
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
		'post_type'           => LP()->course_post_type,
		'author'              => $user_id,
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
	if ( $curriculum ) {
		foreach ( $curriculum as $lesson_quiz_s ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
				foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
					if ( get_post_type( $lesson_quiz ) == LP()->quiz_post_type ) {
						$quizzes[] = $lesson_quiz;
					}
				}
			}
		}
	}

	return $quizzes;
}

/**
 * Get all lessons in a course
 *
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_lessons( $course_id ) {
	$lessons    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) {
		foreach ( $curriculum as $lesson_quiz_s ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
				foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
					if ( get_post_type( $lesson_quiz ) == LP()->lesson_post_type ) {
						$lessons[] = $lesson_quiz;
					}
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
	} elseif ( ( $page_id = learn_press_get_page_id( 'become_teacher_form' ) ) && is_page( $page_id ) ) {
		global $post;

		$post->post_content = '[learn_press_become_teacher_form]';
	} else {
		if ( is_post_type_archive( LP()->course_post_type ) || ( ( $page_id = learn_press_get_page_id( 'courses' ) ) && is_page( $page_id ) ) || ( is_tax( 'course_category' ) ) ) {
			$file   = 'archive-course.php';
			$find[] = $file;
			$find[] = 'learnpress/' . $file;
		} else {
			if ( get_post_type() == LP()->course_post_type ) {
				$file   = 'single-course.php';
				$find[] = $file;
				$find[] = 'learnpress/' . $file;
			} else {
				if ( get_post_type() == LP()->quiz_post_type ) {
					$file   = 'single-quiz.php';
					$find[] = $file;
					$find[] = 'learnpress/' . $file;
				} else {
					if ( get_post_type() == LP()->assignment_post_type ) {
						$file   = 'single-assignment.php';
						$find[] = $file;
						$find[] = 'learnpress/' . $file;
					}
				}
			}
		}
	}

	if ( $file ) {
		$template = locate_template( array_unique( $find ) );
		if ( !$template ) {
			$template = learn_press_plugin_path( 'templates/' ) . $file;
		}
	}

	return $template;
}

add_filter( 'pre_get_posts', 'learn_press_pre_get_post', 0 );
function learn_press_pre_get_post( $q ) {
	if ( is_admin() ) {
		return $q;
	}
	if ( $q->is_main_query() && $q->is_page() && ( $q->queried_object && $q->queried_object->ID == ( $page_id = learn_press_get_page_id( 'courses' ) ) && $page_id ) ) {
		$q->set( 'post_type', LP()->course_post_type );
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

/*
function learn_press_get_web_hooks() {
	$web_hooks = empty( $GLOBALS['learn_press']['web_hooks'] ) ? array() : (array) $GLOBALS['learn_press']['web_hooks'];

	return apply_filters( 'learn_press_get_web_hooks', $web_hooks );
}


function learn_press_register_web_hook( $key, $param ) {
	if ( !$key ) {
		return;
	}
	if ( empty ( $GLOBALS['learn_press']['web_hooks'] ) ) {
		$GLOBALS['learn_press']['web_hooks'] = array();
	}
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
		wp_die( __( 'LearnPress webhook process Complete', 'learn_press' ), __( 'iThemes Exchange Webhook Process Complete', 'learn_press' ), array( 'response' => 200 ) );
	}
}

add_action( 'wp', 'learn_press_process_web_hooks' );
*/

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
	/*array(
		'id'     => '{ID}',
		'before' => array(
			'add_new_page' => __( '[ Add a new page ]', 'learn_press' )
		),
		'class'  => 'lp-dropdown-pages',
		'echo'   => false
	);*/
	$id           = null;
	$class        = null;
	$css          = null;
	$before       = array(
		'add_new_page' => __( '[ Add a new page ]', 'learn_press' )
	);
	$after        = null;
	$echo         = true;
	$allow_create = true;
	is_array( $args ) && extract( $args );

	$args    = array(
		'name'             => $name,
		'id'               => $id,
		'sort_column'      => 'menu_order',
		'sort_order'       => 'ASC',
		'show_option_none' => __( 'Select Page', 'learn_press' ),
		'class'            => $class,
		'echo'             => false,
		'selected'         => $selected,
		'allow_create'     => true
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
	if ( $allow_create ) {
		ob_start(); ?>
		<p class="lpr-quick-add-page-inline hide-if-js">
			<input type="text" />
			<button class="button" type="button"><?php _e( 'Ok', 'learn_press' ); ?></button>
			<a href=""><?php _e( 'Cancel', 'learn_press' ); ?></a>
		</p>
		<p class="lpr-quick-actions-inline<?php echo $selected ? '' : ' hide-if-js'; ?>">
			<a href="<?php echo get_edit_post_link( $selected ); ?>" target="_blank"><?php _e( 'Edit Page', 'learn_press' ); ?></a>
			<a href="<?php echo get_permalink( $selected ); ?>" target="_blank"><?php _e( 'View Page', 'learn_press' ); ?></a>
		</p>
		<?php $output .= ob_get_clean();
	}
	if ( $echo ) {
		echo $output;
	}

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
		$result .= $weeks . ' ' . __( 'week(s)', 'learn_press' ) . ' ';
	}

	if ( $days ) {
		$result .= $days . ' ' . __( 'day(s)', 'learn_press' ) . ' ';
	}

	if ( !$weeks ) {
		if ( $hours ) {
			$result .= $hours . ' ' . __( 'hour(s)', 'learn_press' ) . ' ';

		}
		if ( $mins ) {
			$result .= $mins . ' ' . __( 'min(s)', 'learn_press' ) . ' ';
		}
	}
	$result = rtrim( $result );

	return $result;
}

add_action( 'learn_press_frontend_action_retake_course', array( 'LP_AJAX', 'retake_course' ) );

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
	if ( !class_exists( 'LP_Admin_Settings' ) ) return;
	$settings = LP_Admin_Settings::instance( 'general' );
	if ( $settings->get( 'instructor_registration' ) ) {
		$be_teacher           = array();
		$be_teacher['id']     = 'be_teacher';
		$be_teacher['parent'] = 'user-actions';
		$be_teacher['title']  = __( 'Become An Instructor', 'learn_press' );
		$be_teacher['href']   = '#';
		$wp_admin_bar->add_menu( $be_teacher );
	}
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
	// courses/lesson-name
	add_rewrite_tag( '%lesson%', '([^&]+)' );
	add_rewrite_tag( '%section%', '([^&]+)' );

	// quizzes/question-name
	add_rewrite_tag( '%question%', '([^&]+)' );
}

function learn_press_custom_rewrite_rule() {

	// lesson
	$post_types = get_post_types( array( 'name' => LP()->course_post_type ), 'objects' );
	$slug       = $post_types[LP()->course_post_type]->rewrite['slug'];
	add_rewrite_rule( '^' . $slug . '/([^/]*)/([^/]*)/?([^/]*)?/?', 'index.php?lpr_course=$matches[1]&lesson=$matches[2]&section=$matches[3]', 'top' );

	// question
	$post_types = get_post_types( array( 'name' => LP()->quiz_post_type ), 'objects' );
	$slug       = $post_types[LP()->quiz_post_type]->rewrite['slug'];
	//echo '^' . $slug . '/([^/]*)/([^/]*)/?';
	add_rewrite_rule( '^' . $slug . '/([^/]*)/([^/]*)/?', 'index.php?lpr_quiz=$matches[1]&question=$matches[2]', 'top' );

}

add_action( 'init', 'learn_press_custom_rewrite_tag', 1000, 0 );
add_action( 'init', 'learn_press_custom_rewrite_rule', 1000, 0 );

function learn_press_parse_query_vars_to_request() {
	global $wp_query;
	if ( empty( $wp_query->query_vars['post_type'] ) ) {
		return;
	}
	if ( $wp_query->query_vars['post_type'] != LP()->course_post_type ) {
		//return;
	}
	if ( !empty( $wp_query->query_vars['lesson'] ) ) {
		$post  = null;
		$posts = get_posts(
			array(
				'name'        => $wp_query->query_vars['lesson'],
				'post_type'   => LP()->lesson_post_type,
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
	if ( !empty( $wp_query->query_vars['question'] ) ) {
		$post  = null;
		$posts = get_posts(
			array(
				'name'        => $wp_query->query_vars['question'],
				'post_type'   => LP()->question_post_type,
				'post_status' => 'publish',
				'numberposts' => 1
			)
		);

		if ( $posts ) {
			$post = $posts[0];
		}
		if ( $post ) {
			$_REQUEST['question'] = $post->ID;
			$_GET['question']     = $post->ID;
			$_POST['question']    = $post->ID;
		} else {
			global $wp_query;
			$wp_query->is_404 = true;
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


function learn_press_text_image( $text = null, $args = array() ) {
	$width      = 200;
	$height     = 150;
	$font_size  = 1;
	$background = 'FFFFFF';
	$color      = '000000';
	$padding    = 20;
	extract( $args );

	// Output to browser
	if ( empty( $_REQUEST['debug'] ) ) {
		header( 'Content-Type: image/png' );
	}
	/*
    $uniqid = md5( serialize( array( 'width' => $width, 'height' => $height, 'text' => $text, 'background' => $background, 'color' => $color ) ) );
    @mkdir( LP_PLUGIN_PATH . '/cache' );
    $cache = LP_PLUGIN_PATH . '/cache/' . $uniqid . '.cache';
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
	$font = LP_PLUGIN_PATH . '/assets/fonts/Sumana-Regular.ttf';
	$x    = $width;
	$loop = 0;
	do {
		// First we create our bounding box for the first text
		$bbox = imagettfbbox( $font_size, 0, $font, $text );
		// This is our cordinates for X and Y
		$x = $bbox[0] + ( imagesx( $im ) / 2 ) - ( $bbox[4] / 2 );
		$y = $bbox[1] + ( imagesy( $im ) / 2 ) - ( $bbox[5] / 2 );
		$font_size ++;
		if ( $loop ++ > 100 ) {
			break;
		}
	} while ( $x > $padding );
	// Write it
	imagettftext( $im, $font_size, 0, $x - 5, $y, $color, $font, $text );
	imagepng( $im );
	//readfile( $cache );
	imagedestroy( $im );
}

function become_a_teacher_handler() {
	$name  = !empty( $_POST['bat_name'] ) ? $_POST['bat_name'] : null;
	$email = !empty( $_POST['bat_email'] ) ? $_POST['bat_email'] : null;
	$phone = !empty( $_POST['bat_phone'] ) ? $_POST['bat_phone'] : null;

	$response = array(
		'error' => array()
	);

	if ( !$name ) {
		$response['error'][] = __( 'Please enter your name', 'learn_press' );
	}

	if ( !$email ) {
		$response['error'][] = __( 'Please enter your email address', 'learn_press' );
	}

	if ( !$phone ) {
		//$response['error'][] = __( 'Please enter your phone number', 'learn_press' );
	}
	global $current_user;
	get_currentuserinfo();

	$to_email        = array( get_option( 'admin_email' ) );
	$message_headers = '';
	$subject         = 'Please moderate';
	$notify_message  = sprintf( __( 'The user <a href="%s">%s</a> want to be a teacher.', 'learn_press' ), admin_url( 'user-edit.php?user_id=' . $current_user->ID ), $current_user->data->user_login ) . "\r\n";

	$notify_message .= sprintf( __( 'Name: %s', 'learn_press' ), $name ) . "\r\n";
	$notify_message .= sprintf( __( 'Email: %s', 'learn_press' ), $email ) . "\r\n";
	$notify_message .= sprintf( __( 'Phone: %s', 'learn_press' ), $phone ) . "\r\n";
	$notify_message .= wp_specialchars_decode( sprintf( __( 'Accept: %s', 'learn_press' ), admin_url( 'user-edit.php?user_id=' . $current_user->ID ) . '&action=accept-to-be-teacher' ) ) . "\r\n";

	$args = array(
		$to_email,
		( $subject ),
		$notify_message,
		$message_headers
	);

	$return             = @call_user_func_array( 'wp_mail', $args );// $email, wp_specialchars_decode( $subject ), $notify_message, $message_headers );
	$response['return'] = $return;
	//$response['args']   = $args;
	// $response['user']   = $current_user;
	learn_press_send_json( $response );
	die();
}

add_action( 'learn_press_frontend_action_become_a_teacher', 'become_a_teacher_handler' );


function wpdev_141551_translate_user_roles( $translations, $text, $context, $domain ) {

	$plugin_domain = 'learn_press';

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

add_filter( 'gettext_with_context', 'wpdev_141551_translate_user_roles', 10, 4 );

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
		$q->set( 'post_type', LP()->course_post_type );
		add_filter( 'posts_where', 'learn_press_posts_where_statement_search' );
	}
}

add_filter( 'pre_get_posts', 'learn_press_filter_search' );

/**
 * Convert an object|array to json format and send it to the browser
 *
 * @param $response
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
	// We only want to affect the main query
	if ( !$q->is_main_query() ) {
		return;
	}

	// Fix for verbose page rules
	if ( $GLOBALS['wp_rewrite']->use_verbose_page_rules && isset( $q->queried_object_id ) && $q->queried_object_id === learn_press_get_page_id( 'courses' ) ) {
		$q->set( 'post_type', LP()->course_post_type );
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
			$q->set( 'post_type', LP()->course_post_type );
		}
	}

	if ( $q->is_page() && 'page' == get_option( 'show_on_front' ) && $q->get( 'page_id' ) == learn_press_get_page_id( 'courses' ) && learn_press_get_page_id( 'courses' ) ) {
		echo learn_press_get_page_id( 'courses' ), ',', $q->get( 'page_id' );
		$q->set( 'post_type', LP()->course_post_type );
		$q->set( 'page_id', '' );
		if ( isset( $q->query['paged'] ) ) {
			$q->set( 'paged', $q->query['paged'] );
		}

		global $wp_post_types;

		$course_page = get_post( learn_press_get_page_id( 'courses' ) );

		$wp_post_types[LP()->course_post_type]->ID         = $course_page->ID;
		$wp_post_types[LP()->course_post_type]->post_title = $course_page->post_title;
		$wp_post_types[LP()->course_post_type]->post_name  = $course_page->post_name;
		$wp_post_types[LP()->course_post_type]->post_type  = $course_page->post_type;
		$wp_post_types[LP()->course_post_type]->ancestors  = get_ancestors( $course_page->ID, $course_page->post_type );

		$q->is_singular          = false;
		$q->is_post_type_archive = true;
		$q->is_archive           = true;
		$q->is_page              = true;

	} elseif ( !$q->is_post_type_archive( LP()->course_post_type ) && !$q->is_tax( get_object_taxonomies( LP()->course_post_type ) ) ) {
		return;
	}
}

add_action( 'pre_get_posts', 'learn_press_pre_get_posts' );

function learn_press_init() {
	if ( class_exists( 'LP_Settings' ) ) {
		$settings = LP_Settings::instance( 'general' );
		if ( $settings->get( 'instructor_registration' ) ) {
			add_action( 'register_form', 'learn_press_edit_registration' );
			add_action( 'user_register', 'learn_press_registration_save', 10, 1 );
		}
	}

}

add_action( 'init', 'learn_press_init' );

/**
 * Gets all statuses that order supported
 *
 * @return array
 */
function learn_press_get_order_statuses() {
	$order_statuses = array(
		'lp-pending'    => _x( 'Pending Payment', 'Order status', 'learn_press' ),
		'lp-processing' => _x( 'Processing', 'Order status', 'learn_press' ),
		'lp-completed'  => _x( 'Completed', 'Order status', 'learn_press' ),
	);
	return apply_filters( 'learn_press_order_statuses', $order_statuses );
}

function is_learnpress() {
	return apply_filters( 'is_learnpress', ( is_course_archive() || is_course_taxonomy() || is_course() ) ? true : false );
}

if ( !function_exists( 'is_course_archive' ) ) {

	/**
	 * Returns true when viewing the course type archive.
	 *
	 * @return bool
	 */
	function is_course_archive() {
		return ( is_post_type_archive( LP()->course_post_type ) || ( learn_press_get_page_id( 'course' ) && is_page( learn_press_get_page_id( 'course' ) ) ) ) ? true : false;
	}
}

if ( !function_exists( 'is_course_taxonomy' ) ) {

	/**
	 * Returns true when viewing a course taxonomy archive.
	 *
	 * @return bool
	 */
	function is_course_taxonomy() {
		return is_tax( get_object_taxonomies( LP()->course_post_type ) );
	}
}

if ( !function_exists( 'is_course_category' ) ) {

	/**
	 * Returns true when viewing a course category.
	 *
	 * @param  string
	 *
	 * @return bool
	 */
	function is_course_category( $term = '' ) {
		return is_tax( 'course_category', $term );
	}
}

if ( !function_exists( 'is_course_tag' ) ) {

	/**
	 * Returns true when viewing a course tag.
	 *
	 * @param  string
	 *
	 * @return bool
	 */
	function is_course_tag( $term = '' ) {
		return is_tax( 'course_tag', $term );
	}
}

if ( !function_exists( 'is_course' ) ) {

	/**
	 * Returns true when viewing a single course.
	 *
	 * @return bool
	 */
	function is_course() {
		return is_singular( array( LP()->course_post_type ) );
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
 * @param        $message
 * @param string $type
 */
function learn_press_add_notice( $message, $type = 'notice' ) {
	if ( empty( $notices = LP_Session::get( 'notices' ) ) ) {
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
			LP_Session::set( 'notices', null );
		}
	}
}

function learn_press_debug( $a ) {
	echo '<pre>';
	print_r( $a );
	echo '</pre>';
}