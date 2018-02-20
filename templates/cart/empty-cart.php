<<<<<<< HEAD
<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

learn_press_display_message( __( 'Your cart is currently empty.', 'learnpress' ), 'error' );

$courses_link = learn_press_get_page_link( 'courses' );
if( !$courses_link ){
	return;
}
?>
=======
<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

learn_press_display_message( __( 'Your cart is currently empty.', 'learnpress' ), 'error' );

$courses_link = learn_press_get_page_link( 'courses' );
if( !$courses_link ){
	return;
}
?>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
<a href="<?php echo learn_press_get_page_link( 'courses' ); ?>"><?php _e( 'Back to class', 'learnpress' ); ?></a>