<?php
/**
 * Template for displaying content of course without header and footer
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit();

/**
 * If course has set password
 */
if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}

/**
 * LP Hook
 */
do_action( 'learn-press/before-single-course' );

?>
<div id="learn-press-course" class="course-summary">
	<?php
	/**
	 * @since 3.0.0
	 *
	 * @called single-course/content.php
	 * @called single-course/sidebar.php
	 */
	do_action( 'learn-press/single-course-summary' );
	?>
</div>
<?php

/**
 * LP Hook
 */
do_action( 'learn-press/after-single-course' );
