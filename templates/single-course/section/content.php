<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/* section item display inside a section */
$learnpress_course_sections = learn_press_get_course_sections();
?>

<ul class="section-content">

	<?php if ( !empty( $section->items ) ) { ?>

	<?php
	foreach ( $section->items as $item ) {
		$post_type = str_replace( 'lp_', '', $item->post_type );

		if ( ! in_array( $item->post_type, $learnpress_course_sections ) ) continue;

		$args = array(
			'item'    => $item,
			'section' => $section
		);
		learn_press_get_template( "single-course/section/item-{$post_type}.php", $args );
	}
	?>
	<?php } else { ?>

		<li class="course-item section-empty"><?php learn_press_display_message( __( 'No items in this section', 'learnpress' ) );?></li>

	<?php } ?>
</ul>
