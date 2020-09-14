<?php
/**
 * Template for displaying all error messages from queue.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/notices/error.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! $messages ) {
	return;
}
?>

<ul class="learn-press-message error">
	<?php foreach ( $messages as $message ) : ?>
		<li><?php echo $message; ?></li>
	<?php endforeach; ?>
</ul>
