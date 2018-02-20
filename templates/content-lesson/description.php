<<<<<<< HEAD
<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

$course = LP()->global['course'];
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$item = LP()->global['course-item'];
if ( !$item ) {
	return;
}
?>
<div class="course-lesson-description">

	<?php if ( $the_content = apply_filters( 'learn_press_course_lesson_content', $item->get_content() ) ): ?>

		<?php echo $the_content; ?>


	<?php else: ?>

		<?php learn_press_display_message( __( 'This lesson has no content', 'learnpress' ) ); ?>

	<?php endif; ?>
		<div style="text-align: center;" class="img-responsive aligncenter"><?php echo $item->get_image() ?></div>
</div>
=======
<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

$course = LP()->global['course'];
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$item = LP()->global['course-item'];
if ( !$item ) {
	return;
}
?>
<div class="course-lesson-description">

	<?php if ( $the_content = apply_filters( 'learn_press_course_lesson_content', $item->get_content() ) ): ?>

		<?php echo $the_content; ?>

	<?php else: ?>

		<?php learn_press_display_message( __( 'This lesson has no content', 'learnpress' ) ); ?>

	<?php endif; ?>

</div>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
