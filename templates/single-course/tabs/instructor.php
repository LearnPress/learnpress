<?php
/**
 * Template for displaying instructor of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/instructor.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.3.1
 */

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

defined( 'ABSPATH' ) || exit();

$course = learn_press_get_course();
if ( ! $course ) {
	return;
}
/**
 * @var LP_User $instructor
 */
$instructor               = $course->get_instructor();
$singleInstructorTemplate = SingleInstructorTemplate::instance();
?>

<div class="course-author">

	<?php do_action( 'learn-press/before-single-course-instructor' ); ?>

	<div class="lp-course-author">
		<div class="course-author__pull-left">
			<?php echo wp_kses_post( $instructor->get_profile_picture() ); ?>
		</div>

		<div class="course-author__pull-right">
			<h4 class="author-title"><?php echo wp_kses_post( $course->get_instructor_html() ); ?></h4>
			<?php
				echo $singleInstructorTemplate->html_social( $instructor );
			?>
			<div class="author-description">

				<?php
				/**
				 * LP Hook
				 *
				 * @since 4.0.0
				 */
				do_action( 'learn-press/begin-course-instructor-description', $instructor );

				echo wp_kses_post( $instructor->get_description() );

				/**
				 * LP Hook
				 *
				 * @since 4.0.0
				 */
				do_action( 'learn-press/end-course-instructor-description', $instructor );

				?>
			</div>

			<?php
			/**
			 * LP Hook
			 *
			 * @since 4.0.0
			 */
			do_action( 'learn-press/after-course-instructor-description', $instructor );
			?>

			<?php

			/**
			 * LP Hook
			 *
			 * @since 4.0.0
			 */
			do_action( 'learn-press/after-course-instructor-socials', $instructor );

			?>
		</div>
	</div>
	<?php do_action( 'learn-press/after-single-course-instructor' ); ?>

</div>
