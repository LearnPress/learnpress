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