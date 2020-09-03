<?php
/**
 * Defines the functions which called by hooks
 */

//add_action( 'in_admin_footer', 'learn_press_footer_advertisement', - 10 );
add_action( 'admin_footer', 'learn_press_footer_advertisement', - 10 );

/***************************************/

/**
 * Filter post types the user can access in admin
 *
 * @param $query
 *
 * @deprecated 3.2.7.5
 * @editor     tungnx | comment code
 */
/*function _learn_press_set_user_items( $query ) {
	global $post_type, $pagenow, $wpdb;

	if ( ! function_exists( 'wp_get_current_user' ) ) {
		include( ABSPATH . "wp-includes/pluggable.php" );
	}

	if ( ! did_action( 'plugin_loaded' ) || current_user_can( 'manage_options' ) || ! current_user_can( LP_TEACHER_ROLE ) || ! is_admin() || ( $pagenow != 'edit.php' ) ) {
		return $query;
	}
	if ( ! in_array( $post_type, apply_filters( 'learn-press/filter-user-access-types', array(
		LP_COURSE_CPT,
		LP_LESSON_CPT,
		LP_QUIZ_CPT,
		LP_QUESTION_CPT
	) ) ) ) {
		return;
	}
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
		$query->set( 'post_type', 'no-item-access' );
	} else {
		$query->set( 'post__in', $items );
	}
	add_filter( 'views_edit-' . $post_type . '', '_learn_press_restrict_view_items', 10 );
}*/

//add_action( 'pre_get_posts', '_learn_press_set_user_items', 10 );

/**
 * Restrict user views
 *
 * @param $views
 *
 * @return mixed
 */
function _learn_press_restrict_view_items( $views ) {
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
	$url       = 'edit.php';
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
			$views[ $view ] = sprintf(
				'<a href="%s"' . $class . '>' . __( $name, 'learnpress' ) . ' <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( $query, $url ) ),
				$result->found_posts
			);
		} else {
			unset( $views[ $view ] );
		}
	}
	// remove view 'mine'
	unset( $views['mine'] );

	return $views;
}

/**
 * Update permalink structure for course
 */
function learn_press_update_permalink_structure() {
	global $pagenow;
	if ( $pagenow != 'options-permalink.php' ) {
		return;
	}
	if ( strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' ) {
		return;
	}
	$rewrite_prefix      = '';
	$permalink_structure = ! empty( $_REQUEST['permalink_structure'] ) ? $_REQUEST['permalink_structure'] : '';
	if ( $permalink_structure ) {
		$rewrite_prefix = array();
		$segs           = explode( '/', $permalink_structure );
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

//add_action( 'wp_dashboard_setup', 'learnpress_dashboard_widgets' );

if ( ! function_exists( 'learnpress_dashboard_widgets' ) ) {
	/**
	 * Register dashboard widgets
	 *
	 * LearnPress statistic
	 * Eduma statistic
	 * @since 2.0
	 */
	function learnpress_dashboard_widgets() {
		wp_add_dashboard_widget( 'learn_press_dashboard_widget', __( 'LearnPress Plugin', 'learnpress' ), array(
			'LP_Statistic_Plugin',
			'render'
		) );
		wp_add_dashboard_widget( 'learn_press_dashboard_widget_status', __( 'LearnPress Status', 'learnpress' ), array(
			'LP_Statistic_Status',
			'render'
		) );
	}
}

/**
 * Active Courses menu under LearnPress
 * when user is editing course and course
 * category.
 */
function learn_press_active_course_menu() {

	if ( ! $post_type = LP_Request::get( 'post_type' ) ) {
		return;
	}

	?>
	<script type="text/javascript">
		jQuery(function ($) {
			var $lpMainMenu = $('#toplevel_page_learn_press'),
				href = 'edit.php?post_type=<?php echo esc_js( $_GET['post_type'] ); ?>',
				$current = $('a[href="' + href + '"]', $lpMainMenu);

			if ($current.length) {
				$current.addClass('current');
				$current.parent('li').addClass('current');
			}

			<?php if ( $post_type === LP_COURSE_CPT && LP_Request::get( 'taxonomy' ) === 'course_category' ) {?>
			$("body").removeClass('sticky-menu');
			$lpMainMenu.addClass('wp-has-current-submenu wp-menu-open').removeClass('wp-not-current-submenu');
			$lpMainMenu.children('a').addClass('wp-has-current-submenu wp-menu-open').removeClass('wp-not-current-submenu');
			<?php
			}
			?>
		});
	</script>
	<?php
}

add_action( 'admin_footer', 'learn_press_active_course_menu' );

/*
 * Display tabs related to course in admin when user
 * viewing/editing course/category/tags.
 */
function learn_press_admin_course_tabs() {
	if ( ! is_admin() ) {
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
		$admin_tabs_on_page[ $page ] = $tabs;
	}


	$current_page_id = get_current_screen()->id;
	$current_user    = wp_get_current_user();
	if ( ! in_array( 'administrator', $current_user->roles ) ) {
		return;
	}
	if ( ! empty( $admin_tabs_on_page[ $current_page_id ] ) && count( $admin_tabs_on_page[ $current_page_id ] ) ) {
		echo '<h2 class="nav-tab-wrapper lp-nav-tab-wrapper">';
		foreach ( $admin_tabs_on_page[ $current_page_id ] as $admin_tab_id ) {

			$class = ( $admin_tabs[ $admin_tab_id ]["id"] == $current_page_id ) ? "nav-tab nav-tab-active" : "nav-tab";
			echo '<a href="' . admin_url( $admin_tabs[ $admin_tab_id ]["link"] ) . '" class="' . $class . ' nav-tab-' . $admin_tabs[ $admin_tab_id ]["id"] . '">' . $admin_tabs[ $admin_tab_id ]["name"] . '</a>';
		}
		echo '</h2>';
	}
}

add_action( 'all_admin_notices', 'learn_press_admin_course_tabs' );

/**
 * Create some warning messages:
 *  + LP Profile page is not setup
 *  + LP Checkout page is not setup
 */
if ( ! function_exists( 'lp_remove_admin_warning' ) ) {

	function lp_remove_admin_warning() {

		if ( isset( $_POST['action'] ) && $_POST['action'] === 'lp_remove_admin_warning' && isset( $_POST['name'] ) ) {

			if ( empty( $transient_profile ) ) {
				set_transient( $_POST['name'], true, 60 * 60 * 12 ); // Cache in 24 hours
			}
			echo 'success';
			wp_die();

		}

		echo 'error';
		wp_die();
	}
}
add_action( 'wp_ajax_lp_remove_admin_warning', 'lp_remove_admin_warning' );