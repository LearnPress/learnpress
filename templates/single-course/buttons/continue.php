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

<form name="continue-course" class="continue-course form-button lp-form" method="post" action="" style="display:none">
	<button type="submit" class="lp-button button">
		<?php esc_html_e( 'Continue', 'learnpress' ); ?>
	</button>
</form>
