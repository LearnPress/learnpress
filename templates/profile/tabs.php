<?php
/**
 * User Profile tabs
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

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
<div id="learn-press-profile-nav">

    <?php do_action('learn-press/before-profile-nav', $user);?>

    <ul class="learn-press-tabs tabs">
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<?php
//		if ( ! current_user_can('lp-view-profile-' . $slug, $user ) ) {
//			continue;
//		}
			if ( array_key_exists( 'hidden', $tab ) && $tab['hidden'] ) {
				continue;
			}
			?>
            <li class="<?php echo esc_attr( $key ); ?>_tab<?php echo $current == $key ? ' active' : ''; ?>">
				<?php
				$link = learn_press_user_profile_link( $user->get_id(), $key === '' ? false : $key );
				?>
                <a href="<?php echo esc_url( $link ); ?>"
                   data-slug="<?php echo esc_attr( $link ); ?>"><?php echo apply_filters( 'learn_press_profile_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>
            </li>
		<?php endforeach; ?>
    </ul>

	<?php do_action('learn-press/after-profile-nav', $user);?>

</div>