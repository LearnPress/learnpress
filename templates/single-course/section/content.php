<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<ul class="section-content">

	<?php if ( !empty( $section->items ) ) { ?>

	<?php
	foreach ( $section->items as $item ) {
		$post_type = str_replace( 'lp_', '', $item->post_type );
		if ( !in_array( $post_type, array( 'lesson', 'quiz', 'assignment' ) ) ) continue;
		$args = array(
			'item'    => $item,
			'section' => $section
		);
		/*switch( $item->post_type ){
			case LP()->lesson_post_type:
				$GLOBALS['lesson'] = LP_Lesson::get_lesson( $item );
				break;
			case LP()->quiz_post_type:
				$GLOBALS['quiz'] = LP_Quiz::get_quiz( $item );
				break;
			default:
				do_action( 'learn_press_section_setup_loop_item', $item, $section );
		}*/
		learn_press_get_template( "single-course/section/item-{$post_type}.php", $args );
	}
	?>
	<?php } else { ?>

		<li class="course-item section-empty"><?php learn_press_display_message( __( 'No items in this section', 'learn_press' ) );?></li>

	<?php } ?>
</ul>
