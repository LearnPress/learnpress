<?php do_action( 'learn_press_admin_before_section_loop_item', $item, $section );?>
<li <?php learn_press_admin_section_loop_item_class( $item, $section );?> data-text="<?php echo esc_attr($item->post_title);?>" data-item_id="<?php echo $item->item_id; ?>" data-section_item_id="<?php echo $item->section_item_id;?>" data-type="<?php echo $item->post_type; ?>">
	<?php do_action( 'learn_press_admin_begin_section_item', $item, $section );?>
	<span class="handle learn-press-icon"></span>
	<input type="text" name="_lp_curriculum[__SECTION__][items][<?php echo $item->section_item_id ? $item->section_item_id : '__ITEM__';?>][name]" class="lp-item-name no-submit" data-field="item-name" value="<?php echo esc_attr( $item->post_title );?>" />
	<input type="hidden" name="_lp_curriculum[__SECTION__][items][<?php echo $item->section_item_id ? $item->section_item_id : '__ITEM__';?>][old_name]" value="<?php echo esc_attr( $item->post_title );?>" />
	<input type="hidden" name="_lp_curriculum[__SECTION__][items][<?php echo $item->section_item_id ? $item->section_item_id : '__ITEM__';?>][item_id]" value="<?php echo $item->item_id; ?>" />
	<input type="hidden" name="_lp_curriculum[__SECTION__][items][<?php echo $item->section_item_id ? $item->section_item_id : '__ITEM__';?>][section_item_id]" value="<?php echo $item->section_item_id; ?>" />
	<input type="hidden" class="lp-item-type" name="_lp_curriculum[__SECTION__][items][<?php echo $item->section_item_id ? $item->section_item_id : '__ITEM__';?>][post_type]" value="<?php echo $item->post_type; ?>" />
	<p class="lp-item-actions">
		<?php do_action( 'learn_press_admin_begin_section_item_actions', $item, $section );?>
		<a href="" class="lp-item-action lp-sort-item"><?php _e( 'Move', 'learn_press' ); ?></a> |
		<a href="<?php echo is_numeric( $item->item_id ) ? get_edit_post_link( $item->item_id ) : '{{data.edit_link}}'; ?>" class="lp-item-action lp-edit" target="_blank"><?php _e( 'Edit', 'learn_press' ); ?></a> |
		<a href="" class="lp-item-action lp-remove"><?php _e( 'Remove', 'learn_press' ); ?></a>
		<?php do_action( 'learn_press_admin_end_section_item_actions', $item, $section );?>
	</p>
	<?php do_action( 'learn_press_admin_end_section_item', $item, $section );?>
</li>