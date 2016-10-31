<?php
/**
 * Template for displaying form to search courses
 *
 * @package LearnPress/Templates
 * @author  ThimPress
 * @version 2.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<form method="get" name="search-course" class="learn-press-search-course-form">
	<input type="text" name="s" class="search-course-input" value="<?php echo $s; ?>" placeholder="<?php _e( 'Search course...', 'learnpress' ); ?>" />
	<input type="hidden" name="ref" value="course" />
	<button class="search-course-button"><?php _e( 'Search', 'learnpress' ); ?></button>
</form>
