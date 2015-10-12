<li class="lp-section-item<?php echo $item->ID ? ' lp-item-' . $item->post_type : ' lp-item-empty lp-item-new';?> lp-item-<?php echo $item->post_type; ?>" data-text="<?php echo esc_attr($item->post_title);?>" data-id="<?php echo $item->ID; ?>" data-type="<?php echo $item->post_type; ?>">
	<span class="handle dashicons"></span>
	<input type="text" name="" class="lp-item-name" data-field="item-name" value="<?php echo esc_attr( $item->post_title );?>" />
	<!-- <span class="lp-title" title="<?php _e( 'Click to quick edit', 'learn_press' ); ?>"><?php echo $item->post_title; ?></span>-->
	<a href="" class="lp-remove"><?php _e( 'Remove', 'learn_press' ); ?></a>
	<a href="<?php echo get_edit_post_link( $item->ID ); ?>" target="_blank"><?php _e( 'Edit', 'learn_press' ); ?></a>
	<input type="hidden" name="_lpr_course_lesson_quiz[__SECTION__][lesson_quiz][]" value="<?php echo $item->ID; ?>" />
</li>