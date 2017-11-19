<?php
/**
 * Template for displaying content and items of section in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/section/content.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php if ( ! isset( $section ) ) {
	return;
} ?>

<?php global $lp_user; ?>

<?php if ( $items = $section->get_items() ) { ?>

    <ul class="section-content">

		<?php foreach ( $items as $item ) { ?>

            <li class="<?php echo join( ' ', $item->get_class() ); ?>">

				<?php if ( ! $item->is_visible() ) {
					continue;
				}

				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/begin-section-loop-item', $item );
				?>

                <a href="<?php echo $item->get_permalink(); ?>">

					<?php $args = array( 'item' => $item, 'section' => $section );

					/**
					 * @since 3.0.0
					 */
					do_action( 'learn-press/before-section-loop-item', $item );

					learn_press_get_template( "single-course/section/" . $item->get_template(), $args );

					/**
					 * @since 3.0.0
					 *
					 * @see   learn_press_section_item_meta()
					 */
					do_action( 'learn-press/after-section-loop-item', $item, $section );
					?>

                </a>

				<?php
				/**
				 * @since 3.0.0
				 */
				do_action( 'learn-press/end-section-loop-item', $item );
				?>

            </li>

		<?php } ?>

    </ul>

<?php } else { ?>

	<?php learn_press_display_message( __( 'No items in this section', 'learnpress' ) ); ?>

<?php } ?>