<?php
$exclude_quiz   = array();
$exclude_lesson = array();
$current_user   = get_current_user_id();
global $wpdb;
$q = $wpdb->prepare(
	"SELECT         pm.meta_value
					FROM            $wpdb->posts        AS p
					INNER JOIN      $wpdb->postmeta     AS pm  ON p.ID = pm.post_id
						WHERE           p.post_type = %s
						AND 			p.post_author = %d
						AND             pm.meta_key = %s",
	LP()->course_post_type,
	$current_user,
	'_lpr_course_lesson_quiz'
);
$used_item = $wpdb->get_col(
$q
);

for ( $i = 0; $i < count( $used_item ); $i ++ ) {
	$lesson_quiz_array = unserialize( $used_item[$i] );
	for ( $j = 0; $j < count( $lesson_quiz_array ); $j ++ ) {
		if ( isset($lesson_quiz_array[$j]['lesson_quiz']) ) {
			foreach ( $lesson_quiz_array[$j]['lesson_quiz'] as $key => $value ) {
				array_push( $exclude_lesson, $value );
				array_push( $exclude_quiz, $value );
			}
		}
	}
}
global $post;
?><!-- -->
<div id="lp-course-curriculum" class="lp-course-curriculum">
	<p class="lp-course-curriculum-toggle">
		<a href="" class="expand" data-action="expand"><?php _e( 'Expand All', 'learn_press' ); ?></a>
		<a href="" class="close" data-action="close"><?php _e( 'Collapse All', 'learn_press' ); ?></a>
	</p>
	<?php _e( 'Outline your course and add content with sections, lessons and quizzes.', 'learn_press');?>
	<ul class="lp-curriculum-sections">
		<?php
		global $post;
		$course_sections = $course->get_curriculum();
		$section_state   = get_post_meta( $post->ID, '_lpr_course_section_state', true );
		if ( $course_sections ):
			foreach ( $course_sections as $k => $section ):

				$content_items = '';

				if ( $section->items ):
					foreach ( $section->items as $item ):
						if ( LP()->quiz_post_type == $item->post_type ) $exclude_quiz[] = $item->ID;
						if ( LP()->lesson_post_type == $item->post_type ) $exclude_lesson[] = $item->ID;
						$loop_item_view = learn_press_get_admin_view( 'meta-boxes/course/loop-item.php' );
						ob_start();
						include $loop_item_view;
						$content_items .= "\n" . ob_get_clean();
					endforeach;
				endif;

				learn_press_admin_view(
					'meta-boxes/course/loop-section.php',
					array(
						'class' 		=> $section->is_closed ? 'closed' : '',
						'content_items' => $content_items,
						'toggle_class'	=> ! $section->is_closed ? "dashicons-minus" : "dashicons-plus",
						'section_name'	=> $section->name,
						'section'		=> $section
					)
				);
			endforeach;
		endif;
		learn_press_admin_view(
			'meta-boxes/course/loop-section.php',
			array(
				'class' 		=> 'lp-section-empty',
				'content_items' => '',
				'toggle_class'	=> "dashicons-minus",
				'section_name'	=> ''
			)
		);
		?>
	</ul>
</div>