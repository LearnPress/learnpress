<?php
/**
 * Template for displaying footer content in course popup.
 *
 * @author  ThimPress
 * @package LearnPress
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

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