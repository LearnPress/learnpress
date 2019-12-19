<?php
/**
 * Template for displaying own courses in courses tab of user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/courses/own.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.11.2
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$profile       = learn_press_get_profile();
$user          = LP_Profile::instance()->get_user();
$filter_status = LP_Request::get_string( 'filter-status' );
$query         = $profile->query_courses( 'own', array( 'status' => $filter_status ) );
$counts        = $query['counts'];
?>

<div class="learn-press-subtab-content">

	<?php if ( $filters = $profile->get_own_courses_filters( $filter_status ) ) { ?>
        <ul class="learn-press-filters">
			<?php foreach ( $filters as $class => $link ) {
				$count = ! empty( $counts[ $class ] ) ? absint( $counts[ $class ] ) : false;

				if ( $class !== 'all' && $count === $counts['all'] ) {
					continue;
				}

				if ( $count ) {
					?>
                    <li class="<?php echo $class; ?>">
						<?php
						printf( '%s <span class="count">%s</span>', $link, $count );
						?>
                    </li>
				<?php }
			} ?>
        </ul>
		<?php
	} ?>

	<?php if ( ! $query['total'] ) {
		learn_press_display_message( __( 'No courses!', 'learnpress' ) );
	} else { ?>
        <div class="lp-archive-courses">
            <ul class="learn-press-courses profile-courses-list" id="learn-press-profile-created-courses"
                data-layout="grid" data-size="3">
				<?php
				global $post;
				foreach ( $query['items'] as $item ) {
					$course = learn_press_get_course( $item );
					$post   = get_post( $item );
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
	<?php } ?>
</div>
