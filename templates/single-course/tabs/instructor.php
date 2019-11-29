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

        <?php if( $socials = $instructor->get_profile_socials()) { ?>
            <div class="author-socials">
                <?php echo join( '', $socials ); ?>
            </div>
        <?php } ?>

    </div>

    <div class="course-author__pull-right">
        <div class="author-title"><?php echo $course->get_instructor_html(); ?></div>
        <div class="author-description margin-bottom">
			<?php

			/**
			 * LP Hook
			 *
			 * @since 4.0.0
			 */
			do_action( 'learn-press/begin-course-instructor-description', $instructor );

			echo $instructor->get_description();

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

	<?php do_action( 'learn-press/after-single-course-instructor' ); ?>

</div>