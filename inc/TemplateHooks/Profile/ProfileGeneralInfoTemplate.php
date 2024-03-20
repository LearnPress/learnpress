<?php
/**
 * Template hooks Archive Package.
 *
 * @since 4.2.6.4
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Profile;

use LP_Profile;
use LP_Settings;

class ProfileGeneralInfoTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		//add_action( 'learn-press/profile/layout/general-info', [ $this, 'sections' ], 2 );
		add_action( 'learn-press/profile/layout/general-info-custom', [ $this, 'info_custom' ] );
		add_action( 'learn-press/admin/user/layout/general-info-custom', [ $this, 'user_info_custom' ], 10, 2 );
	}

	/**
	 * Show custom field in profile.
	 *
	 * @param LP_Profile $profile
	 *
	 * @return void
	 */
	public function info_custom( $profile ) {
		$user_info_custom = lp_get_user_custom_register_fields( $profile->get_user()->get_id() );
		$custom_fields    = LP_Settings::get_option( 'register_profile_fields', [] );

		if ( $custom_fields ) {
			foreach ( $custom_fields as $field ) {
				?>
				<li class="form-field form-field__<?php echo esc_attr( $field['id'] ); ?> form-field__clear">
					<?php
					switch ( $field['type'] ) {
						case 'text':
						case 'number':
						case 'email':
						case 'url':
						case 'tel':
							?>
							<label><?php echo esc_html( $field['name'] ); ?></label>
							<input name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"
								   type="<?php echo esc_attr( $field['type'] ); ?>" class="regular-text"
								   value="<?php echo esc_attr( $user_info_custom[ $field['id'] ] ?? '' ); ?>">
							<?php
							break;
						case 'textarea':
							?>
							<label><?php echo esc_html( $field['name'] ); ?></label>
							<textarea
								name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"><?php echo isset( $user_info_custom[ $field['id'] ] ) ? esc_textarea( $user_info_custom[ $field['id'] ] ) : ''; ?></textarea>
							<?php
							break;
						case 'checkbox':
							?>
							<label>
								<input name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"
									   type="<?php echo esc_attr( $field['type'] ); ?>"
									   value="1" <?php echo isset( $user_info_custom[ $field['id'] ] ) ? checked( $user_info_custom[ $field['id'] ], 1 ) : ''; ?>>
								<?php echo esc_html( $field['name'] ); ?>
							</label>
							<?php
							break;
					}
					?>
				</li>
				<?php
			}
		}
	}

	public function user_info_custom( $user, $user_info_custom ) {
		$custom_fields = LP_Settings::get_option( 'register_profile_fields', [] );

		if ( $custom_fields ) {
			foreach ( $custom_fields as $field ) {
				?>
				<tr>
					<th>
						<label
							for="learn-press-custom-register-<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['name'] ); ?></label>
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
								<input name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"
									   type="<?php echo esc_attr( $field['type'] ); ?>" class="regular-text"
									   value="<?php echo esc_attr( $user_info_custom[ $field['id'] ] ?? '' ); ?>">
								<?php
								break;
							case 'textarea':
								?>
								<textarea
									name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"><?php echo isset( $user_info_custom[ $field['id'] ] ) ? esc_textarea( $user_info_custom[ $field['id'] ] ) : ''; ?></textarea>
								<?php
								break;
							case 'checkbox':
								?>
								<input name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"
									   type="<?php echo esc_attr( $field['type'] ); ?>"
									   value="1" <?php echo isset( $user_info_custom[ $field['id'] ] ) ? checked( $user_info_custom[ $field['id'] ], 1 ) : ''; ?>>
								<?php
								break;
						}
						?>
					</td>
				</tr>
				<?php
			}
		}
	}

	public function print_type_field( $field, $user_info_custom = [] ) {
		ob_start();

		//echo sprintf( '<label>%s</label>', esc_html( $field['name'] ?? '' ) );

		switch ( $field['type'] ?? '' ) {
			case 'text':
			case 'number':
			case 'email':
			case 'url':
			case 'tel':
				?>
				<input name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"
					   type="<?php echo esc_attr( $field['type'] ); ?>" class="regular-text"
					   placeholder="<?php echo esc_attr( $field['name'] ?? '' ); ?>"
					   value="<?php echo esc_attr( $user_info_custom[ $field['id'] ] ?? '' ); ?>">
				<?php
				break;
			case 'textarea':
				?>
				<textarea name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]">
					<?php echo isset( $user_info_custom[ $field['id'] ] ) ? esc_textarea( $user_info_custom[ $field['id'] ] ) : ''; ?>
				</textarea>
				<?php
				break;
			case 'checkbox':
				?>
				<input name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"
					   type="<?php echo esc_attr( $field['type'] ); ?>"
					   value="1" <?php echo isset( $user_info_custom[ $field['id'] ] ) ? checked( $user_info_custom[ $field['id'] ], 1 ) : ''; ?>>
				<?php
				break;
		}

		return '<div>' . ob_get_clean() . '</div>';
	}
}
