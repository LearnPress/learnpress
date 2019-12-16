<?php
/**
 * Template for displaying featured courses in user profile dashboard.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

$user = LP_Profile::instance()->get_user();
?>
<div class="profile-courses featured-courses">
    <h3><?php esc_html_e( 'Featured courses', 'learnpress' ); ?></h3>

	<?php
	if ( ! empty( $courses ) ) {
		?>
        <div class="lp-archive-courses">
            <ul class="learn-press-courses" data-size="3" data-layout="grid" id="learn-press-profile-featured-courses">
				<?php
				global $post;

				foreach ( $courses as $item ) {
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
		if ( isset( $max_num_pages ) && $max_num_pages > 1 ) {
			?>
            <button data-type="featured" data-user="<?php echo $user->get_id(); ?>"
                    data-num-pages="<?php echo $max_num_pages; ?>" data-container="learn-press-profile-featured-courses"
                    data-template="profile/dashboard/featured-courses"
                    class="lp-button btn-load-more-courses"><?php esc_html_e( 'View More', 'learnpress' ); ?></button>
			<?php
		}
	} else {
		learn_press_display_message( __( 'There is no featured courses.', 'learnpress' ) );
	} // End if !empty( $courses ) ?>
</div>
