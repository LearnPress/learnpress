<?php
/**
 * Template for displaying comments of a course item.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( comments_open() || get_comments_number() ) {
	?>
	<div id="learn-press-item-comments">
		<div class="learn-press-comments">
			<?php comments_template(); ?>
		</div>
	</div>
<?php } ?>
