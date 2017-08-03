<?php
/**
 * Template for displaying profile content.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined('ABSPATH') or exit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $user ) ) {
	$user = learn_press_get_current_user();
}

$profile = LP_Profile::instance( $user->get_id() );
$tabs    = $profile->get_tabs();

$current = learn_press_get_current_profile_tab();

?>
<div id="learn-press-profile-content" class="lp-profile-content">
    <div class="user-profile-tabs learn-press-tabs-wrapper-x">
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<?php if ( $current == $key && learn_press_current_user_can_view_profile_section( $key, $user ) ) { ?>
                <div class="learn-press-tab" id="tab-<?php echo esc_attr( $key ); ?>">
                    <div class="entry-tab-inner">
						<?php if ( is_callable( $tab['callback'] ) ): print_r( $tab ); ?>

							<?php echo call_user_func_array( $tab['callback'], array( $key, $tab, $user ) ); ?>

						<?php else: ?>
                            xx
							<?php do_action( 'learn-press/profile-tab-callback', $key, $tab, $user ); ?>
                            yy
						<?php endif; ?>
                    </div>
                </div>
			<?php } ?>
		<?php endforeach; ?>
    </div>

</div>
