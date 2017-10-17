<?php
/**
 * Template for displaying the form let user fill out their information to become a teacher
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();
?>
<div id="learn-press-become-teacher-form" class="become-teacher-form learn-press-form">
    <form name="become-teacher-form" method="post"
          enctype="multipart/form-data" action="">

		<?php

		/**
		 *
		 */
		do_action( 'learn-press/before-become-teacher-form' );

		/**
		 *
		 */
		do_action( 'learn-press/become-teacher-form' );

		/**
		 *
		 */
		do_action( 'learn-press/after-become-teacher-form' );
		?>

    </form>
</div>
