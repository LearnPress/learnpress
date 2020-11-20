<?php
/**
 * Template for displaying purchased courses in courses tab of user profile page.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$profile       = learn_press_get_profile();
$filter_status = LP_Request::get_string( 'filter-status' );
$query         = $profile->query_courses( 'purchased', array( 'status' => $filter_status ) );
$counts        = $query['counts'];
$filters       = $profile->get_purchased_courses_filters( $filter_status );
?>

<div class="learn-press-subtab-content">

	<?php if ( $filters ) : ?>
		<ul class="learn-press-filters">
			<?php
			foreach ( $filters as $class => $link ) {
				$count = ! empty( $counts[ $class ] ) ? $counts[ $class ] : false;

				if ( $count !== false ) {
					?>

					<li class="<?php echo esc_attr( $class ); ?>">
						<?php echo sprintf( '%s <span class="count">%s</span>', $link, $count ); ?>
					</li>
					<?php
				}
			}
			?>
		</ul>
	<?php endif; ?>

	<?php if ( $query['items'] ) : ?>
		<div class="lp-archive-courses">
			<ul <?php lp_item_course_class(array('profile-courses-list'));?> id="learn-press-profile-enrolled-courses" data-layout="grid" data-size="3">
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
		?>

		<?php if ( $num_pages > 1 && $current_page < $num_pages ) : ?>
			<button data-container="learn-press-profile-enrolled-courses"
					data-pages="<?php echo $num_pages; ?>"
					data-paged="<?php echo $current_page; ?>"
					class="lp-button btn-load-more-courses btn-ajax-off">
				<i class="fas fa-spinner icon"></i>
				<?php esc_html_e( 'View More', 'learnpress' ); ?></button>
		<?php endif; ?>

	<?php else : ?>
		<?php learn_press_display_message( esc_html__( 'No courses!', 'learnpress' ) ); ?>
	<?php endif; ?>
</div>
