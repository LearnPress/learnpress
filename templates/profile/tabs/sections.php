<?php
/**
 * Template for displaying sections in the top inside a tab content.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

global $profile;

if ( ! isset( $tab_key, $tab_data ) ) {
	return;
}

if ( empty( $tab_data['sections'] ) ) {
	return;
}


$link = $profile->get_tab_link( $tab_key );

?>
    <ul class="lp-tab-sections">

		<?php
		foreach ( $tab_data['sections'] as $section_key => $section_data ) {
			$classes = array( esc_attr( $section_key ) );
			if ( $profile->is_current_section( $section_key, $section_key ) ) {
				$classes[] = 'active';
			}

			$section_slug = $profile->get_slug( $section_data, $section_key );
			$section_link = $profile->get_tab_link( $tab_key, $section_slug );

			?>
            <li class="<?php echo join( ' ', $classes ); ?>">
                <a href="<?php echo $section_link; ?>"><?php echo $section_data['title']; ?></a>
            </li>

		<?php } ?>

    </ul>

<?php

foreach ( $tab_data['sections'] as $section => $section_data ) {
	if ( is_callable( $section_data['callback'] ) ):
		echo call_user_func_array( $section_data['callback'], array( $section, $section_data, $profile ) );
	else:
		do_action( 'learn-press/profile-section-content', $section, $section_data, $profile );
	endif;
}