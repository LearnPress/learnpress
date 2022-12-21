<?php
/**
 * Template for displaying course level in secondary section.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 * @see LP_Template_Course::count_object()
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $object ) || ! isset( $count ) ) {
	return;
}
?>

<div class="meta-item meta-item-<?php echo esc_attr( $object ); ?>"><?php echo wp_kses_post( $count ); ?></div>

