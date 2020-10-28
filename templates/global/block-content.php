<?php
/**
 * Template for displaying block lesson content.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-lesson/block-content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();
?>

<div class="learn-press-content-protected-message content-item-block">
	<?php esc_html_e( 'Content of this item has blocked because the course has exceeded duration.', 'learnpress' ); ?>
</div>
