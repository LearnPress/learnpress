<?php
/**
 * Template for displaying comments of a course item.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;
?>
<input type="checkbox" id="learn-press-item-comments-toggle">
<div id="learn-press-item-comments">
	<label for="learn-press-item-comments-toggle">Comments</label>
	<div class="learn-press-comments">
		<!-- Place holder for loading comments -->
	<?php
	//comments_template();
	?>
	</div>
</div>
