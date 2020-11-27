<?php
/**
 * Template for displaying footer of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/footer.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
$user   = LP_Global::user();
?>

<div id="popup-footer">
	<?php do_action( 'learn-press/popup-footer' ); ?>
</div>
