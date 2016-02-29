<?php
/**
 * Template for displaying item in a section
 *
 * @package Admin/Views
 */
if ( !isset( $section ) ) {
	$section = null;
}
$section_item_id = !empty( $item->section_item_id ) ? $item->section_item_id : null;
$item_id         = !empty( $item->item_id ) ? $item->item_id : null;
$group_name      = '_lp_curriculum[__SECTION__][items][' . ( $section_item_id ? $section_item_id : '__ITEM__' ) . ']';

$support_types = apply_filters(
	'learn_press_support_item_types', array(
		'lp_quiz'   => __( 'Quiz', 'learn_press' ),
		'lp_lesson' => __( 'Lesson', 'learn_press' )
	)
);
?>

<?php do_action( 'learn_press_admin_before_section_loop_item', $item, $section ); ?>

<tr <?php learn_press_admin_section_loop_item_class( $item, $section ); ?> data-text="<?php echo esc_attr( $item->post_title ); ?>" data-item_id="<?php echo $item_id; ?>" data-section_item_id="<?php echo $section_item_id; ?>" data-type="<?php echo $item->post_type; ?>">
	<?php do_action( 'learn_press_admin_begin_section_item', $item, $section ); ?>
	<td class="section-item-icon">
		<?php foreach ( $support_types as $_type => $text ) { ?>
			<span class="learn-press-icon<?php echo $_type == $item->post_type ? ' item-selected' : ''; ?> item-<?php echo $_type; ?>" title="<?php echo sprintf( __( 'Switch to %s', 'learnpress' ), $text ); ?>" data-type="<?php echo $_type;?>"></span>
		<?php } ?>
	</td>
	<td class="section-item-input">
		<input type="text" name="<?php echo $group_name; ?>[name]" class="lp-item-name no-submit" data-field="item-name" value="<?php echo esc_attr( $item->post_title ); ?>" placeholder="<?php _e( 'Enter name of the new item here and press Enter', 'learnpress' ); ?>" />
		<input type="hidden" name="<?php echo $group_name; ?>[old_name]" value="<?php echo esc_attr( $item->post_title ); ?>" />
		<input type="hidden" name="<?php echo $group_name; ?>[item_id]" value="<?php echo $item_id; ?>" />
		<input type="hidden" name="<?php echo $group_name; ?>[section_item_id]" value="<?php echo $section_item_id; ?>" />
		<input type="hidden" class="lp-item-type" name="<?php echo $group_name; ?>[post_type]" value="<?php echo $item->post_type; ?>" />
	</td>
	<td class="section-item-actions">
		<p class="lp-item-actions lp-button-actions">
			<?php do_action( 'learn_press_admin_begin_section_item_actions', $item, $section ); ?>
			<a href="<?php echo absint( $item_id ) ? get_edit_post_link( $item_id ) : '{{data.edit_link}}'; ?>" class="lp-item-action lp-edit dashicons dashicons-edit" target="_blank"><?php _e( '', 'learnpress' ); ?></a>
			<a href="" class="lp-item-action lp-remove dashicons dashicons-trash" data-confirm-remove="<?php _e( 'Are you sure you want to remove this item?', 'learnpress' ); ?>"><?php _e( '', 'learnpress' ); ?></a>
			<span class="item-checkbox">
				<input type="checkbox" value="<?php echo $section_item_id; ?>" />
			</span>
			<?php do_action( 'learn_press_admin_end_section_item_actions', $item, $section ); ?>
		</p>
	</td>
	<?php do_action( 'learn_press_admin_end_section_item', $item, $section ); ?>
</tr>