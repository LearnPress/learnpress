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

$user = LP_Global::user();
?>

<form name="continue-course" class="continue-course form-button lp-form" method="post" action="<?php echo esc_url( $user->get_current_item( get_the_ID(), true ) ); ?>">
	<button type="submit" class="lp-button button">
		<?php esc_html_e( 'Continue', 'learnpress' ); ?>
	</button>
</form>
