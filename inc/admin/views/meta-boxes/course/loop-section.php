<?php
/**
 * Template for displaying the loop of section
 *
 * @param $class
 * @param $toggle_class
 * @param $section_name
 * @param $content_items
 */
$is_hidden = $section->ID && is_array( $hidden_sections ) && in_array( $section->ID, $hidden_sections );
$class = array(
	'curriculum-section'
);
if( !$section->ID ){
	$class[] = 'lp-empty-section';
}
if( $is_hidden ){
	$class[] = 'is_hidden';
}
?>
<li class="<?php echo join(' ', $class ); ?>" data-id="<?php echo $section ? $section->ID : '';?>">
	<h3 class="curriculum-section-head">
		<input name="_lp_curriculum[__SECTION__][name]" type="text" data-field="section-name" placeholder="<?php _e( 'Enter the section name and hit enter', 'learn_press' );?>" class="lp-section-name no-submit" value="<?php echo esc_attr( $section->section_name ); ?>" />
		<p class="lp-section-actions">
			<a href="" data-action="expand"<?php echo $is_hidden ? '' : ' class="hide-if-js"';?>><?php _e( 'Expand', 'learn_press' );?></a>
			<a href="" data-action="collapse"<?php echo ! $is_hidden ? '' : ' class="hide-if-js"';?>><?php _e( 'Collapse', 'learn_press' );?></a>
			<a href="" data-action="remove"><?php _e( 'Remove', 'learn_press' );?></a>
			<a href="" data-action="move"><?php _e( 'Move', 'learn_press' );?></a>
		</p>
	</h3>
	<div class="curriculum-section-content<?php echo $is_hidden ? ' hide-if-js' : '';?>">
		<ul class="curriculum-section-items">
			<?php echo $content_items;?>
			<?php learn_press_admin_view( 'meta-boxes/course/loop-item.php', array( 'item' => learn_press_post_object( array( 'post_type' => LP()->lesson_post_type ) ) ) );?>
		</ul>
		<?php do_action( 'learn_press_after_section_items', $section );?>
		<?php if( $buttons = apply_filters( 'learn_press_loop_section_buttons', array() ) ):?>
			<br />
			<div class="lp-add-buttons">
				<input type="text" class="regular-text no-submit" name="lp-new-item-name" placeholder="<?php _e( 'The name of new question or quiz and hit enter', 'learn_press' );?>" />
				<div class="button lp-button-dropdown lp-button-add-item disabled">
					<span class="lp-dropdown-label lp-add-new-item"><?php _e( 'Add New', 'learn_press' );?></span>
					<span class="lp-dropdown-arrow">+</span>
					<ul class="lp-dropdown-items">
						<?php foreach( learn_press_section_item_types() as $slug => $name ){?>
							<li>
								<a href="" data-type="<?php echo $slug;?>"><?php echo $name;?></a>
							</li>
						<?php } ?>
					</ul>
				</div>
				-OR-
				<button class="button " id="add-lesson" type="button" data-action="add-lesson" data-type="lp_lesson">
					<?php _e( 'Select Lessons', 'learn_press' );?>
				</button>
				<button class="button " id="add-quiz" type="button" data-action="add-quiz" data-type="lp_quiz">
					<?php _e( 'Select Quizzes', 'learn_press' );?>
				</button>
				<!--
				<button id="learn-press-button-add-question" class="button" type="button"><?php _e( 'Select Lessons', 'learn_press' ); ?></button>
				<button id="learn-press-button-add-question" class="button" type="button"><?php _e( 'Select Quizzes', 'learn_press' ); ?></button>
				-->
			</div>
			<!--
		<p class="lp-add-buttons">

			<?php do_action( 'learn_press_before_section_buttons' );?>
			<?php
				foreach( $buttons as $button ){
					$button = wp_parse_args(
						$button,
						array(
							'id'	=> '',
							'text'	=> '',
							'class'	=> 'button',
							'attr'	=> null
						)
					);
					$button['class'] = is_array( $button['class'] ) ? join( ' ', $button['class'] ) : '';

				?>
				<button class="button <?php echo $button['class'] ? ' ' . $button['class'] : '';?>" id="<?php echo $button['id'];?>" type="button"<?php echo $button['attr'] ? ' ' . $button['attr'] : '';?>>
					<?php echo $button['text']; ?>
				</button>
				<?php } ?>

			<?php do_action( 'learn_press_after_section_buttons' );?>
		</p>-->
		<?php endif; ?>
		<?php do_action( 'learn_press_after_section_content', $section );?>
	</div>
</li>