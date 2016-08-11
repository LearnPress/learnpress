<?php
/**
 * Template for displaying all error messages from queue
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( ! $messages ){
	return;
}

?>

<ul class="learn-press-message error">
	<?php foreach ( $messages as $message ) : ?>
		<li><?php echo /*wp_kses_post*/( $message ); ?></li>
	<?php endforeach; ?>
</ul>