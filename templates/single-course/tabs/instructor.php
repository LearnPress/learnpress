<?php
/**
 * Template for displaying instructor of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/instructor.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
?>

<div class="course-author">

    <!--    <h3>--><?php //_e( 'Instructor', 'learnpress' ); ?><!--</h3>-->

	<?php do_action( 'learn-press/before-single-course-instructor' ); ?>

    <p class="author-name">
		<?php echo $course->get_instructor()->get_profile_picture(); ?>
    </p>

    <div class="author-bio">
        <div class="author-title"><?php echo $course->get_instructor_html(); ?></div>
        <div class="author-description">
			<?php echo $course->get_author()->get_description(); ?>
        </div>
    </div>

	<?php do_action( 'learn-press/after-single-course-instructor' ); ?>

</div>