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

/**
 * @var LP_Profile $profile
 * @var LP_Profile_Tabs $tabs
 * @var LP_Profile_Tab $profile_tab
 */
$profile = learn_press_get_profile();
$tabs    = $profile->get_tabs();
$current = $profile->get_current_tab();


?>
<article id="profile-content" class="lp-profile-content">

	<?php foreach ( $tabs as $tab_key => $profile_tab ) {

		if ( ! $profile_tab->tab_is_visible_for_user( ) ) {
			continue;
		}
		?>

        <div id="profile-content-<?php echo esc_attr( $tab_key ); ?>">
			<?php
			// show profile sections
			do_action( 'learn-press/before-profile-content', $tab_key, $profile_tab, $user ); ?>

			<?php if ( empty( $profile_tab['sections'] ) ) {
				if ( is_callable( $profile_tab['callback'] ) ) {
					echo call_user_func_array( $profile_tab['callback'], array( $tab_key, $profile_tab, $user ) );
				} else {
					do_action( 'learn-press/profile-content', $tab_key, $profile_tab, $user );
				}
			} else {
				foreach ( $profile_tab['sections'] as $key => $section ) {
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

</article>