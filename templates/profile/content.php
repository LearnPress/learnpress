<?php
/**
 * Template for displaying user profile content.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $user ) ) {
	$user = learn_press_get_current_user();
}

$profile = learn_press_get_profile();
$tabs    = $profile->get_tabs();
$current = $profile->get_current_tab();

?>
<div id="learn-press-profile-content" class="lp-profile-content">

	<?php foreach ( $tabs as $tab_key => $tab_data ) {
		if ( ! $profile->is_current_tab( $tab_key ) || ! $profile->current_user_can( "view-tab-{$tab_key}" ) ) {
			continue;
		}
		?>
        <div id="profile-content-<?php echo esc_attr( $tab_key ); ?>">
			<?php
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
					do_action( 'learn-press/profile-section-content', $key );
				}
			} ?>

			<?php do_action( 'learn-press/after-profile-content' ); ?>
        </div>
	<?php } ?>

</div>
