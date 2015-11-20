<?php do_action( 'learn_press_admin_before_section_loop_item', $item, $section );?>
<li <?php learn_press_admin_section_loop_item_class( $item, $section );?> data-text="<?php echo esc_attr($item->post_title);?>" data-id="<?php echo $item->lp_si_ID; ?>" data-item_id="<?php echo $item->ID;?>" data-type="<?php echo $item->post_type; ?>">
	<?php do_action( 'learn_press_admin_begin_section_item', $item, $section );?>
	<span class="handle learn-press-icon"></span>
	<input type="text" name="_lp_curriculum[__SECTION__][items][<?php echo $item->lp_si_ID ? $item->lp_si_ID : '__ITEM__';?>][name]" class="lp-item-name no-submit" data-field="item-name" value="<?php echo esc_attr( $item->post_title );?>" />
	<input type="hidden" name="_lp_curriculum[__SECTION__][items][<?php echo $item->lp_si_ID ? $item->lp_si_ID : '__ITEM__';?>][old_name]" value="<?php echo esc_attr( $item->post_title );?>" />
	<input type="hidden" name="_lp_curriculum[__SECTION__][items][<?php echo $item->lp_si_ID ? $item->lp_si_ID : '__ITEM__';?>][item_id]" value="<?php echo $item->ID; ?>" />
	<input type="hidden" name="_lp_curriculum[__SECTION__][items][<?php echo $item->lp_si_ID ? $item->lp_si_ID : '__ITEM__';?>][ID]" value="<?php echo $item->lp_si_ID; ?>" />
	<input type="hidden" class="lp-item-type" name="_lp_curriculum[__SECTION__][items][<?php echo $item->lp_si_ID ? $item->lp_si_ID : '__ITEM__';?>][post_type]" value="<?php echo $item->post_type; ?>" />
	<p class="lp-item-actions">
		<?php do_action( 'learn_press_admin_begin_section_item_actions', $item, $section );?>
		<a href="" class="lp-item-action lp-sort-item"><?php _e( 'Move', 'learn_press' ); ?></a> |
		<a href="<?php echo is_numeric( $item->ID ) ? get_edit_post_link( $item->ID ) : '{{data.edit_link}}'; ?>" class="lp-item-action lp-edit" target="_blank"><?php _e( 'Edit', 'learn_press' ); ?></a> |
		<a href="" class="lp-item-action lp-remove"><?php _e( 'Remove', 'learn_press' ); ?></a>
		<?php do_action( 'learn_press_admin_end_section_item_actions', $item, $section );?>
	</p>
	<?php do_action( 'learn_press_admin_end_section_item', $item, $section );?>
</li>