<?php
/**
 * Template for displaying comments of a course item.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<input type="checkbox" id="learn-press-item-comments-toggle">
<div id="learn-press-item-comments">
	<label for="learn-press-item-comments-toggle"><?php esc_html_e( 'Comments', 'learnpress' ); ?></label>

	<div class="learn-press-comments">
	<?php
	comments_template();
	?>
	</div>
</div>
