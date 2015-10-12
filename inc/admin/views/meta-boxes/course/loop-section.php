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
			<i class="dashicons<?php echo $toggle_class ? " {$toggle_class}" : ''; ?>"></i>
		</span>
		<span class="lp-action lp-sort"><i class="dashicons dashicons-sort"></i></span>
		<span class="lp-action lp-remove" title="<?php _e( 'Remove', 'learn_press' ); ?>"><i class="dashicons dashicons-no"></i></span>
		<span class="lp-section-icon"><i class="dashicons dashicons-pressthis"></i></span>
		<span class="lp-section-name-wrapper">
			<input name="_lpr_course_lesson_quiz[__SECTION__][name]" type="text" data-field="section-name" placeholder="<?php _e( 'Enter the section name and hit enter', 'learn_press' );?>" class="lp-section-name" value="<?php echo $section_name; ?>" />
		</span>
	</h3>
	<div class="lp-curriculum-section-content">
		<ul class="lp-section-items">
			<?php echo $content_items;?>
			<?php learn_press_admin_view( 'meta-boxes/course/loop-item.php', array( 'item' => learn_press_post_object( array( 'post_type' => LP()->lesson_post_type ) ) ) );?>
		</ul>
		<p class="lp-add-buttons">
			<?php do_action( 'learn_press_before_section_buttons' );?>
			<!--<button class="button button-primary" type="button" data-action="add-lesson"><?php _e( 'Add Lesson', 'learn_press' ); ?></button>
			<button class="button button-primary" type="button" data-action="add-quiz"><?php _e( 'Add Quiz', 'learn_press' ); ?></button>
			<button class="button button-primary" type="button" data-action="quick-add-lesson"><?php _e( 'Quick add <span>L</span>esson', 'learn_press' ); ?></button>
			<button class="button button-primary" type="button" data-action="quick-add-quiz"><?php _e( 'Quick add <span>Q</span>uiz', 'learn_press' ); ?></button>-->
			<?php do_action( 'learn_press_after_section_buttons' );?>
		</p>
	</div>
</li>