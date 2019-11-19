<?php
/**
 * Template for displaying categories of course in primary-meta section.
 *
 * @version 4.0.0
 * @author  ThimPress
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) or die;
?>
<div class="meta-item meta-item-categories">
    <div class="meta-item__value">
        <label><?php esc_html_e( 'Category', 'learnpress' ); ?></label>
        <div>
			<?php echo get_the_term_list( get_the_ID(), 'course_category', '', '<span>|</span>' ); ?>
        </div>
    </div>
</div>