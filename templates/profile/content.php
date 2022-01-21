<?php
/**
 * Template for displaying user profile content.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/profile/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) || ! isset( $tab_key ) || ! isset( $profile ) || ! isset( $profile_tab ) ) {
	return;
}
?>

<article id="profile-content" class="lp-profile-content">
	<?php learn_press_print_messages( true ); ?>
	<div id="profile-content-<?php echo esc_attr( $tab_key ); ?>">
		<?php do_action( 'learn-press/before-profile-content', $tab_key, $profile_tab, $user ); ?>

		<?php
		if ( empty( $profile_tab['sections'] ) ) {
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
		}
		?>

		<?php do_action( 'learn-press/after-profile-content' ); ?>
	</div>
</article>
