<?php
/**
 * Template for displaying Continue button in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/buttons/continue.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$user = learn_press_get_current_user();
?>

<form name="continue-course" class="continue-course form-button lp-form" action="" style="display:none">
	<button type="submit" class="lp-button button">
		<?php echo esc_html( apply_filters( 'learn-press/continue-course-button-text', esc_html__( 'Continue', 'learnpress' ) ) ); ?>
 	</button>
</form>
