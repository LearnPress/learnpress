<?php
/**
 * Template for displaying extra info in backend user profile.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 4.0.1
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
		do_action( 'learn-press/admin/user/layout/general-info-custom', $user, $custom_profile );

		foreach ( $extra_profile_fields as $key => $label ) {
			$type = apply_filters( 'learn-press/extra-profile-field-type', 'text' );
			?>
			<tr>
				<th>
					<label for="learn-press-user-profile-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
				</th>
				<td>
					<input id="learn-press-user-profile-<?php echo esc_attr( $key ); ?>"
						class="regular-text" type="<?php echo esc_attr( $type ); ?>"
						value="<?php echo esc_attr( $extra_profile[ $key ] ?? '' ); ?>"
						name="_lp_extra_info[<?php echo esc_attr( $key ); ?>]">
				</td>
			</tr>
		<?php } ?>
	</tbody>
</table>
