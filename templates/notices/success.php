<<<<<<< HEAD
<?php
/**
 * Template for displaying all success messages from queue
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !$messages ) {
	return;
}

?>

<?php foreach ( $messages as $message ) : ?>
	<div class="learn-press-message">
		<?php echo /*wp_kses_post*/( $message ); ?>
	</div>
<?php endforeach; ?>
=======
<?php
/**
 * Template for displaying all success messages from queue
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !$messages ) {
	return;
}

?>

<?php foreach ( $messages as $message ) : ?>
	<div class="learn-press-message">
		<?php echo /*wp_kses_post*/( $message ); ?>
	</div>
<?php endforeach; ?>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
