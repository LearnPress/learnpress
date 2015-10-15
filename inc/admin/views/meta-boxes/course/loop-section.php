<?php
/**
 * Template for displaying the loop of section
 *
 * @param $class
 * @param $toggle_class
 * @param $section_name
 * @param $content_items
 */
?>
<li class="lp-curriculum-section<?php echo $class ? " {$class}" : ''; ?>">
	<h3 class="lp-section-head">
		<span class="lp-action lp-toggle" title="<?php _e( 'Expand/Close', 'learn_press' ); ?>">
			<i class="dashicons"></i>
		</span>
		<span class="lp-action lp-sort"><i class="dashicons dashicons-sort"></i></span>
		<span class="lp-action lp-remove" title="<?php _e( 'Remove', 'learn_press' ); ?>"><i class="dashicons dashicons-no"></i></span>
		<span class="lp-section-icon"><i class="dashicons dashicons-pressthis"></i></span>
		<span class="lp-section-name-wrapper">
			<input name="_lp_curriculum[__SECTION__][name]" type="text" data-field="section-name" placeholder="<?php _e( 'Enter the section name and hit enter', 'learn_press' );?>" class="lp-section-name" value="<?php echo esc_attr( $section_name ); ?>" />
			<input name="_lp_curriculum[__SECTION__][ID]" type="hidden" value="<?php echo $section->ID; ?>" />
		</span>
	</h3>
	<div class="lp-curriculum-section-content">
		<ul class="lp-section-items">
			<?php echo $content_items;?>
			<?php learn_press_admin_view( 'meta-boxes/course/loop-item.php', array( 'item' => learn_press_post_object( array( 'post_type' => LP()->lesson_post_type ) ) ) );?>
		</ul>
		<p class="lp-add-buttons">
			<?php do_action( 'learn_press_before_section_buttons' );?>
			<?php
			if( $buttons = apply_filters( 'learn_press_loop_section_buttons', array() ) ):
				foreach( $buttons as $button ){
					$button = wp_parse_args(
						$button,
						array(
							'id'	=> '',
							'text'	=> '',
							'class'	=> 'button'
						)
					);
					$button['class'] = is_array( $button['class'] ) ? join( ' ', $button['class'] ) : '';
				?>
				<button class="<?php echo $button['class'] ? ' ' . $button['class'] : '';?>" id="<?php echo $button['id'];?>" type="button">
					<?php echo $button['text']; ?>
				</button>
				<?php
				}
			endif;?>
			<!--<button class="button button-primary" type="button" data-action="add-lesson"><?php _e( 'Add Lesson', 'learn_press' ); ?></button>
			<button class="button button-primary" type="button" data-action="add-quiz"><?php _e( 'Add Quiz', 'learn_press' ); ?></button>
			<button class="button button-primary" type="button" data-action="quick-add-lesson"><?php _e( 'Quick add <span>L</span>esson', 'learn_press' ); ?></button>
			<button class="button button-primary" type="button" data-action="quick-add-quiz"><?php _e( 'Quick add <span>Q</span>uiz', 'learn_press' ); ?></button>-->
			<?php do_action( 'learn_press_after_section_buttons' );?>
		</p>
	</div>
</li>