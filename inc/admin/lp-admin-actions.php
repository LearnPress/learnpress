<?php
/**
 * Active Courses menu under LearnPress
 * when user is editing course and course
 * category.
 */
function learn_press_active_course_menu() {
	$post_type = LP_Request::get_param( 'post_type' );
	if ( ! $post_type ) {
		return;
	}
	?>

	<script>
		jQuery( function( $ ) {
			var $lpMainMenu = $( '#toplevel_page_learn_press' ),
				href = 'edit.php?post_type=<?php echo esc_js( $_GET['post_type'] ); ?>',
				$current = $( 'a[href="' + href + '"]', $lpMainMenu );

			if ( $current.length ) {
				$current.addClass( 'current' );
				$current.parent( 'li' ).addClass( 'current' );
			}

			<?php if ( $post_type === LP_COURSE_CPT && LP_Request::get_param( 'taxonomy' ) === 'course_category' ) { ?>
				$( 'body' ).removeClass( 'sticky-menu' );
				$lpMainMenu.addClass( 'wp-has-current-submenu wp-menu-open' ).removeClass( 'wp-not-current-submenu' );
				$lpMainMenu.children( 'a' ).addClass( 'wp-has-current-submenu wp-menu-open' ).removeClass( 'wp-not-current-submenu' );
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
/*
function learn_press_admin_course_tabs() {
	if ( ! is_admin() ) {
		return;
	}

	$wp_screen = get_current_screen();

	if ( ! $wp_screen || 'edit-lp_course' !== $wp_screen->id ) {
		return;
	}

	$admin_tabs = apply_filters(
		'learn_press_admin_tabs_info',
		array(
			10 => array(
				'link' => 'edit.php?post_type=lp_course',
				'name' => esc_html__( 'Courses', 'learnpress' ),
				'id'   => 'edit-lp_course',
			),
			20 => array(
				'link' => 'edit-tags.php?taxonomy=course_category&post_type=' . LP_COURSE_CPT,
				'name' => esc_html__( 'Categories', 'learnpress' ),
				'id'   => 'edit-course_category',
			),
			30 => array(
				'link' => 'edit-tags.php?taxonomy=course_tag&post_type=' . LP_COURSE_CPT,
				'name' => esc_html__( 'Tags', 'learnpress' ),
				'id'   => 'edit-course_tag',
			),

		)
	);

	ksort( $admin_tabs );
	$tabs = array();

	foreach ( $admin_tabs as $key => $value ) {
		array_push( $tabs, $key );
	}

	$pages = apply_filters(
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
			$class = ( $admin_tabs[ $admin_tab_id ]['id'] == $current_page_id ) ? 'nav-tab nav-tab-active' : 'nav-tab';
			// echo '<a href="' . admin_url( $admin_tabs[ $admin_tab_id ]['link'] ) . '" class="' . $class . ' nav-tab-' . $admin_tabs[ $admin_tab_id ]['id'] . '">' . $admin_tabs[ $admin_tab_id ]['name'] . '</a>';
			echo wp_kses(
				sprintf(
					'<a href="%s" class="%s">%s</a>',
					admin_url( $admin_tabs[ $admin_tab_id ]['link'] ),
					$class . ' nav-tab-' . $admin_tabs[ $admin_tab_id ]['id'],
					$admin_tabs[ $admin_tab_id ]['name']
				),
				true
			);
		}

		echo '</h2>';
	}
}
add_action( 'all_admin_notices', 'learn_press_admin_course_tabs' );
*/

