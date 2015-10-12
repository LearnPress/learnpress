<?php global $post;?>
<script type="text/html" id="tmpl-quick-add-lesson">
	<div id="lpr-quick-add-lesson-form" class="lpr-quick-add-form">
		<input type="text" name="" />
		<button type="button" class="button" data-action="cancel"><?php _e( 'Cancel [ESC]', 'learn_press' ); ?></button>
		<button type="button" class="button" data-action="add"><?php _e( 'Add [Enter]', 'learn_press' ); ?></button>
		<span class="lpr-ajaxload">...</span>
	</div>
</script>
<script type="text/html" id="tmpl-quick-add-quiz">
	<div id="lpr-quick-add-quiz-form" class="lpr-quick-add-form">
		<input type="text" name="" />
		<button type="button" class="button" data-action="cancel"><?php _e( 'Cancel [ESC]', 'learn_press' ); ?></button>
		<button type="button" class="button" data-action="add"><?php _e( 'Add [Enter]', 'learn_press' ); ?></button>
		<span class="lpr-ajaxload">...</span>
	</div>
</script>
<script type="text/html" id="tmpl-curriculum-section">
	<?php
	learn_press_admin_view(
		'meta-boxes/course/loop-section.php',
		array(
			'class' 		=> 'lp-section-empty',
			'toggle_class'	=> 'dashicons-minus',
			'section_name'	=> '',
			'content_items'	=> ''
		)
	);
	?>
</script>
<script type="text/html" id="tmpl-section-item">
	<?php
	learn_press_admin_view(
		'meta-boxes/course/loop-item.php',
		array(
			'item' => learn_press_post_object( array( 'post_type' => LP()->lesson_post_type ) )
		)
	)
	?>
</script>

<?php $curriculum_items = LP_Course::get_course( $post )->get_curriculum_items( array( 'group' => true, 'field' => 'ID' ) );?>
<script type="text/html" id="tmpl-lp-modal-search-lesson">
	<div id="lp-modal-search-lesson" class="lpr-dynamic-form">
		<?php
		$lessons = learn_press_get_current_user()->get_lessons();
		?>
		<ul>
			<?php
			if ( $lessons ) {
				foreach( $lessons as $lesson ){
					echo '<li data-id="' . $lesson->ID . '" data-type="' . $lesson->post_type . '" data-text="' . esc_attr( $lesson->post_title ) . '">' . $lesson->post_title . '</li>';
				}
			}
			?>
			<li class="lp-search-no-results" data-id="0"><?php _e( 'No results', 'learn_press' );?></li>
		</ul>
	</div>
</script>
<script type="text/html" id="tmpl-lp-modal-search-quiz">
	<div id="lp-modal-search-quiz" class="lp-modal-search">
		<?php
		$exclude_quizzes = $curriculum_items['quizzes'];
		$quizzes = learn_press_get_current_user()->get_quizzes();
		?>
		<ul>
			<?php
			if ( $quizzes ) {
				foreach( $quizzes as $quiz ){
					$hidden = in_array( $quiz->ID, $exclude_quizzes ) ? 'selected hide-if-js' : '';
					echo '<li class="'.$hidden.'" data-id="' . $quiz->ID . '" data-type="' . $quiz->post_type . '" data-text="' . esc_attr( $quiz->post_title ) . '">' . $quiz->post_title . '</li>';
				}
			}
			?>
			<li class="lp-search-no-results" data-id="0"><?php _e( 'No results', 'learn_press' );?></li>
		</ul>
	</div>
</script>