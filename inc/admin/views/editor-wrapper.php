<?php
/**
 * Course editor wrapper.
 *
 * @since 3.0.0
 */
?>

<div id="admin-editor-<?php esc_attr_e( $post_type ); ?>">
    <div class="lp-place-holder">
		<?php learn_press_admin_view( 'placeholder-animation' ); ?>
    </div>
</div>
