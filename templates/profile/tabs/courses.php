<?php
/**
 * User Courses tab
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $profile;

$query   = $profile->query_courses();
$profile = learn_press_get_profile();

if ( ! $query['total'] ) {
	learn_press_display_message( __( 'You haven\'t got any courses yet!', 'learnpress' ) );

	return;
}

?>
<div class="learn-press-subtab-content">

    <table class="lp-list-table profile-list-courses profile-list-table">
        <thead>
        <tr>
            <th class="column-course"><?php _e( 'Course', 'learnpress' ); ?></th>
            <th class="column-date"><?php _e( 'Date', 'learnpress' ); ?></th>
            <th class="column-status"><?php _e( 'Progress', 'learnpress' ); ?></th>
            <th class="column-status"><?php _e( 'Actions', 'learnpress' ); ?></th>
        </tr>
        </thead>
        <tbody>
		<?php foreach ( $query['items'] as $user_course ) { ?>
			<?php $course = learn_press_get_course( $user_course->get_id() ); ?>
            <tr>
                <td class="column-course">
                    <a href="<?php echo $course->get_permalink(); ?>">
						<?php echo $course->get_title(); ?>
                    </a>
                </td>
                <td class="column-date"><?php echo $user_course->get_start_time( 'd M Y' ); ?></td>
                <td class="column-status">
                    <span class="result-percent"><?php echo $user_course->get_percent_result(); ?></span>
                    <span class="lp-label label-<?php echo esc_attr( $user_course->get_results( 'status' ) ); ?>">
                            <?php echo $user_course->get_status_label(); ?>
                        </span>
                </td>
                <td class="column-actions">
                    <a href="<?php echo $profile->get_current_url( array( 'details' => $course->get_id() ) ); ?>"><?php esc_html_e( 'Details', 'learnpress' ); ?></a>
                </td>
            </tr>
			<?php continue; ?>
            <tr>
                <td colspan="4">
					<?php
					$user_quiz = $user_course->get_item( $course->get_final_quiz() );
					learn_press_debug( $user_quiz, $user_course->get_results() );
					?>
                </td>
            </tr>
		<?php } ?>
        </tbody>
        <tfoot>
        <tr class="list-table-nav">
            <td colspan="2" class="nav-text">
				<?php echo $query->get_offset_text(); ?>
            </td>
            <td colspan="2" class="nav-pages">
				<?php $query->get_nav( true ); ?>
            </td>
        </tr>
        </tfoot>
    </table>
</div>
