<?php
/**
 * @file
 * Common functions used for admin
 */

/**
 * Translate javascript text
 */
function learn_press_admin_localize_script(){
    if( defined( 'DOING_AJAX' ) || !is_admin() ) return;
    $translate = array(
        'quizzes_is_not_available' => __( 'Quiz is not available', 'learn_press' ),
        'lessons_is_not_available'   => __( 'Lesson is not available', 'learn_press' )
    );
    LPR_Admin_Assets::add_localize( $translate );
}
add_action( 'init', 'learn_press_admin_localize_script' );

/**
 * Default admin settings pages
 *
 * @return mixed
 */
function learn_press_settings_tabs_array() {
	$tabs = array(
		'general' => __( 'General', 'learn_press' ),
		'pages'   => __( 'Pages', 'learn_press' ),
		'payment' => __( 'Payments', 'learn_press' ),
		'emails'  => __( 'Emails', 'learn_press' )
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
					$user_id, 'lpr_order', 'publish', '_learn_press_transaction_status', 'completed', $y, $m, $d
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
					$user_id, 'lpr_order', 'publish', '_learn_press_transaction_status', 'completed', $y, $m
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
			$user_id, 'lpr_course', $status
		)
	);
	return $courses;
}

/**
 * Count number of orders by price
 *
 * @param string
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
			$user_id, 'lpr_course', 'publish', 'pending', '_lpr_course_payment', $fee
		)
	);
	return $courses;
}

/**
 * Get data about students to render in chart
 *
 * @param null $from
 * @param null $by
 * @param $time_ago
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
	if( isset($_GET['post_type']) ) {
		$post_type = $_GET['post_type'];
	} else $post_type = '';
  	if ( is_admin() && 'edit.php' == $pagenow && $post_type=='lpr_course' && !isset($_GET['orderby'])) {
    	$wp_query->set( 'orderby', 'date' );
    	$wp_query->set( 'order', 'DSC' );
  	}
}
add_filter('pre_get_posts', 'set_post_order_in_admin' );

/**
 * Add actions to the list of the course. e.g: Duplicate link
 *
 * @param $actions
 * @return mixed
 */
function learn_press_add_row_action_link( $actions ) {
    global $post;
    if ( 'lpr_course' == $post->post_type ) {
        $duplicate_link = admin_url( 'edit.php?post_type=lpr_course&action=lpr-duplicate-course&post=' . $post->ID );
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

    if ( isset( $_REQUEST['action'] ) && ( $action = $_REQUEST['action'] ) == 'lpr-duplicate-course' ) {
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
    if( ! empty( $_REQUEST['tab'] ) && ( 'bundle_activate' != $_REQUEST['tab'] ) ) {
        ?>
        <div class="updated">
            <p><?php printf(__('Want full free features? Click <a href="%s">here</a> to install LearnPress Add-ons Bundle for free!', 'learn_press'), admin_url('admin.php?page=learn_press_add_ons&tab=bundle_activate')); ?></p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'learn_press_admin_notice_bundle_activation' );

/**
 * Install a plugin
 *
 * @param string $plugin_name
 * @return array
 */
function learn_press_install_add_on( $plugin_name ){
    require_once( LPR_PLUGIN_PATH . '/inc/admin/class-lpr-upgrader.php' );
    $upgrader = new LPR_Upgrader();
    global $wp_filesystem;
    $response = array();

    $package = 'http://thimpress.com/lprepo/' . $plugin_name . '.zip';

    $package = $upgrader->download_package( $package );
    if( is_wp_error( $package ) ) {
        $response['error'] = $package;
    }else {
        $working_dir = $upgrader->unpack_package($package, true, $plugin_name);
        if (is_wp_error($working_dir)){
            $response['error'] = $working_dir;
        }else {

            $wp_upgrader = new WP_Upgrader();
            $options = array(
                'source' => $working_dir,
                'destination' => WP_PLUGIN_DIR,
                'clear_destination' => false, // Do not overwrite files.
                'clear_working' => true,
                'hook_extra' => array(
                    'type' => 'plugin',
                    'action' => 'install'
                )
            );
            //$response = array();
            $result = $wp_upgrader->install_package($options);

            if (is_wp_error($result)) {
                $response['error'] = $result;
            }else{
                $response = $result;
                $response['text'] = __( 'Installed' );
            }
        }
    }
    return $response;
}

function learn_press_accept_become_a_teacher(){
    $action     = ! empty( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
    $user_id    = ! empty( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : '';
    if( ! $action || ! $user_id || ( $action != 'accept-to-be-teacher' ) ) return;

    $be_teacher = new WP_User( $user_id );
    $be_teacher->set_role( 'lpr_teacher' );

    do_action( 'learn_press_user_become_a_teacher', $user_id );
}
add_action( 'admin_notices', 'learn_press_accept_become_a_teacher' );

function learn_press_user_become_a_teacher_notice( $user_id ){
    $user = new WP_User( $user_id );
?>
    <div class="updated">
        <p><?php printf( __( 'The user %s has become a teacher', 'learn_press' ), $user->user_login );?></p>
    </div>
<?php
}
add_action( 'learn_press_user_become_a_teacher', 'learn_press_user_become_a_teacher_notice' );

/**
 * Check to see if a plugin is already installed or not
 *
 * @param $plugin
 * @return bool
 */
function learn_press_is_plugin_install( $plugin ){
    $installed_plugins = get_plugins();
    return isset( $installed_plugins[ $plugin ] );
}

/**
 * Get plugin file that contains the information from slug
 *
 * @param $slug
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

function learn_press_one_click_install_sample_data_notice(){
    $courses = get_posts(
        array(
            'post_type' => 'lpr_course',
            'post_status' => 'any'
        )
    );
    if( ( 0 == sizeof( $courses ) ) && ( 'off' != get_transient( 'learn_press_install_sample_data' ) ) ){
        _e('
            <div class="updated" id="learn-press-install-sample-data-notice">
                <p>You haven\'t got any courses yet! Would you like to install sample data?</p>
                <p>If yes, it requires to install addon named <strong>LearnPress Import/Export</strong> but don\'t worry because it is completely automated. [<a href="" class="yes">Yes</a>]&nbsp;&nbsp;&nbsp;&nbsp;[<a href="" class="no">No thank!</a>]</p>
            </div>'
        );
    }
}
add_action( 'admin_notices', 'learn_press_one_click_install_sample_data_notice' );