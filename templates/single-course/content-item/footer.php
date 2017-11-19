<?php
/**
 * Template for displaying footer of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/footer.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
$course = LP_Global::course();
$user   = LP_Global::user();
?>

<div id="course-item-content-footer">

    <form class="lp-form form-button" action="<?php echo $course->get_permalink(); ?>">
        <button
                class="lp-button"><?php _e( 'Back to Course', 'learnpress' ); ?></button>
    </form>

	<?php if ( $user->can_finish_course( $course->get_id() ) ) { ?>

		<?php learn_press_get_template( 'single-course/buttons/finish.php' ); ?>

	<?php } ?>

</div>