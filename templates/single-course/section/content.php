<?php
/**
 * Template for displaying content and items of section in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/section/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

/**
 * @var LP_Course_Section $section
 */
if ( ! isset( $section ) ) {
	return;
}

$user  = LP_Global::user();
$items = $section->get_items();
?>

<?php if ( $items ) { ?>

	<ul class="section-content">

		<?php foreach ( $items as $item ) { ?>

			<li class="<?php echo join( ' ', $item->get_class() ); ?>">
				<?php
				if ( $item->is_visible() ) {
					/**
					 * @since 3.0.0
					 */
					do_action( 'learn-press/begin-section-loop-item', $item );
					?>
					<a class="section-item-link" href="<?php echo $item->get_permalink(); ?>">
						<?php
						learn_press_get_template( 'single-course/section/content-item.php',
							array(
								'item'    => $item,
								'section' => $section
							)
						);
						?>
					</a>
					<?php
					/**
					 * @since 3.0.0
					 */
					do_action( 'learn-press/end-section-loop-item', $item );
				}
				?>

			</li>

		<?php } ?>

	</ul>

<?php } else { ?>

	<?php learn_press_display_message( __( 'No items in this section', 'learnpress' ) ); ?>

<?php } ?>