<?php
/**
 * Template for displaying curriculum tab of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tabs/curriculum.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
?>

<div class="course-curriculum" id="learn-press-course-curriculum">
	<div class="curriculum-scrollable">

		<?php do_action( 'learn-press/before-single-course-curriculum' ); ?>

		<?php $curriculum = $course->get_curriculum(); ?>

		<?php if ( $curriculum ) : ?>
			<ul class="curriculum-sections">
				<?php
				foreach ( $curriculum as $section ) {
					learn_press_get_template( 'single-course/loop-section.php', array( 'section' => $section ) );
				}
				?>
			</ul>

		<?php else : ?>
			<?php echo apply_filters( 'learn_press_course_curriculum_empty', esc_html__( 'Curriculum is empty', 'learnpress' ) ); ?>
		<?php endif ?>

		<?php do_action( 'learn-press/after-single-course-curriculum' ); ?>

	</div>
</div>
