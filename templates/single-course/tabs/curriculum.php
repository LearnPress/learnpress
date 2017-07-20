<?php
/**
 * Template for displaying the curriculum of a course
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;
?>
<div class="course-curriculum" id="learn-press-course-curriculum">

	<?php

	/**
	 * @deprecated
	 */
	do_action( 'learn_press_before_single_course_curriculum' );

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/before-single-course-curriculum' );

	?>

	<?php if ( $curriculum = $course->get_curriculum() ): ?>

        <ul class="curriculum-sections">
			<?php
            foreach ( $curriculum as $section ) {
	            learn_press_get_template( 'single-course/loop-section.php', array( 'section' => $section ) );
            }
            ?>
        </ul>

	<?php else: ?>

		<?php echo apply_filters( 'learn_press_course_curriculum_empty', __( 'Curriculum is empty', 'learnpress' ) ); ?>

	<?php endif; ?>

	<?php

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/after-single-course-curriculum' );

	/**
	 * @deprecated
	 */
    do_action( 'learn_press_after_single_course_curriculum' );
    ?>

</div>