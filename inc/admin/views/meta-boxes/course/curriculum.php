<?php
global $post;
$course_sections = $course->get_curriculum();
$hidden_sections = (array) get_post_meta( $post->ID, '_admin_hidden_sections', true );
?>
<div id="lp-course-curriculum" class="lp-course-curriculum">
	<h3 class="curriculum-heading">
		<?php _e( 'Curriculum', 'learnpress' ); ?>
		<span class="description"><?php _e( 'Outline your course and add content with sections, lessons and quizzes.', 'learnpress' ); ?></span>

		<p align="right" class="items-toggle">
			<a href="" data-action="expand" class="dashicons dashicons-arrow-down<?php echo !sizeof( $hidden_sections ) ? ' hide-if-js' : ''; ?>" title="<?php _e( 'Expand All', 'learnpress' ); ?>"></a>
			<a href="" data-action="collapse" class="dashicons dashicons-arrow-up<?php echo sizeof( $hidden_sections ) ? ' hide-if-js' : ''; ?>" title="<?php _e( 'Collapse All', 'learnpress' ); ?>"></a>
		</p>
	</h3>
	<!---->
	<ul class="curriculum-sections">
		<?php
		if ( $course_sections ):
			foreach ( $course_sections as $k => $section ):

				$content_items = '';

				if ( $section->items ):
					foreach ( $section->items as $item ):
						$loop_item_view = learn_press_get_admin_view( 'meta-boxes/course/loop-item.php' );
						ob_start();
						include $loop_item_view;
						$content_items .= "\n" . ob_get_clean();
					endforeach;
				endif;

				include learn_press_get_admin_view( 'meta-boxes/course/loop-section.php' );
			endforeach;
			unset( $content_items );
		endif;
		if ( !empty( $section ) )
			foreach ( get_object_vars( $section ) as $k => $v ) {
				$section->{$k} = null;
			}
		include learn_press_get_admin_view( 'meta-boxes/course/loop-section.php' );
		?>
	</ul>
</div>