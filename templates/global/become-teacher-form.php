<?php
/**
 * Template for displaying the form let user fill out their information to become a teacher.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/become-teacher-form.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();
?>

<div id="learn-press-become-teacher-form" class="become-teacher-form learn-press-form">

	<form name="become-teacher-form" method="post" enctype="multipart/form-data" action="">

		<?php do_action( 'learn-press/before-become-teacher-form' ); ?>

		<?php do_action( 'learn-press/become-teacher-form' ); ?>

		<?php do_action( 'learn-press/after-become-teacher-form' ); ?>

	</form>

</div>
