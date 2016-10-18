<?php
/**
 * User Courses enrolled
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
?>
<li>

	<?php do_action( 'learn_press_before_profile_tab_' . $subtab . '_loop_course' ); ?>

	<?php learn_press_get_template_part('content', 'course');?>

	<?php do_action( 'learn_press_after_profile_tab_' . $subtab . '_loop_course' ); ?>

</li>