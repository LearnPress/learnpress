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
?>

<ul class="section-content">

	<?php if ( $items = $section->get_items() ) { ?>

		<?php foreach ( $items as $item ) {?>
            <li class="<?php echo join( ' ', $item->get_class() ); ?>">
                <a href="<?php echo $item->get_permalink(); ?>">
					<?php

					if ( ! $item->is_visible() ) {
						continue;
					}

					$args = array(
						'item'    => $item,
						'section' => $section
					);

					$item_type = $item->get_item_type();

					$template_type = apply_filters( 'learn-press/section-item-template', 'item-' . str_replace( 'lp_', '', $item_type ), $item_type );
					learn_press_get_template( "single-course/section/{$template_type}.php", $args );
					?>
                    <i class="item-icon icon-lock dashicons dashicons-lock"></i>
                </a>
            </li>
		<?php } ?>

	<?php } else { ?>

        <li class="course-item section-empty"><?php learn_press_display_message( __( 'No items in this section', 'learnpress' ) ); ?></li>

	<?php } ?>
</ul>
