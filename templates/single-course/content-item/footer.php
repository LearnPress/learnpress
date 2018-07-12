<?php
/**
 * Template for displaying footer of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/footer.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.1.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
$user   = LP_Global::user();

ob_start();
do_action( 'learn-press/content-item-footer' );
$content = ob_get_clean();

if ( ! $content ) {
	return;
}
?>
<div id="course-item-content-footer">

	<?php echo $content; ?>

</div>