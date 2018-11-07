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

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php if ( ! $messages ) {
	return;
} ?>

<ul class="learn-press-message error">

	<?php foreach ( $messages as $message ) { ?>

        <li><?php echo $message; ?></li>

	<?php } ?>

</ul>