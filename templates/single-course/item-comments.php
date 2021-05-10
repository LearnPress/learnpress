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

<a class="lp-lesson-comment-btn" href="#" data-close="<?php esc_attr_e( 'Close comments', 'learnpress' ); ?>"><?php esc_html_e( 'View comments', 'learnpress' ); ?></a>

<div id="learn-press-item-comments">
	<div class="learn-press-comments">
		<?php comments_template(); ?>
	</div>
</div>
