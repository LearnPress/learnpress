<?php
/**
 * Template for displaying loop course of section.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/loop-section.php.
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
 * @var LP_Course_Item    $item
 */
if ( ! isset( $section ) ) {
	return;
}

$course = learn_press_get_the_course();

/**
 * Allow filter to hide this section in curriculum.
 *
 * @since 3.x.x
 */
if ( ! apply_filters( 'learn-press/section-visible', true, $section, $course ) ) {
	return;
}

$user        = learn_press_get_current_user();
$user_course = $user->get_course_data( get_the_ID() );
$items       = $section->get_items();

?>

<li<?php $section->main_class(); ?> id="section-<?php echo $section->get_slug(); ?>"
                                    data-id="<?php echo $section->get_slug(); ?>"
                                    data-section-id="<?php echo $section->get_id(); ?>">

	<?php
	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/before-section-summary', $section, $course->get_id() );
	?>

    <div class="section-header">

        <div class="section-left">

            <h5 class="section-title">
				<?php
				if ( ! $title = $section->get_title() ) {
					$title = _x( 'Untitled', 'template title empty', 'learnpress' );
				}
				echo $title;
				?>

				<?php if ( $description = $section->get_description() ) { ?>
                    <p class="section-desc"><?php echo $description; ?></p>
				<?php } ?>
            </h5>

            <span class="section-toggle">
                <i class="fas fa-caret-down"></i>
	            <i class="fas fa-caret-up"></i>
            </span>
        </div>

		<?php if ( $user->has_enrolled_course( $section->get_course_id() ) ) { ?>

			<?php $percent = $user_course->get_percent_completed_items( '', $section->get_id() ); ?>

            <div class="section-meta">
                <div class="learn-press-progress"
                     title="<?php echo esc_attr( sprintf( __( 'Section progress %s%%', 'learnpress' ), round( $percent, 2 ) ) ); ?>">
                    <div class="learn-press-progress__active" data-value="<?php echo $percent; ?>"></div>
					<?php //learn_press_circle_progress_html( $percent, 24, 6 ); ?>
                </div>
            </div>

		<?php } ?>

    </div>

	<?php

	do_action( 'learn-press/before-section-content', $section, $course->get_id() );

	if ( ! $items ) {
		learn_press_display_message( __( 'No items in this section', 'learnpress' ) );
	} else {
		?>
        <ul class="section-content">

			<?php
			$i = 1;
			foreach ( $items as $item ) { ?>

				<?php
				if ( $item->is_visible() ) {
					?>
                    <li class="<?php echo join( ' ', $item->get_class() ); ?>" data-id="<?php echo $item->get_id(); ?>">
						<?php
						/**
						 * @since 3.0.0
						 */
						do_action( 'learn-press/before-section-loop-item', $item, $section, $course );

						$item_link = $user->can_view_item( $item->get_id() ) ? $item->get_permalink() : 'javascript:void(0);';

						?>
                        <span><?php echo $i; ?></span>
                        <a class="section-item-link"
                           href="<?php echo apply_filters( 'learn-press/section-item-permalink', $item_link, $item, $section, $course ); ?>">

							<?php

							/**
							 * @since 3.x.x
							 */
							do_action( 'learn-press/before-section-loop-item-title', $item, $section, $course );

							learn_press_get_template(
								'single-course/section/' . $item->get_template(),
								array(
									'item'    => $item,
									'section' => $section
								)
							);

							/**
							 * @since 3.x.x
							 */
							do_action( 'learn-press/after-section-loop-item-title', $item, $section, $course );

							?>
                        </a>

						<?php
						/**
						 * @since 3.0.0
						 */
						do_action( 'learn-press/after-section-loop-item', $item, $section, $course );

						?>
                    </li>
					<?php
				} else { // End if $item->is_visible()

					/**
					 * @since 3.x.x
					 */
					do_action( 'learn-press/section-loop-item-invisible', $item, $section, $course );
				}
				?>

				<?php
				$i ++;
			} // End foreach $items ?>

        </ul>

		<?php
	}

	/**
	 * @since 3.x.x
	 */
	do_action( 'learn-press/after-section-summary', $section, $course->get_id() );
	?>

</li>