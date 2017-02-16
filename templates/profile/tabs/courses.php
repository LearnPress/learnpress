<?php
/**
 * User Courses tab
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/*
$subtab  = !empty( $_REQUEST['section'] ) ? $_REQUEST['section'] : '';
$subtabs = learn_press_get_subtabs_course();
if ( !$subtabs ) {
	return;
}
$subkeys = array_keys( $subtabs );
$firstid = current( $subkeys );
$sublink = learn_press_user_profile_link( $user->id, $current );
?>
	<ul class="learn-press-subtabs">
		<?php foreach ( $subtabs as $subid => $subtitle ) { ?>
			<?php
			?>
			<li<?php echo ( $subid == $subtab || ( !$subtab && $subid == $firstid ) ) ? ' class="current"' : ''; ?>>
				<a href="<?php echo add_query_arg( array( 'section' => $subid ), $sublink ); ?>"><?php echo esc_html( $subtitle ); ?></a>
			</li>
		<?php } ?>
	</ul>
<?php foreach ( $subtabs as $subid => $subtitle ) { ?>
	<div id="learn-press-subtab-<?php echo esc_attr( $subid ); ?>" class="learn-press-subtab-content<?php echo ( $subid == $subtab || ( !$subtab && $subid == $firstid ) ) ? ' current' : ''; ?>">
		<?php do_action( 'learn_press_profile_tab_courses_' . $subid, $user, $subid ); ?>
	</div>
<?php } ?>
<?php


*/
global $post;

$args              = array(
                        'user'   => $user
                    );
$limit             = LP()->settings->get( 'profile_courses_limit', 10 );
$limit             = apply_filters( 'learn_press_profile_tab_courses_all_limit', $limit );
$courses           = $user->get( 'courses', array( 'limit' => $limit ) );

$num_pages         = learn_press_get_num_pages( $user->_get_found_rows(), $limit );
$args['courses']   = $courses;
$args['num_pages'] = $num_pages;

if ( $courses ) {
    ?>
    <div class="learn-press-subtab-content" style="display: block">
        <ul class="learn-press-courses profile-courses courses-list">
            <?php foreach ( $courses as $post ) {
                setup_postdata( $post );
                ?>

                <?php
                learn_press_get_template( 'profile/tabs/courses/loop.php', array( 'user' => $user, 'course_id' => $post->ID ) );
                wp_reset_postdata();
                ?>

            <?php } ?>
        </ul>

        <?php learn_press_paging_nav( array( 'num_pages' => $num_pages ) ); ?>

    </div>
    <?php
}
else {
    learn_press_display_message( __( 'You haven\'t got any courses yet!', 'learnpress' ) );
}
?>
