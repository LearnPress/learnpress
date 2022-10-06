<?php
/**
 * Template for displaying course currciulum in popup
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="popup-sidebar">
	<form method="post" class="search-course">
		<input type="text" name="s" autocomplete="off" placeholder="<?php echo esc_attr_x( 'Search for course content', 'search course input placeholder', 'learnpress' ); ?>">
		<button name="submit"></button>
		<button type="button" class="clear"></button>
	</form>

	<?php LearnPress::instance()->template( 'course' )->course_curriculum(); ?>
</div>
