<?php
/**
 * Display the content of a section including the items.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! isset( $section ) ) {
	return;
}
/* section item display inside a section */
$allow_items = learn_press_get_course_item_types();
?>

<ul class="section-content">

	<?php if ( $items = $section->get_items() ) { ?>

		<?php
		foreach ( $items as $item ) {
			$item_type = get_post_type( $item->get_id() );

			// If item type does not allow
			if ( ! in_array( $item_type, $allow_items ) ) {
				continue;
			}

			$args = array(
				'item'    => $item,
				'section' => $section
			);

			$template_type = apply_filters( 'learn-press/section-item-template', 'item-' . str_replace( 'lp_', '', $item_type ), $item_type );

			learn_press_get_template( "single-course/section/{$template_type}.php", $args );
		}
		?>

	<?php } else { ?>

        <li class="course-item section-empty"><?php learn_press_display_message( __( 'No items in this section', 'learnpress' ) ); ?></li>

	<?php } ?>
</ul>
