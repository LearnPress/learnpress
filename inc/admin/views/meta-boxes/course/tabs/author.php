<?php
$author = $post ? $post->post_author : get_current_user_id();

$options = array();
$role    = array( 'administrator', 'lp_teacher' );

$role = apply_filters( 'learn_press_course_author_role_meta_box', $role );

foreach ( $role as $_role ) {
	$users_by_role = get_users( array( 'role' => $_role ) );

	if ( $users_by_role ) {
		foreach ( $users_by_role as $user ) {
			$options[ $user->get( 'ID' ) ] = $user->user_login;
		}
	}
}
?>

<div id="author_course_data" class="lp-meta-box-course-panels">
	<?php
	do_action( 'learnpress/course-settings/before-author' );

	if ( is_super_admin() ) {
		lp_meta_box_select_field(
			array(
				'id'      => '_lp_course_author',
				'label'   => esc_html__( 'Author', 'learnpress' ),
				'options' => $options,
				'default' => $author,
				'style'   => 'min-width:200px;',
			)
		);
	} else {
		esc_html_e( 'Author is set by Admintrator', 'learnpress' );
	}

	do_action( 'learnpress/course-settings/after-author' );
	?>
</div>

<?php



