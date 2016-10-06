<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<?php if ( learn_press_is_course() ): ?>
	<div id="lp-single-course" class="lp-single-course">
<?php else: ?>
	<div id="lp-archive-courses" class="lp-archive-courses">
<?php endif; ?>
