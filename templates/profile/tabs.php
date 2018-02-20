<?php
/**
 * User Profile tabs
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
$current = learn_press_get_current_profile_tab();
?>
<ul class="tabs learn-press-tabs clearfix">
	<?php foreach ( $tabs as $key => $tab ) : ?>
		<?php
		if ( !learn_press_current_user_can_view_profile_section( $key, $user ) ) {
			continue;
		}
		?>
		<li class="<?php echo esc_attr( $key ); ?>_tab<?php echo $current == $key ? ' current' : ''; ?>">
			<?php
			$link = learn_press_user_profile_link( $user->id, $key );
			?>
			<a href="<?php echo esc_url( $link ); ?>" data-slug="<?php echo esc_attr( $link ); ?>"><?php echo apply_filters( 'learn_press_profile_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>
		</li>
	<?php endforeach; ?>
</ul>
<div class="user-profile-tabs learn-press-tabs-wrapper-x">
	<?php foreach ( $tabs as $key => $tab ) : ?>
		<?php if ( $current == $key && learn_press_current_user_can_view_profile_section( $key, $user ) ) { ?>
			<div class="learn-press-tab" id="tab-<?php echo esc_attr( $key ); ?>">
				<div class="entry-tab-inner">
					<?php if ( is_callable( $tab['callback'] ) ): ?>
						<?php echo call_user_func_array( $tab['callback'], array( $key, $tab, $user ) ); ?>
					<?php endif; ?>
				</div>
			</div>
		<?php } ?>
	<?php endforeach; ?>
</div>
<div class="clearfix"></div>
