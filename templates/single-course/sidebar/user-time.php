<?php
/**
 * Template for displaying user time on course.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.2
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $data ) || ! isset( $data['start_time'] ) || ! isset( $data['end_time'] )
	|| ! isset( $data['status'] ) || ! isset( $data['expiration_time'] ) ) {
	return;
}

/**
 * @var LP_Datetime $start_time
 * @var LP_Datetime $end_time
 * @var LP_Datetime $expiration_time
 */
$start_time      = $data['start_time'];
$end_time        = $data['end_time'];
$status          = $data['status'];
$expiration_time = $data['expiration_time'];
?>

<div class="course-time">
	<p class="course-time-row">
		<strong><?php esc_html_e( 'You started on:', 'learnpress' ); ?></strong>
		<time class="entry-date enrolled"><?php echo esc_html( $start_time->format( 'i18n' ) ); ?></time>
	</p>
	<?php if ( in_array( $status, array( learn_press_user_item_in_progress_slug(), 'enrolled' ) ) ) : ?>
		<?php if ( $expiration_time ) : ?>
			<p class="course-time-row">
				<strong><?php esc_html_e( 'Course will end:', 'learnpress' ); ?></strong>
				<time class="entry-date expire"><?php echo esc_html( $expiration_time->format( 'i18n' ) ); ?></time>
			</p>
		<?php else : ?>
			<p class="course-time-row">
				<strong><?php esc_html_e( 'Duration:', 'learnpress' ); ?></strong>
				<?php esc_html_e( 'Lifetime', 'learnpress' ); ?>
			</p>
		<?php endif; ?>
	<?php elseif ( $status === 'finished' && $end_time ) : ?>
		<p class="course-time-row">
			<strong><?php esc_html_e( 'You finished on:', 'learnpress' ); ?></strong>
			<time class="entry-date finished"><?php echo esc_html( $end_time->format( 'i18n' ) ); ?></time>
		</p>
	<?php endif; ?>
</div>
