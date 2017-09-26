<?php
/**
 * Template for displaying form to search courses
 *
 * @package LearnPress/Templates
 * @author  ThimPress
 * @version 2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! ( learn_press_is_courses() || learn_press_is_search() ) ) {
	return;
}
?>
<form method="get" name="search-course" class="learn-press-search-course-form"
      action="<?php echo learn_press_get_page_link( 'courses' ); ?>">
    <input type="text" name="s" class="search-course-input" value="<?php echo $s; ?>"
           placeholder="<?php _e( 'Search course...', 'learnpress' ); ?>"/>
    <input type="hidden" name="ref" value="course"/>
    <button class="search-course-button"><?php _e( 'Search', 'learnpress' ); ?></button>
</form>
