<?php
/**
 * User Courses tab
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}
$subtabs = array(
	'all'      => __( 'All', 'learn_press' ),
	'learning' => __( 'Learning', 'learn_press' ),
	'finished' => __( 'Finished', 'learn_press' ),
	'own'      => __( 'Own', 'learn_press' )
);
$subtabs = apply_filters( 'learn_press_profile_tab_courses_subtabs', $subtabs );
if( !$subtabs ){
	return;
}
?>
<ul class="learn-press-subtabs">
	<?php foreach( $subtabs as $subid => $subtitle ){ ?>
	<li><a href="#learn-press-subtab-<?php echo $subid;?>"><?php echo esc_html( $subtitle );?></a> </li>
	<?php } ?>
</ul>
<?php foreach( $subtabs as $subid => $subtitle ){ ?>
	<div id="learn-press-subtab-<?php echo esc_attr( $subid );?>" class="learn-press-subtab-content">
	<?php do_action( 'learn_press_profile_tab_courses_' . $subid, $user ); ?>
	</div>
<?php } ?>