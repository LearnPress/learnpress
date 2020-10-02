<?php
/**
 * Template for displaying extra info in backend user profile.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || die;

/**
 * @var WP_User $user
 */

if ( empty( $user ) ) {
	return;
}

$extra_profile_fields = learn_press_get_user_extra_profile_fields();
$extra_profile        = learn_press_get_user_extra_profile_info( $user->ID );

$custom_profile = lp_get_user_custom_register_fields( $user->ID );
?>

<h3><?php esc_html_e( 'LearnPress User Profile', 'learnpress' ); ?></h3>

<table class="form-table">
	<tbody>
		<?php
		$custom_fields = LP()->settings()->get( 'register_profile_fields' );

		if ( $custom_fields ) {
			foreach ( $custom_fields as $field ) {
				$value = sanitize_key( $field['name'] );
				?>
				<tr>
					<th>
						<label for="learn-press-custom-register-<?php echo $value; ?>"><?php echo esc_html( $field['name'] ); ?></label>
					</th>
					<td>
					<?php
					switch ( $field['type'] ) {
						case 'text':
						case 'number':
						case 'email':
						case 'url':
						case 'tel':
							?>
							<input name="_lp_custom_register[<?php echo $value; ?>]" type="<?php echo $field['type']; ?>" class="regular-text" value="<?php echo isset( $custom_profile[ $value ] ) ? $custom_profile[ $value ] : ''; ?>">
							<?php
							break;
						case 'textarea':
							?>
							<textarea name="_lp_custom_register[<?php echo $value; ?>]"><?php echo isset( $custom_profile[ $value ] ) ? esc_textarea( $custom_profile[ $value ] ) : ''; ?></textarea>
							<?php
							break;
						case 'checkbox':
							?>
							<input name="_lp_custom_register[<?php echo $value; ?>]" type="<?php echo $field['type']; ?>" value="1" <?php echo isset( $custom_profile[ $value ] ) ? checked( $custom_profile[ $value ], 1 ) : ''; ?>>
							<?php
							break;
					}
					?>
					</td>
				</tr>
				<?php
			}
		}

		foreach ( $extra_profile_fields as $key => $label ) {
			$type = apply_filters( 'learn-press/extra-profile-field-type', 'text' );
			?>
			<tr>
				<th>
					<label for="learn-press-user-profile-<?php echo $key; ?>"><?php echo esc_html( $label ); ?></label>
				</th>
				<td>
					<input id="learn-press-user-profile-<?php echo $key; ?>" class="regular-text" type="<?php echo $type; ?>" value="<?php echo isset( $extra_profile[ $key ] ) ? $extra_profile[ $key ] : ''; ?>" name="_lp_extra_info[<?php echo $key; ?>]">
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>
