<?php
/**
 * Created by PhpStorm.
 * User: tu
 * Date: 9/19/19
 * Time: 2:34 PM
 */
?>
<div id="popup-sidebar">
    <form method="post" class="search-course">
        <input type="text" name="s" autocomplete="off"
               placeholder="<?php echo esc_attr( _x( 'Search course...', 'search course input placeholder', 'learnpress' ) ); ?>">
        <button name="submit"></button>
        <button type="button" class="clear"></button>
    </form>
	<?php

	LP()->template()->course_curriculum();

	?>
</div>
