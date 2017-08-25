<?php
/**
 * Template for displaying content of learning course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $lp_course, $lp_course_item;

?>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_before_content_learning' );
?>
<div class="course-learning-summary">

	<?php
	/**
	 * @deprecated
	 */
	do_action( 'learn_press_content_learning_summary' );

	/**
	 * @since 3.x.x
	 *
	 * @see   learn_press_course_meta_start_wrapper()
	 */
	do_action( 'learn-press/content-learning-summary' );

	?>

</div>

<?php
/**
 * @deprecated
 */
do_action( 'learn_press_after_content_learning' );
?>

