<?php
/**
 * User Information
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_query;

$tabs         = learn_press_user_profile_tabs( $user );
$current      = learn_press_get_current_profile_tab();
$profile_link = learn_press_get_page_link( 'profile' );
if ( !empty( $tabs ) && !empty( $tabs[$current] ) ) : ?>
	<div class="user-info" id="learn-press-user-info">
		<div class="user-basic-info">
			<span class="user-avatar"><?php echo get_avatar( $user->ID ); ?></span>
			<strong class="user-nicename"><?php echo $user->user_nicename; ?></strong>
			<?php if ( $description = get_user_meta( $user->id, 'description', true ) ): ?>
				<p class="user-bio"><?php echo get_user_meta( $user->id, 'description', true ); ?></p>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>