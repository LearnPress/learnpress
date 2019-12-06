<?php
/**
 * Template for displaying user time on course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

/**
 * @var string      $status
 * @var LP_Datetime $start_time
 * @var LP_Datetime $end_time
 * @var LP_Datetime $expiration_time
 */
$time   = current_time( 'mysql', true );
$user   = LP_Global::user();
$course = LP_Global::course();
?>
<div class="course-time">
    <p class="course-time-row">
        <strong><?php esc_html_e( 'You started on:', 'learnpress' ); ?></strong>
		<?php echo $start_time->format( 'i18n' ); ?>
    </p>
	<?php if ( $status === 'enrolled' ) { ?>
		<?php if ( $expiration_time ) { ?>
            <p class="course-time-row">
                <strong><?php esc_html_e( 'Course will end:', 'learnpress' ); ?></strong>
				<?php echo $expiration_time->format( 'i18n' ); ?>
            </p>
		<?php } else { ?>
            <p class="course-time-row">
                <strong><?php esc_html_e( 'Duration:', 'learnpress' ); ?></strong>
				<?php esc_html_e( 'Lifetime', 'learnpress' ); ?>
            </p>
		<?php } ?>
	<?php } elseif ( $status === 'finished' && $end_time ) { ?>
        <p class="course-time-row">
            <strong><?php esc_html_e( 'You finished on:', 'learnpress' ); ?></strong>
			<?php echo $end_time->format( 'human' ); ?>
        </p>
	<?php } ?>
</div>
