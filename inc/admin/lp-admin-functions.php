<?php
/**
 * Common functions used for admin
 *
 * @package   LearnPress
 * @author    ThimPress
 * @version   1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Get html view path for admin to display
 *
 * @param $name
 *
 * @return mixed
 */
function learn_press_get_admin_view( $name ) {
	if ( !preg_match( '/\.(.*)$/', $name ) ) {
		$name .= '.php';
	}
	$view = LP()->plugin_path( 'inc/admin/views/' . $name );
	return apply_filters( 'learn_press_admin_view', $view, $name );
}

/**
 * Find a full path of a view and display the content in admin
 *
 * @param            $name
 * @param array      $args
 * @param bool|false $include_once
 *
 * @return bool
 */
function learn_press_admin_view( $name, $args = array(), $include_once = false ) {
	$view = learn_press_get_admin_view( $name );
	if ( file_exists( $view ) ) {
		// extract parameters as local variables if passed
		is_array( $args ) && extract( $args );
		do_action( 'learn_press_before_display_admin_view', $name, $args );
		if ( $include_once ) {
			include_once $view;
		} else {
			include $view;
		}
		do_action( 'learn_press_after_display_admin_view', $name, $args );
		return true;
	}
	return false;
}

/**
 * List all pages as a dropdown with "Add New Page" option
 *
 * @param            $name
 * @param bool|false $selected
 * @param array      $args
 *
 * @return mixed|string
 */
function learn_press_pages_dropdown( $name, $selected = false, $args = array() ) {
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

	if ( empty( $id ) ) {
		$id = $name;
	}
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

	$class .= 'learn-press-dropdown-pages';

	if ( $class ) {
		$replace .= ' class="' . $class . '"';
	}
	if ( $css ) {
		$replace .= ' style="' . $css . '"';
	}

	$replace .= ' data-selected="' . $selected . '"';
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
		<p class="learn-press-quick-add-page-inline <?php echo $id; ?> hide-if-js">
			<input type="text" />
			<button class="button" type="button"><?php _e( 'Ok', 'learn_press' ); ?></button>
			<a href=""><?php _e( 'Cancel', 'learn_press' ); ?></a>
		</p>
		<p class="learn-press-quick-add-page-actions <?php echo $id; ?><?php echo $selected ? '' : ' hide-if-js'; ?>">
			<a class="edit-page" href="<?php echo get_edit_post_link( $selected ); ?>" target="_blank"><?php _e( 'Edit Page', 'learn_press' ); ?></a>
			<a class="view-page" href="<?php echo get_permalink( $selected ); ?>" target="_blank"><?php _e( 'View Page', 'learn_press' ); ?></a>
		</p>
		<?php $output .= ob_get_clean();
	}
	if ( $echo ) {
		echo $output;
	}

	return $output;
}


/**************************************************/
/**************************************************/
/**************************************************/

/**
 * Translate javascript text
 */
function learn_press_admin_localize_script() {
	if ( defined( 'DOING_AJAX' ) || !is_admin() ) return;
	$translate = array(
		'quizzes_is_not_available' => __( 'Quiz is not available', 'learn_press' ),
		'lessons_is_not_available' => __( 'Lesson is not available', 'learn_press' )
	);
	LP_Admin_Assets::add_localize( $translate );
}

add_action( 'init', 'learn_press_admin_localize_script' );

/**
 * Default admin settings pages
 *
 * @return mixed
 */
function learn_press_settings_tabs_array() {
	$tabs = array(
		'general'  => __( 'General', 'learn_press' ),
		'courses'  => __( 'Courses', 'learn_press' ),
		'pages'    => __( 'Pages', 'learn_press' ),
		'payments' => __( 'Payments', 'learn_press' ),
		'checkout' => __( 'Checkout', 'learn_press' ),
		//'profile'  => __( 'Profile', 'learn_press' ),
		'emails'   => __( 'Emails', 'learn_press' )
	);
	return apply_filters( 'learn_press_settings_tabs_array', $tabs );
}

/**
 * Count number of orders between to dates
 *
 * @param string
 * @param int
 *
 * @return int
 */
function learn_press_get_order_by_time( $by, $time ) {
	global $wpdb;
	$user_id = get_current_user_id();

	$y = date( 'Y', $time );
	$m = date( 'm', $time );
	$d = date( 'd', $time );
	switch ( $by ) {
		case 'days':
			$orders = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM $wpdb->posts AS p
					INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id
					WHERE p.post_author = %d
					AND p.post_type = %s
					AND p.post_status = %s
					AND m.meta_key = %s
					AND m.meta_value = %s
					AND YEAR(p.post_date) = %s AND MONTH(p.post_date) = %s AND DAY(p.post_date) = %s",
					$user_id, LP()->order_post_type, 'publish', '_learn_press_transaction_status', 'completed', $y, $m, $d
				)
			);
			break;
		case 'months':
			$orders = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM $wpdb->posts AS p
					INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id
					WHERE p.post_author = %d
					AND p.post_type = %s
					AND p.post_status = %s
					AND m.meta_key = %s
					AND m.meta_value = %s
					AND YEAR(p.post_date) = %s AND MONTH(p.post_date) = %s",
					$user_id, LP()->order_post_type, 'publish', '_learn_press_transaction_status', 'completed', $y, $m
				)
			);
			break;
	}
	return $orders;
}

/**
 * Count number of orders by status
 *
 * @param string Status of the orders
 *
 * @return int
 */
function learn_press_get_courses_by_status( $status ) {
	global $wpdb;
	$user_id = get_current_user_id();
	$courses = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM $wpdb->posts
			WHERE post_author = %d
			AND post_type = %s
			AND post_status = %s",
			$user_id, LP()->course_post_type, $status
		)
	);
	return $courses;
}

/**
 * Count number of orders by price
 *
 * @param string
 *
 * @return int
 */
function learn_press_get_courses_by_price( $fee ) {
	global $wpdb;
	$user_id = get_current_user_id();
	$courses = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*)
			FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS m ON p.ID = m.post_id
			WHERE p.post_author = %d
			AND p.post_type = %s
			AND p.post_status IN (%s, %s)
			AND m.meta_key = %s
			AND m.meta_value = %s",
			$user_id, LP()->course_post_type, 'publish', 'pending', '_lpr_course_payment', $fee
		)
	);
	return $courses;
}

/**
 * Get data about students to render in chart
 *
 * @param null $from
 * @param null $by
 * @param      $time_ago
 *
 * @return array
 */
function learn_press_get_chart_students( $from = null, $by = null, $time_ago ) {
	$labels   = array();
	$datasets = array();
	if ( is_null( $from ) ) {
		$from = current_time( 'mysql' );
	}
	// $by: days, months or years
	if ( is_null( $by ) ) {
		$by = 'days';
	}
	switch ( $by ) {
		case 'days':
			$date_format = 'M d';
			break;
		case 'months':
			$date_format = 'M Y';
			break;
		case 'years':
			$date_format = 'Y';
			break;
	}

	for ( $i = - $time_ago + 1; $i <= 0; $i ++ ) {
		$labels[]              = date( $date_format, strtotime( "$i $by", strtotime( $from ) ) );
		$datasets[0]['data'][] = learn_press_get_order_by_time( $by, strtotime( "$i $by", strtotime( $from ) ) );
	}
	$colors                              = learn_press_get_admin_colors();
	$datasets[0]['fillColor']            = 'rgba(255,255,255,0.1)';
	$datasets[0]['strokeColor']          = $colors[0];
	$datasets[0]['pointColor']           = $colors[0];
	$datasets[0]['pointStrokeColor']     = $colors[2];
	$datasets[0]['pointHighlightFill']   = $colors[2];
	$datasets[0]['pointHighlightStroke'] = $colors[0];
	return array(
		'labels'   => $labels,
		'datasets' => $datasets
	);
}

/**
 * Get data about courses to render in the chart
 * @return array
 */
function learn_press_get_chart_courses() {
	$labels              = array( __( 'Pending Courses / Publish Courses', 'learn_press' ), __( 'Free Courses / Priced Courses', 'learn_press' ) );
	$datasets            = array();
	$datasets[0]['data'] = array( learn_press_get_courses_by_status( 'pending' ), learn_press_get_courses_by_price( 'free' ) );
	$datasets[1]['data'] = array( learn_press_get_courses_by_status( 'publish' ), learn_press_get_courses_by_price( 'not_free' ) );

	$colors                     = learn_press_get_admin_colors();
	$datasets[0]['fillColor']   = $colors[1];
	$datasets[0]['strokeColor'] = $colors[1];
	$datasets[1]['fillColor']   = $colors[3];
	$datasets[1]['strokeColor'] = $colors[3];
	return array(
		'labels'   => $labels,
		'datasets' => $datasets
	);
}

/**
 * Get colors setting up by admin user
 * @return array
 */
function learn_press_get_admin_colors() {
	$admin_color = get_user_meta( get_current_user_id(), 'admin_color', true );
	global $_wp_admin_css_colors;
	$colors = array();
	if ( !empty( $_wp_admin_css_colors[$admin_color]->colors ) ) {
		$colors = $_wp_admin_css_colors[$admin_color]->colors;
	}
	if ( empty ( $colors[0] ) ) {
		$colors[0] = '#000000';
	}
	if ( empty ( $colors[2] ) ) {
		$colors[2] = '#00FF00';
	}
	return $colors;
}

/**
 * Convert an array to json format and print out to browser
 *
 * @param array $chart
 */
function learn_press_process_chart( $chart = array() ) {
	$data = json_encode(
		array(
			'labels'   => $chart['labels'],
			'datasets' => $chart['datasets']
		)
	);
	echo $data;
}

/**
 * Print out the configuration for admin chart
 */
function learn_press_config_chart() {
	$colors = learn_press_get_admin_colors();
	$config = array(
		'scaleShowGridLines'      => true,
		'scaleGridLineColor'      => "#777",
		'scaleGridLineWidth'      => 0.3,
		'scaleFontColor'          => "#444",
		'scaleLineColor'          => $colors[0],
		'bezierCurve'             => true,
		'bezierCurveTension'      => 0.2,
		'pointDotRadius'          => 5,
		'pointDotStrokeWidth'     => 5,
		'pointHitDetectionRadius' => 20,
		'datasetStroke'           => true,
		'responsive'              => true,
		'tooltipFillColor'        => $colors[2],
		'tooltipFontColor'        => "#eee",
		'tooltipCornerRadius'     => 0,
		'tooltipYPadding'         => 10,
		'tooltipXPadding'         => 10,
		'barDatasetSpacing'       => 10,
		'barValueSpacing'         => 200

	);
	echo json_encode( $config );
}

function set_post_order_in_admin( $wp_query ) {
	global $pagenow;
	if ( isset( $_GET['post_type'] ) ) {
		$post_type = $_GET['post_type'];
	} else $post_type = '';
	if ( is_admin() && 'edit.php' == $pagenow && $post_type == LP()->course_post_type && !isset( $_GET['orderby'] ) ) {
		$wp_query->set( 'orderby', 'date' );
		$wp_query->set( 'order', 'DSC' );
	}
}

add_filter( 'pre_get_posts', 'set_post_order_in_admin' );
/**
 * Add actions to the list of the course. e.g: Duplicate link
 *
 * @param $actions
 *
 * @return mixed
 */
function learn_press_add_row_action_link( $actions ) {
	global $post;
	if ( LP()->course_post_type == $post->post_type ) {
		$duplicate_link = admin_url( 'edit.php?post_type=lp_course&action=lp-duplicate-course&post=' . $post->ID );
		$duplicate_link = array(
			array(
				'link'  => $duplicate_link,
				'title' => __( 'Duplicate this course', 'learn_press' ),
				'class' => ''
			)
		);
		$links          = apply_filters( 'learn_press_row_action_links', $duplicate_link );
		if ( count( $links ) > 1 ) {
			$drop_down = array( '<ul class="lpr-row-action-dropdown">' );
			foreach ( $links as $link ) {
				$drop_down[] = '<li>' . sprintf( '<a href="%s" class="%s">%s</a>', $link['link'], $link['class'], $link['title'] ) . '</li>';
			};
			$drop_down[] = '</ul>';
			$link        = sprintf( '<div class="lpr-row-actions"><a href="%s">%s</a>%s</div>', 'javascript: void(0);', __( 'Course', 'learn_press' ), join( "\n", $drop_down ) );
		} else {
			$link = array_shift( $links );
			$link = sprintf( '<a href="%s" class="%s">%s</a>', $link['link'], $link['class'], $link['title'] );
		}
		$actions['lpr-course-row-action'] = $link;
	}
	return $actions;
}

add_filter( 'page_row_actions', 'learn_press_add_row_action_link' );

/**
 * Duplicate a course when user hit "Duplicate" button
 *
 * @author  TuNN
 */
function learn_press_process_duplicate_action() {

	$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
	$action        = $wp_list_table->current_action();

	if ( isset( $_REQUEST['action'] ) && ( $action = $_REQUEST['action'] ) == 'lp-duplicate-course' ) {
		$post_id = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : 0;
		if ( $post_id && is_array( $post_id ) ) {
			$post_id = array_shift( $post_id );
		}
		// check for post is exists
		if ( !( $post_id && $post = get_post( $post_id ) ) ) {
			wp_die( __( 'Op! The course does not exists', 'learn_press' ) );
		}
		// ensure that user can create course
		if ( !current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Sorry! You have not permission to duplicate this course', 'learn_press' ) );
		}

		// assign course to current user
		$current_user      = wp_get_current_user();
		$new_course_author = $current_user->ID;

		// setup course data
		$new_course_title = $post->post_title . ' - Copy';
		$args             = array(
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_course_author,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name,
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => 'draft',
			'post_title'     => $new_course_title,
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order
		);

		// insert new course and get it ID
		$new_post_id = wp_insert_post( $args );

		// assign related tags/categories to new course
		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
			wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
		}

		// duplicate course data
		global $wpdb;
		$course_meta = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id" );
		if ( count( $course_meta ) != 0 ) {
			$sql_query     = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			$sql_query_sel = array();

			foreach ( $course_meta as $meta ) {
				$meta_key   = $meta->meta_key;
				$meta_value = addslashes( $meta->meta_value );

				$sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
			}

			$sql_query .= implode( " UNION ALL ", $sql_query_sel );
			$wpdb->query( $sql_query );
		}
		wp_redirect( admin_url( 'edit.php?post_type=lpr_course' ) );
		die();
	}
}

add_action( 'load-edit.php', 'learn_press_process_duplicate_action' );

function learn_press_admin_notice_bundle_activation() {
	if ( !empty( $_REQUEST['tab'] ) && ( 'bundle_activate' != $_REQUEST['tab'] ) && learn_press_get_notice_dismiss( 'bundle-addon-install', '' ) != 'off' ) {
		?>
		<div class="updated">
			<p>
				<?php printf( __( 'Want full free features? Click <a href="%s">here</a> to install LearnPress Add-ons Bundle for free!', 'learn_press' ), admin_url( 'admin.php?page=learn_press_add_ons&tab=bundle_activate' ) ); ?>
				<?php printf( '<a href="" class="learn-press-admin-notice-dismiss" data-context="bundle-addon-install" data-transient="-1"></a>' ); ?>
			</p>
		</div>
		<?php
	}
}

add_action( 'admin_notices', 'learn_press_admin_notice_bundle_activation' );

/**
 * Install a plugin
 *
 * @param string $plugin_name
 *
 * @return array
 */
function learn_press_install_add_on( $plugin_name ) {
	require_once( LP_PLUGIN_PATH . '/inc/admin/class-lp-upgrader.php' );
	$upgrader = new LP_Upgrader();
	global $wp_filesystem;
	$response = array();

	$package = 'http://thimpress.com/lprepo/' . $plugin_name . '.zip';

	$package = $upgrader->download_package( $package );
	if ( is_wp_error( $package ) ) {
		$response['error'] = $package;
	} else {
		$working_dir = $upgrader->unpack_package( $package, true, $plugin_name );
		if ( is_wp_error( $working_dir ) ) {
			$response['error'] = $working_dir;
		} else {

			$wp_upgrader = new WP_Upgrader();
			$options     = array(
				'source'            => $working_dir,
				'destination'       => WP_PLUGIN_DIR,
				'clear_destination' => false, // Do not overwrite files.
				'clear_working'     => true,
				'hook_extra'        => array(
					'type'   => 'plugin',
					'action' => 'install'
				)
			);
			//$response = array();
			$result = $wp_upgrader->install_package( $options );

			if ( is_wp_error( $result ) ) {
				$response['error'] = $result;
			} else {
				$response         = $result;
				$response['text'] = __( 'Installed', 'learn_press' );
			}
		}
	}
	return $response;
}

function learn_press_accept_become_a_teacher() {
	$action  = !empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
	$user_id = !empty( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : '';
	if ( !$action || !$user_id || ( $action != 'accept-to-be-teacher' ) ) return;

	$be_teacher = new WP_User( $user_id );
	$be_teacher->set_role( LP()->teacher_role );

	do_action( 'learn_press_user_become_a_teacher', $user_id );
}

add_action( 'admin_notices', 'learn_press_accept_become_a_teacher' );

function learn_press_user_become_a_teacher_notice( $user_id ) {
	$user = new WP_User( $user_id );
	?>
	<div class="updated">
		<p><?php printf( __( 'The user %s has become a teacher', 'learn_press' ), $user->user_login ); ?></p>
	</div>
	<?php
}

add_action( 'learn_press_user_become_a_teacher', 'learn_press_user_become_a_teacher_notice' );

/**
 * Check to see if a plugin is already installed or not
 *
 * @param $plugin
 *
 * @return bool
 */
function learn_press_is_plugin_install( $plugin ) {
	$installed_plugins = get_plugins();
	return isset( $installed_plugins[$plugin] );
}

/**
 * Get plugin file that contains the information from slug
 *
 * @param $slug
 *
 * @return mixed
 */
function learn_press_plugin_basename_from_slug( $slug ) {
	$keys = array_keys( get_plugins() );
	foreach ( $keys as $key ) {
		if ( preg_match( '|^' . $slug . '/|', $key ) ) {
			return $key;
		}
	}
	return $slug;
}

function learn_press_one_click_install_sample_data_notice() {
	$courses = get_posts(
		array(
			'post_type'   => LP()->course_post_type,
			'post_status' => 'any'
		)
	);
	if ( ( 0 == sizeof( $courses ) ) && ( 'off' != get_transient( 'learn_press_install_sample_data' ) ) ) {
		printf(
			'<div class="updated" id="learn-press-install-sample-data-notice">
				<div class="install-sample-data-notice">
                <p>%s</p>
                <p>%s <strong>%s</strong> %s
                <p><a href="" class="button yes" data-action="yes">%s</a> <a href="" class="button disabled no" data-action="no">%s</a></p>
                </div>
                <div class="install-sample-data-loading">
                	<p>Importing...</p>
				</div>
            </div>',
			__( 'You haven\'t got any courses yet! Would you like to import sample data?', 'learn_press' ),
			__( 'If yes, it requires to install addon named', 'learn_press' ),
			__( 'LearnPress Import/Export', 'learn_press' ),
			__( 'but don\'t worry because it is completely automated.', 'learn_press' ),
			__( 'Import now', 'learn_press' ),
			__( 'No, thanks!', 'learn_press' )
		);
	}
}
//add_action( 'admin_notices', 'learn_press_one_click_install_sample_data_notice' );

function learn_press_request_query( $vars = array() ) {
	global $typenow, $wp_query, $wp_post_statuses;
	if ( LP()->order_post_type === $typenow ) {
		// Status
		if ( !isset( $vars['post_status'] ) ) {
			$post_statuses = learn_press_get_order_statuses();

			foreach ( $post_statuses as $status => $value ) {
				if ( isset( $wp_post_statuses[$status] ) && false === $wp_post_statuses[$status]->show_in_admin_all_list ) {
					unset( $post_statuses[$status] );
				}
			}

			$vars['post_status'] = array_keys( $post_statuses );

		}
	}
	return $vars;
}

add_filter( 'request', 'learn_press_request_query' );

function _learn_press_reset_course_data() {
	if ( empty( $_REQUEST['reset-course-data'] ) ) {
		return false;
	}
	learn_press_reset_course_data( intval( $_REQUEST['reset-course-data'] ) );
	wp_redirect( remove_query_arg( 'reset-course-data' ) );
}

add_action( 'init', '_learn_press_reset_course_data' );

/***********************/
function learn_press_admin_section_loop_item_class( $item, $section ) {
	$classes   = array(
		'lp-section-item'
	);
	$classes[] = 'lp-item-' . $item->post_type;
	if ( !absint( $item->ID ) ) {
		$classes[] = 'lp-item-empty lp-item-new';
	}
	$classes = apply_filters( 'learn_press_section_loop_item_class', $classes, $item, $section );
	if ( $classes ) echo 'class="' . join( ' ', $classes ) . '"';
	return $classes;
}

function learn_press_disable_checked_ontop( $args ) {

	if ( 'course_category' == $args['taxonomy'] ) {
		$args['checked_ontop'] = false;
	}

	return $args;
}

add_filter( 'wp_terms_checklist_args', 'learn_press_disable_checked_ontop' );

function learn_press_output_admin_template(){
	learn_press_admin_view( 'admin-template.php' );
}
add_action( 'admin_print_scripts', 'learn_press_output_admin_template' );

function learn_press_output_screen_id(){
	$screen = get_current_screen();
	if( $screen ){
		echo "<div style=\"position:fixed;top: 0; left:0; z-index: 99999999; background-color:#FFF;padding:4px;\">".$screen->id . "</div>";
	}
}
//add_action( 'admin_head', 'learn_press_output_screen_id' );

function learn_press_get_screens(){
	$screen_id = sanitize_title( __( 'LearnPress', 'learn_press' ) );
	$screens   = array(
		'toplevel_page_' . $screen_id,
		$screen_id . '_page_learn_press_statistics',
		$screen_id . '_page_learn_press_add_ons'
	);
	foreach( array( 'lp_course', 'lp_lesson', 'lp_quiz', 'lp_lesson', 'lp_order' ) as $post_type ){
		$screens[] = 'edit-' . $post_type;
		$screens[] = $post_type;
	}

	return apply_filters( 'learn_press_screen_ids', $screens );
}

function learn_press_get_admin_pages(){
	return apply_filters(
		'learn_press_admin_pages',
		array(
			'learn_press_settings'
		)
	);
}

function learn_press_get_notice_dismiss( $context, $type = 'transient' ){
	if( $type == 'transient' ) {
		return get_transient( 'learn_press_dismiss_notice_' . $context );
	}
	return get_option( 'learn_press_dismiss_notice_' . $context );
}
