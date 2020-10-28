<?php
/**
 * Template for displaying meta data in course loop.
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

global $post;
?>

<div class="course-excerpt"><?php echo wp_trim_words( $post->post_content, 15 ); ?></div>
