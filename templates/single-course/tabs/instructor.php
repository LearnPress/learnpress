<?php
/**
 * Template for displaying instructor of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/instructor.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.3.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

/**
 * @var LP_Course $course
 * @var LP_User   $instructor
 *
 */
$course     = LP_Global::course();
$instructor = $course->get_instructor();
?>

<div class="course-author">

	<?php do_action( 'learn-press/before-single-course-instructor' ); ?>

    <div class="course-author__pull-left">
		<?php echo $instructor->get_profile_picture(); ?>

        <div class="course-author__meta">

			<?php

			if ( $author_meta = $instructor->get_profile_meta() ) {
				foreach ( $author_meta as $key => $value ) {
					?>
                    <span class="course-author__meta-row <?php echo $key; ?>"><?php echo $value; ?></span>
					<?php
				}
			}

			?>

        </div>
    </div>

    <div class="course-author__pull-right">
        <div class="author-title"><?php echo $course->get_instructor_html(); ?></div>
        <div class="author-description">
			<?php echo $instructor->get_description(); ?>
        </div>
    </div>

	<?php do_action( 'learn-press/after-single-course-instructor' ); ?>

</div>