<?php
/**
 * Template for displaying extra info in backend user profile.
 *
 * @author  ThimPress
 * @package LearnPress/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) or die;

/**
 * @var WP_User $user
 */

if ( empty( $user ) ) {
	return;
}

$extra_profile_fields = learn_press_get_user_extra_profile_fields();
$extra_profile        = learn_press_get_user_extra_profile_info( $user->ID );

?>
<h3><?php esc_html_e( 'LearnPress User Profile', 'eduma' ); ?></h3>

<table class="form-table">
    <tbody>
	<?php foreach ( $extra_profile_fields as $key => $label ) {
		$type = apply_filters( 'learn-press/extra-profile-field-type', 'text' );
		?>
        <tr>
            <th>
                <label for="learn-press-user-profile-<?php echo $key; ?>"><?php echo esc_html( $label ); ?></label>
            </th>
            <td>
                <input id="learn-press-user-profile-<?php echo $key; ?>" class="regular-text"
                       type="<?php echo $type; ?>"
                       value="<?php echo isset( $extra_profile[ $key ] ) ? $extra_profile[ $key ] : ''; ?>"
                       name="_lp_extra_info[<?php echo $key; ?>]">
            </td>
        </tr>
	<?php } ?>
    </tbody>
</table>
