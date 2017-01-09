<?php
/**
 * Display html for general settings
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$settings = LP()->settings;
?>

<table class="form-table">
	<tbody>
	<?php
		do_action( 'learn_press_before_general_settings_fields', $settings );
		$this->output_settings();
	?>
	</tbody>
</table>
