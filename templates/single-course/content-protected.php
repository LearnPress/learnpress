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

<div class="learn-press-content-protected-message">

    <span class="icon"></span>

	<?php
    if( $can_view_item && $can_view_item == 'not-enrolled' ){
	    echo apply_filters( 'learn_press_content_item_protected_message',
		    __( 'This content is protected, please enroll course to view this content!', 'learnpress' ) );
	    learn_press_course_enroll_button();
    } else{
	    echo apply_filters( 'learn_press_content_item_protected_message',
		    sprintf( __( 'This content is protected, please <a href="%s">login</a> and enroll course to view this content!', 'learnpress' ), learn_press_get_login_url( learn_press_get_current_url() ) ) );

    }
    ?>

</div>