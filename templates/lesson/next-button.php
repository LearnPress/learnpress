<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */
defined( 'ABSPATH' ) || exit();
?>
<div class="course-item-next">
	<span><?php _e( 'Next', 'learnpress' ); ?></span>
	<a data-id="<?php echo $item;?>" href="<?php echo $course->get_item_link( $item ); ?>"><?php echo get_the_title( $item ); ?></a>
</div>