<?php
/**
 * Template for displaying extra info in backend user profile.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 4.0.2
 */

use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || die;

/**
 * @var WP_User $user
 */

if ( empty( $user ) ) {
	return;
}

$extra_profile_fields = learn_press_social_profiles();
$extra_profile        = learn_press_get_user_extra_profile_info( $user->ID );

$custom_profile = lp_get_user_custom_register_fields( $user->ID );
$user_model     = new UserModel( $user );
$lp_message     = LP_Request::get_param( 'lp-message' );
?>

<h3><?php esc_html_e( 'LearnPress User Profile', 'learnpress' ); ?></h3>

<table class="form-table">
	<tbody>
	<?php
	do_action( 'learn-press/admin/user/layout/general-info-custom', $user, $custom_profile );

	if ( current_user_can( 'edit_users' ) ) {
		?>
		<tr>
			<th>
				<label>
					<?php esc_html_e( 'LP slug user name', 'learnpress' ); ?>
				</label>
			</th>
			<td>
				<input class="regular-text"
					type="text"
					value="<?php echo esc_attr( $user_model->get_pretty_slug( false ) ); ?>"
					name="lp_user_slug">
				<?php
				if ( ! empty( $lp_message ) ) {
					Template::print_message( $lp_message, 'error' );
				}
				?>
				<p class="description">
					<?php
					printf(
						'%s<br>%s',
						esc_html__( 'Custom slug to replace the WP login username, use for link lp profile/instructor. Must be unique.', 'learnpress' ),
						sprintf(
							/* translators: 1: link to permalink settings page */
							__( 'Set for all uses not exists pretty slug <a href="%s">here</a>. Then click "Generate users slug"', 'learnpress' ),
							esc_url( admin_url( 'admin.php?page=learn-press-settings&tab=permalink' ) )
						)
					);
					?>
				</p>
			</td>
		</tr>
		<?php
	}

	foreach ( $extra_profile_fields as $key => $label ) {
		$type = apply_filters( 'learn-press/extra-profile-field-type', 'text' );
		?>
		<tr>
			<th>
				<label for="learn-press-user-profile-<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $label ); ?>
				</label>
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
