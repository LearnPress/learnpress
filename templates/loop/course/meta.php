<?php
/**
 * Template for displaying meta data in course loop.
 *
 * @version 4.x.x
 *
 * @author ThimPress
 * @package LearnPress/Templates
 */

defined('ABSPATH') or die;

global $post;
?>

<div class="course-categories">
	<?php echo get_the_term_list( '', 'course_category', sprintf( '<span>%s</span>', __( 'in', 'learnpress' ) ), '|', '' ) ?>
</div>

<div class="course-tags">
	<?php echo get_the_term_list( '', 'course_tag', '', '', '' ); ?>
</div>

<div class="course-excerpt"><?php echo wp_trim_words( $post->post_content, 15 ); ?></div>