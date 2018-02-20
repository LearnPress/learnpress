<?php
/**
 * Defines the functions which called by hooks
 */

/**
 * Filter post types the user can access in admin
 *
 * @param $query
 */
function _learn_press_set_user_items( $query ) {
	global $post_type, $pagenow, $wpdb;

	if ( current_user_can( 'manage_options' ) || ! current_user_can( LP_TEACHER_ROLE ) || ! is_admin() || ( $pagenow != 'edit.php' ) ) {
		return $query;
	}
	if ( ! in_array( $post_type, array( 'lp_course', LP_LESSON_CPT, LP_QUIZ_CPT, LP_QUESTION_CPT ) ) ) {
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
}

add_action( 'pre_get_posts', '_learn_press_set_user_items', 10 );

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

add_action( 'wp_dashboard_setup', 'learnpress_dashboard_widgets' );

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