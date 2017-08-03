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
global $wp;
if ( ! isset( $user ) ) {
	$user = learn_press_get_current_user();
}

$profile = LP_Profile::instance( $user->get_id() );
$tabs    = $profile->get_tabs();
$current = learn_press_get_current_profile_tab();
?>
<div id="learn-press-profile-nav">

	<?php do_action( 'learn-press/before-profile-nav', $user ); ?>

    <ul class="learn-press-tabs tabs">
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<?php
//		if ( ! current_user_can('lp-view-profile-' . $slug, $user ) ) {
//			continue;
//		}
			if ( array_key_exists( 'hidden', $tab ) && $tab['hidden'] ) {
				continue;
			}

			$link = learn_press_user_profile_link( $user->get_id(), $key === '' ? false : $key );
			if ( empty( $tab['sections'] ) ) {
				$js        = '';
				$main_link = $link;
			} else {
				$js        = 'onmouseover="jQuery(this).find(\'ul\').show(); return false;" onmouseleave="jQuery(this).find(\'ul\').toggle(jQuery(this).hasClass(\'active\'))"';
				$main_link = 'javascript:void(0)';
			}

			?>
            <li class="<?php echo esc_attr( $key ); ?>_tab<?php echo $current == $key ? ' active' : ''; ?>" <?php echo $js; ?>>
				<?php

				?>
                <a href="<?php echo esc_url( $main_link ); ?>"
                   data-slug="<?php echo esc_attr( $main_link ); ?>" ><?php echo apply_filters( 'learn_press_profile_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?></a>

				<?php if ( ! empty( $tab['sections'] ) ) {

					$sections  = array();
					$is_active = false;
					foreach ( $tab['sections'] as $section => $section_data ) {

						$class = ! empty( $wp->query_vars['section'] ) && $wp->query_vars['section'] == $section ? 'active' : '';
						if ( $class && ! $is_active ) {
							$is_active = true;
						}
						$sections[] = '<li class="' . $class . '"><a href="' . $link . $section . '/">' . $section_data['title'] . '</a></li>';
					}
					echo '<ul' . ( ! $is_active ? ' style="display: none;"' : '' ) . '>';
					echo join( "\n", $sections );

					echo '</ul>';

				} ?>
            </li>
		<?php endforeach; ?>
    </ul>

	<?php do_action( 'learn-press/after-profile-nav', $user ); ?>

</div>