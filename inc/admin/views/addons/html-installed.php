<?php
/**
 * Template for displaying all LearnPress's add-ons have installed.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit();

$add_ons = LP_Plugins_Helper::get_plugins( 'installed' );
if ( ! $add_ons ) {
	_e( 'There is no addon has installed.', 'learnpress' );

	return;
}
?>
<h2><?php printf( __( 'Installed add-ons (<span>%d</span>)', 'learnpress' ), sizeof( $add_ons ) ); ?></h2>
<ul class="addons-browse widefat">
	<?php
	foreach ( $add_ons as $file => $add_on ) {
		include learn_press_get_admin_view( 'addons/html-loop-plugin' );
	}
	?>
</ul>
