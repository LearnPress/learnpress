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

global $lp_user;

if ( $items = $section->get_items() ) { ?>

    <ul class="section-content">

		<?php foreach ( $items as $item ) { ?>
            <li class="<?php echo join( ' ', $item->get_class() ); ?>">

				<?php
				/**
				 * @since 3.x.x
				 */
				do_action( 'learn-press/begin-section-loop-item', $item );
				?>

                <a href="<?php echo $item->get_permalink(); ?>">
					<?php

					if ( ! $item->is_visible() ) {
						continue;
					}

					$args = array(
						'item'    => $item,
						'section' => $section
					);

					/**
					 * @since 3.x.x
					 */
					do_action( 'learn-press/before-section-loop-item', $item );

					learn_press_get_template( "single-course/section/" . $item->get_template(), $args );

					/**
					 * @since 3.x.x
					 *
					 * @see   learn_press_section_item_meta()
					 */
					do_action( 'learn-press/after-section-loop-item', $item, $section );

					?>
                </a>

				<?php
				/**
				 * @since 3.x.x
				 */
				do_action( 'learn-press/end-section-loop-item', $item );
				?>

            </li>
		<?php } ?>
    </ul>
<?php } else { ?>

    <?php learn_press_display_message( __( 'No items in this section', 'learnpress' ) ); ?>

<?php } ?>