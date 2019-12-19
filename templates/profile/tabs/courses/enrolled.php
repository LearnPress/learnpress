<?php
/**
 * Template for displaying purchased courses in courses tab of user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/courses/purchased.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.11.2
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
/**
 * @var LP_User_Item_Course $user_course
 */
$profile       = learn_press_get_profile();
$filter_status = LP_Request::get_string( 'filter-status' );
$query         = $profile->query_courses( 'purchased', array( 'status' => $filter_status ) );
$counts        = $query['counts'];
?>

<div class="learn-press-subtab-content">

	<?php if ( $filters = $profile->get_purchased_courses_filters( $filter_status ) ) { ?>
        <ul class="learn-press-filters">
			<?php foreach ( $filters as $class => $link ) {
				$count = ! empty( $counts[ $class ] ) ? $counts[ $class ] : false;

				if ( $count !== false ) {

					?>
                    <li class="<?php echo $class; ?>">
						<?php
						echo sprintf( '%s <span class="count">%s</span>', $link, $count );
						?>
                    </li>
				<?php }
			} ?>
        </ul>
		<?php
	} ?>

	<?php if ( $query['items'] ) { ?>
        <div class="lp-archive-courses">
            <ul class="learn-press-courses profile-courses-list" id="learn-press-profile-enrolled-courses" data-layout="grid" data-size="3">
				<?php
				global $post;
				foreach ( $query['items'] as $item ) {
					$course = learn_press_get_course( $item->get_id() );
					$post   = get_post( $item->get_id() );
					setup_postdata( $post );
					learn_press_get_template( 'content-course.php' );
				}
				wp_reset_postdata();
				?>
            </ul>
        </div>

		<?php
		$num_pages    = $query->get_pages();
		$current_page = $query->get_paged();
		if ( $num_pages > 1 && $current_page < $num_pages ) { ?>
            <button data-container="learn-press-profile-enrolled-courses"
                    data-pages="<?php echo $num_pages ?>"
                    data-paged="<?php echo $current_page; ?>"
                    class="lp-button btn-load-more-courses btn-ajax-off">
                <i class="fas fa-spinner icon"></i>
                <?php esc_html_e( 'View More', 'learnpress' ); ?></button>
		<?php } ?>
	<?php } else {
		learn_press_display_message( __( 'No courses!', 'learnpress' ) );
	} ?>
</div>
