<?php
/**
 * Admin View: Displaying all LearnPress's add-ons have installed.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit();
?>

<?php
// get all plugins installed
$add_ons = LP_Plugins_Helper::get_plugins( 'installed' );

if ( ! $add_ons ) {
	_e( 'There is no add-on installed.', 'learnpress' );

	return;
}
?>

<h2>
	<?php echo __( 'Installed add-ons', 'learnpress' ) . ' (<span>' . sizeof( $add_ons ) . '</span>)'; ?>
</h2>
<ul class="addons-browse widefat">
	<?php
	foreach ( $add_ons as $file => $add_on ) {
		include learn_press_get_admin_view( 'addons/html-loop-plugin' );
	}
	?>
</ul>
