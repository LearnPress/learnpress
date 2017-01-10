<?php
/**
 * Form for editing basic information of user in profile page
 *
 * @author  ThimPress
 * @version 2.1.1
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) || exit;
?>

<ul class="lp-form-field-wrap">
	<?php do_action( 'learn_press_before_' . $section . '_edit_fields' ); ?>
	<li class="lp-form-field">
		<label class="lp-form-field-label"><?php _e( 'Biographical Info', 'learnpress' ); ?></label>
		<div class="lp-form-field-input">
			<textarea name="description" id="description" rows="5" cols="30"><?php esc_html_e( $user_info->description ); ?></textarea>
			<p class="description"><?php _e( 'Share a little biographical information to fill out your profile. This may be shown publicly.', 'learnpress' ); ?></p>
		</div>
	</li>
	<li class="lp-form-field">
		<label class="lp-form-field-label"><?php _e( 'First Name', 'learnpress' ); ?></label>
		<div class="lp-form-field-input">
			<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text">
		</div>
	</li>
	<li class="lp-form-field">
		<label class="lp-form-field-label"><?php _e( 'Last Name', 'learnpress' ); ?></label>
		<div class="lp-form-field-input">
			<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text">
		</div>
	</li>
	<li class="lp-form-field">
		<label class="lp-form-field-label"><?php _e( 'Nickname', 'learnpress' ); ?></label>
		<div class="lp-form-field-input">
			<input type="text" name="nickname" id="nickname" value="<?php echo esc_attr( $user_info->nickname ) ?>" class="regular-text" />
		</div>
	</li>
	<li class="lp-form-field">
		<label class="lp-form-field-label"><?php _e( 'Display name publicly as', 'learnpress' ); ?></label>
		<div class="lp-form-field-input">
			<select name="display_name" id="display_name">
				<?php
				$public_display = learn_press_get_display_name_publicly( $user_info );
				foreach ( $public_display as $id => $item ) {
					?>
					<option <?php selected( $user_info->display_name, $item ); ?>><?php echo $item; ?></option>
					<?php
				}
				?>
			</select>
		</div>
	</li>
	<?php do_action( 'learn_press_after_' . $section . '_edit_fields' ); ?>
</ul>