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

			<?php if ( isset( $tab_data['sections'] ) ) { ?>
				<?php foreach ( $tab_data['sections'] as $section => $section_data ) {

					if ( is_callable( $section_data['callback'] ) ) {
						echo call_user_func_array( $section_data['callback'], array(
							$section,
							$section_data,
							$profile
						) );
					} else {
						do_action( 'learn-press/profile-section-content', $section, $section_data, $profile );
					}
				}
			} ?>

			<?php do_action( 'learn-press/after-profile-content' ); ?>
        </div>
	<?php } ?>

</div>
