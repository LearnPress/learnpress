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
	<h3><?php _e( 'Profile', 'learn_press' ); ?></h3>
	<table class="form-table">
		<tbody>
		<?php do_action( 'learn_press_before_' . $this->id . '_settings_fields', $this ); ?>
		<?php foreach( $this->get_settings() as $field ){?>
			<?php $this->output_field( $field );?>
		<?php }?>
		<?php if( 1 == 0 ){?>
		<tr>
			<th scope="row"><label><?php _e( 'Profile page', 'learn_press' ); ?></label></th>
			<td>
				<?php
				$profile_page_id = $settings->get( 'profile_page_id', 0 );
				learn_press_pages_dropdown( $this->get_field_name( "profile_page_id" ), $profile_page_id );
				?>
			</td>
		</tr>
		<?php }?>
		<?php do_action( 'learn_press_after_' . $this->id . '_settings_fields', $this ); ?>
		</tbody>
	</table>