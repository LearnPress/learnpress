<?php
/**
 * Template for displaying global login form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/form-register.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$profile = LP_Global::profile();
$fields  = $profile->get_register_fields();
?>

<div class="learn-press-form-register learn-press-form">

	<h3><?php echo esc_html_x( 'Register', 'register-heading', 'learnpress' ); ?></h3>

	<?php do_action( 'learn-press/before-form-register' ); ?>

	<form name="learn-press-register" method="post" action="">

		<?php do_action( 'learn-press/before-form-register-fields' ); ?>

		<ul class="form-fields">
			<?php foreach ( $fields as $field ) : ?>
				<li class="form-field">
					<?php LP_Meta_Box_Helper::show_field( $field ); ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<?php $custom_fields = LP()->settings()->get( 'register_profile_fields' ); ?>

		<?php if ( $custom_fields ) : ?>
			<ul class="form-fields">
				<?php foreach ( $custom_fields as $custom_field ) : ?>
					<?php $value = sanitize_key( $custom_field['name'] ); ?>

					<li class="form-field">
					<?php
					switch ( $custom_field['type'] ) {
						case 'text':
						case 'number':
						case 'email':
						case 'url':
						case 'tel':
							?>
								<label for="description"><?php echo esc_html( $custom_field['name'] ); ?></label>
								<input name="_lp_custom_register_form[<?php echo $value; ?>]" type="<?php echo $custom_field['type']; ?>" class="regular-text" value="" <?php echo $custom_field['required'] === 'yes' ? 'required' : ''; ?>>
								<?php
							break;
						case 'textarea':
							?>
								<label for="description"><?php echo esc_html( $custom_field['name'] ); ?></label>
								<textarea name="_lp_custom_register_form[<?php echo $value; ?>]" <?php echo $custom_field['required'] === 'yes' ? 'required' : ''; ?>></textarea>
								<?php
							break;
						case 'checkbox':
							?>
								<label>
									<input name="_lp_custom_register_form[<?php echo $value; ?>]" type="<?php echo $custom_field['type']; ?>" value="1" <?php echo $custom_field['required'] === 'yes' ? 'required' : ''; ?>>
								<?php echo esc_html( $custom_field['name'] ); ?>
								</label>
								<?php
							break;
					}
					?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php do_action( 'learn-press/after-form-register-fields' ); ?>

		<?php do_action( 'register_form' ); ?>

		<p>
			<?php wp_nonce_field( 'learn-press-register', 'learn-press-register-nonce' ); ?>
			<button type="submit"><?php esc_html_e( 'Register', 'learnpress' ); ?></button>
		</p>

	</form>

	<?php do_action( 'learn-press/after-form-register' ); ?>

</div>
