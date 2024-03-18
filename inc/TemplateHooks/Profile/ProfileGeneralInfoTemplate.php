<?php
/**
 * Template hooks Archive Package.
 *
 * @since 1.0.0
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
	}

	/**
	 * Show custom field in profile.
	 *
	 * @param LP_Profile $profile
	 *
	 * @return void
	 */
	public function info_custom( $profile ) {
		$custom_profile = lp_get_user_custom_register_fields( $profile->get_user()->get_id() );
		$custom_fields  = LP_Settings::get_option( 'register_profile_fields' );

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
							<label for="description"><?php echo esc_html( $field['name'] ); ?></label>
							<input name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]" type="<?php echo esc_attr( $field['type'] ); ?>" class="regular-text" value="<?php echo esc_attr( $custom_profile[ $field['id'] ] ?? '' ); ?>">
							<?php
							break;
						case 'textarea':
							?>
							<label for="description"><?php echo esc_html( $field['name'] ); ?></label>
							<textarea name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]"><?php echo isset( $custom_profile[ $field['id'] ] ) ? esc_textarea( $custom_profile[ $field['id'] ] ) : ''; ?></textarea>
							<?php
							break;
						case 'checkbox':
							?>
							<label>
								<input name="_lp_custom_register[<?php echo esc_attr( $field['id'] ); ?>]" type="<?php echo esc_attr( $field['type'] ); ?>" value="1" <?php echo isset( $custom_profile[ $field['id'] ] ) ? checked( $custom_profile[ $field['id'] ], 1 ) : ''; ?>>
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
}
