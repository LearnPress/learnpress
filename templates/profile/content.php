<?php
/**
 * Template for displaying user profile content.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/profile/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) ) {
	$user = learn_press_get_current_user();
}

$profile = learn_press_get_profile();
$tabs    = $profile->get_tabs();
$current = $profile->get_current_tab();

?>
<div id="learn-press-profile-content" class="lp-profile-content">

	<?php foreach ( $tabs as $tab_key => $tab_data ) {

		if ( ! $profile->tab_is_visible_for_user( $tab_key ) ) {
			continue;
		}
		?>

        <div id="profile-content-<?php echo esc_attr( $tab_key ); ?>">
			<?php
			learn_press_print_messages( true );

			// show profile sections
			do_action( 'learn-press/before-profile-content', $tab_key, $tab_data, $user ); ?>

			<?php if ( empty( $tab_data['sections'] ) ) {
				if ( is_callable( $tab_data['callback'] ) ) {
					echo call_user_func_array( $tab_data['callback'], array( $tab_key, $tab_data, $user ) );
				} else {
					do_action( 'learn-press/profile-content', $tab_key, $tab_data, $user );
				}
			} else {
				foreach ( $tab_data['sections'] as $key => $section ) {
					if ( $profile->get_current_section( '', false, false ) === $section['slug'] ) {
						if ( isset( $section['callback'] ) && is_callable( $section['callback'] ) ) {
							echo call_user_func_array( $section['callback'], array( $key, $section, $user ) );
						} else {
							do_action( 'learn-press/profile-section-content', $key, $section, $user );
						}
					}
				}
			} ?>

			<?php do_action( 'learn-press/after-profile-content' ); ?>
        </div>

	<?php } ?>

</div>