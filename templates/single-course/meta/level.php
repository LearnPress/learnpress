<?php
/**
 * Template for displaying course level in secondary section.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$level = learn_press_get_post_level( get_the_ID() );

if ( ! $level ) {
	return;
}
?>

<div class="meta-item meta-item-level"><?php echo esc_html( $level ); ?></div>

