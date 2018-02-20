<?php
/**
 * Template for displaying the loop of section
 *
 * @param $class
 * @param $toggle_class
 * @param $section_name
 * @param $content_items
 */

$section   = learn_press_get_default_section( isset( $section ) ? $section : null );
$is_hidden = $section->section_id && is_array( $hidden_sections ) && in_array( $section->section_id, $hidden_sections );

$class = array(
	'curriculum-section'
);
if ( !$section->section_id ) {
	$class[] = 'lp-empty-section';
}
if ( $is_hidden ) {
	$class[] = 'is_hidden';
}
?>
<li class="<?php echo join( ' ', $class ); ?>" data-id="<?php echo $section ? $section->section_id : ''; ?>">
	<h3 class="curriculum-section-head">
		<input name="_lp_curriculum[__SECTION__][name]" type="text" data-field="section-name" placeholder="<?php _e( 'Enter section name and hit enter', 'learnpress' ); ?>" class="lp-section-name no-submit" value="<?php echo esc_attr( $section->section_name ); ?>" />
		<p class="lp-section-actions lp-button-actions">
			<a href="" data-action="expand" class="dashicons dashicons-arrow-down<?php echo $is_hidden ? '' : ' hide-if-js'; ?>" title="<?php _e( 'Expand', 'learnpress' ); ?>"></a>
			<a href="" data-action="collapse" class="dashicons dashicons-arrow-up<?php echo !$is_hidden ? '' : ' hide-if-js'; ?>" title="<?php _e( 'Collapse', 'learnpress' ); ?>"></a>
			<a href="" data-action="remove" class="dashicons dashicons-trash" data-confirm-remove="<?php _e( 'Are you sure you want to remove this section?', 'learnpress' ); ?>"></a>
			<a href="" class="move"></a>
		</p>
	</h3>
	<div class="curriculum-section-content<?php echo $is_hidden ? ' hide-if-js' : ''; ?>">
		<div class="item-bulk-actions">
			<input name="_lp_curriculum[__SECTION__][description]" class="lp-section-describe" type="text" value="<?php echo esc_attr( $section->section_description ); ?>" placeholder="<?php _e( 'Describe about this section', 'learnpress' ); ?>" />
			<button class="button hide-if-js" type="button" data-action="delete" data-title="<?php _e( 'Remove', 'learnpress' ); ?>" data-confirm-remove="<?php _e( 'Are you sure you want to remove these items from section?', 'learnpress' ); ?>"><?php _e( 'Remove', 'learnpress' ); ?></button>
            <span class="button lp-check-items">
				<input class="lp-check-all-items" data-action="check-all" type="checkbox" />
			</span>
		</div>
		<table class="curriculum-section-items">
			<?php echo isset( $content_items ) ? $content_items : ''; ?>
			<?php
			$item = learn_press_post_object( array( 'post_type' => LP_LESSON_CPT ) );
			?>
			<?php learn_press_admin_view( 'meta-boxes/course/loop-item.php', array( 'item' => $item ) ); ?>

		</table>
		<?php do_action( 'learn_press_after_section_items', $section ); ?>
		<?php if ( $buttons = apply_filters( 'learn_press_loop_section_buttons', array() ) ): ?>
			<br />
			<div class="lp-add-buttons">

				<?php foreach ( learn_press_section_item_types() as $slug => $name ) { ?>
					<?php if ( apply_filters( 'learn_press_button_type_select_items', true, $slug ) ) { ?>
						<button class="button" type="button" data-action="add-<?php echo $slug; ?>" data-type="<?php echo $slug; ?>">
							<?php echo sprintf( __( 'Select %s', 'learnpress' ), $name ); ?>
						</button>
					<?php } ?>
				<?php } ?>

			</div>
		<?php endif; ?>
		<?php do_action( 'learn_press_after_section_content', $section ); ?>
	</div>
</li>