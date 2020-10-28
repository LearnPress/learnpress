<?php
/**
 * Template for displaying Continue button in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/buttons/continue.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
?>

<form name="course-external-link" class="course-external-link form-button lp-form" method="post">

	<input type="hidden" name="lp-ajax" value="external-link">
	<input type="hidden" name="id" value="<?php echo esc_attr( $course->get_id() ); ?>">
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'external-link-' . $course->get_external_link() ); ?>">
	<button type="submit" class="lp-button button"><?php echo esc_html( $course->get_external_link_text() ); ?></button>

</form>
