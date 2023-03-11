<?php
/**
 * Template show toggle switch field
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $data ) ) {
	return;
}
?>
<div class="lp-toggle-switch">
	<input class="lp-toggle-switch-input"
		name="<?php echo esc_attr( $data['name'] ?? '' ); ?>"
		id="<?php echo esc_attr( $data['id'] ?? '' ); ?>"
		<?php echo esc_attr( $data['value'] ? 'checked' : '' ); ?>
		type="checkbox" value="<?php echo esc_attr( $data['value'] ?? 0 ); ?>"
		<?php echo sanitize_text_field( $data['extra'] ?? '' ); ?>>
	<label class="lp-toggle-switch-label" for="<?php echo esc_attr( $data['id'] ?? '' ); ?>">
		<span class="toggle-on"></span>
		<span class="toggle-off"></span>
	</label>
	<span class="dashicons dashicons-update" style="display: none"></span>
</div>
