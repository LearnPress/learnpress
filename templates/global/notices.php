<?php
/**
 * Template for displaying all notices from queue.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/notices.php.
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

<?php if ( ! $notices ) {
	return;
} ?>

<ul class="learn-press-error">

	<?php foreach ( $notices as $notice ) { ?>

        <li><?php echo wp_kses_post( $notice ); ?></li>

	<?php } ?>

</ul>
