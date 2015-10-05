<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<?php do_action( 'learn_press_before_course_landing_content' ); ?>

<div id="course-landing">
	<?php do_action( 'learn_press_course_landing_content' ); ?>
</div>

<?php do_action( 'learn_press_after_course_landing_content' ); ?>