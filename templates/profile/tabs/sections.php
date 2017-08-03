<?php
if ( empty( $args['sections'] ) ) {
	return;
}
global $wp;
if ( ! empty( $args['sections'] ) ) {

	echo '<ul class="lp-sections">';
	foreach ( $args['sections'] as $section => $section_data ) {
		$link = learn_press_user_profile_link( $user->get_id(), $tab === '' ? false : $tab );

		$class = ! empty( $wp->query_vars['section'] ) && $wp->query_vars['section'] == $section ? 'active' : '';
		echo '<li class="' . $class . '"><a href="' . $link . $section . '/">' . $section_data['title'] . '</a></li>';
	}

	echo '</ul>';

	foreach ( $args['sections'] as $section => $section_data ) {
		if ( is_callable( $section_data['callback'] ) ): print_r( $section_data );

			echo call_user_func_array( $section_data['callback'], array( $section, $section_data, $user ) );

		else:

			do_action( 'learn-press/profile-section-content', $section, $section_data, $user );

		endif;
	}
}