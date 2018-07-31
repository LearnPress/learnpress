<?php
/**
 * Template for displaying message for course content protected.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-protected.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<div class="learn-press-message warning has-icon"><?php echo apply_filters( 'learn-press/content-protected-message', __( 'This content is protected, please login and enroll course to view this content!', 'learnpress' ) ); ?></div>

<p>
	<?php
    // Show enroll button if user can enroll
	learn_press_course_enroll_button();

	// Show purchase button if user can purchase
	learn_press_course_purchase_button();
	?>
</p>
