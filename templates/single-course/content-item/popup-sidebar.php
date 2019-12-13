<?php
/**
 * Template for displaying course currciulum in popup
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;
?>
<div id="popup-sidebar">
    <form method="post" class="search-course">
        <input type="text" name="s" autocomplete="off"
               placeholder="<?php echo esc_attr( _x( 'Search courses content', 'search course input placeholder', 'learnpress' ) ); ?>">
        <button name="submit"></button>
        <button type="button" class="clear"></button>
    </form>

	<?php

	/**
	 * Get course curriculum sections
	 */
	LP()->template( 'course' )->course_curriculum();

	?>
</div>
