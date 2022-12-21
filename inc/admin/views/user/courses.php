<?php
/**
 * HTML View for displaying courses user enrolled in wp profile.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || die;

if ( ! isset( $user_id ) ) {
	return;
}

$profile              = LP_Profile::instance( $user_id );
$user                 = $profile->get_user();
$slug_profile_courses = LP_Settings::instance()->get( 'profile_endpoints.courses', 'courses' );
$link_user_profile    = add_query_arg( [ 'tab' => 'enrolled' ], learn_press_user_profile_link( $user_id ) . $slug_profile_courses );
?>
<p>
	<b><?php _e( 'The course list of enrolled users', 'learnpress' ); ?></b>
	<a href="<?php echo esc_url_raw( $link_user_profile ); ?>" target="_blank"><?php _e( 'View', 'learnpress' ); ?></a>
</p>
