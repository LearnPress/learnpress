<?php
/**
 * Template for displaying link to show form for Guest checkout.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! LearnPress::instance()->checkout()->is_enable_guest_checkout() ) {
	return;
}

esc_html_e( 'Or quick checkout as', 'learnpress' ); ?>

<a href="<?php echo add_query_arg( 'form', 'guest' ); ?>">
	<?php echo esc_html_x( 'Guest', 'checkout guest link', 'learnpress' ); ?>
</a>.
