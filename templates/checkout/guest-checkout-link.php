<?php
/**
 * Template for displaying link to show form for Guest checkout.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! LearnPress::instance()->checkout()->is_enable_guest_checkout() ) {
	return;
}

esc_html_e( 'Or quick checkout as', 'learnpress' ); ?>

<a href="<?php echo esc_url( LP_Helper::get_link_no_cache( learn_press_get_page_link( 'checkout' ) ) ); ?>">
	<?php echo esc_html_x( 'Guest', 'checkout guest link', 'learnpress' ); ?>
</a>.
