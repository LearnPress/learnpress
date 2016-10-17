<?php
/**
 * Display settings for profile
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
		<?php do_action( 'learn_press_before_' . $this->id . '_settings_fields', $this ); ?>
		<?php foreach( $this->get_settings() as $field ){?>
			<?php $this->output_field( $field );?>
		<?php }?>
		<?php do_action( 'learn_press_after_' . $this->id . '_settings_fields', $this ); ?>
		</tbody>
	</table>