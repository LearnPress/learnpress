<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( empty( $section->items ) ) {
	return;
}
?>
<ul class="section-content">
	<?php
	foreach ( $section->items as $item ) {
		$post_type = str_replace( 'lp_', '', $item->post_type );
		if ( !in_array( $post_type, array( 'lesson', 'quiz', 'assignment' ) ) ) continue;
		$args = array(
			'item'    => $item,
			'section' => $section
		);
		learn_press_get_template( "single-course/section/item-{$post_type}.php", $args );
	}
	?>
</ul>