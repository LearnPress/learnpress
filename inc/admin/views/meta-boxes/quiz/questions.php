<?php
global $post;
$quiz           = LP_Quiz::get_quiz( $post->ID );
$current_user   = get_current_user_id();
$question_types = learn_press_question_types();
$exclude_ids    = array();
$questions      = $quiz->get_questions();
$hidden         = (array) get_post_meta( $quiz->id, '_admin_hidden_questions', true );
$question_ids   = $questions ? array_keys( $questions ) : array();

$hidden_all = sizeof( $hidden ) && ( sizeof( array_diff( $hidden, $question_ids ) ) == 0 );
?>
<div id="learn-press-quiz-questions-wrap">
	<h3 class="quiz-questions-heading">
		<?php _e( 'Questions', 'learn_press' ); ?>
		<p align="right" class="questions-toggle">
			<a href="" data-action="expand"<?php echo $hidden_all ? '' : ' class="hide-if-js"';?>><?php _e( 'Expand All', 'learn_press' ); ?></a>
			<a href="" data-action="collapse"<?php echo !$hidden_all ? '' : ' class="hide-if-js"';?>><?php _e( 'Collapse All', 'learn_press' ); ?></a>
		</p>
	</h3>

	<div id="learn-press-list-questions">
		<?php if ( $questions ): $index = 0; ?>
			<?php foreach ( $questions as $question ): ?>
				<?php
				$question      = LP_Question_Factory::get_question( $question );
				$question_view = learn_press_get_admin_view( 'meta-boxes/quiz/question.php' );
				include $question_view;
				?>
			<?php endforeach; ?>
			<?php $exclude_ids = array_keys( $questions ); endif; ?>
	</div>
	<div class="question-actions">
		<div id="learn-press-dropdown-questions">
			<input type="text" name="question" id="learn-press-question-name" />
			<button id="learn-press-toggle-questions" type="button" class="dashicons dashicons-arrow-down"></button>
			<ul>
				<?php
				$query_args = array(
					'post_type'      => LP()->question_post_type,
					'post_status'    => 'publish',
					'author'         => $current_user,
					'posts_per_page' => - 1,
					//'post__not_in'   => $exclude_ids
				);
				$query      = new WP_Query( $query_args );
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$p = $query->next_post();
						?>
						<li class="question<?php echo in_array( $p->ID, $exclude_ids ) ? ' added' : ''; ?>">
							<a href="" data-id="<?php echo $p->ID; ?>"><?php echo $p->post_title; ?></a></li>
						<?php
					}
				}
				?>
			</ul>
		</div>
		<button id="learn-press-button-add-question" class="button" type="button"><?php _e( 'New Question', 'learn_press' ); ?></button>
	</div>
	<br />

	<?php /*

		<div class="btn-group" id="learn-press-add-new-question">
			<button type="button" class="btn btn-default" data-type="single_choice"><?php _e( 'Add new Question', 'learn_press' ); ?></button>
			<button type="button" class="btn btn-default dropdown-toggle">
				<span class="caret"></span>
				<span class="sr-only"><?php _e( 'Toggle Dropdown', 'learn_press' ); ?></span>
			</button>
			<ul class="dropdown-menu" role="menu">
				<?php if ( $question_types ): ?>
					<?php foreach ( $question_types as $slug => $name ): ?>
						<li><a href="" rel="<?php echo $slug; ?>"><?php echo $name; ?></a></li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>
		<?php _e( '-Or-', 'learn_press' ); ?>
		<select class="lpr-select2" name="" id="lpr-quiz-question-select-existing" style="width:300px">
			<option value=""><?php _e( '--Select existing question--', 'learn_press' ); ?></option>
			<?php

			$query_args = array(
				'post_type'      => LP()->question_post_type,
				'post_status'    => 'publish',
				'author'         => $current_user,
				'posts_per_page' => - 1,
				'post__not_in'   => $qids
			);
			$query      = new WP_Query( $query_args );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$p = $query->next_post();
					echo '<option value="' . $p->ID . '" data-type="">' . $p->post_title . '</option>';
				}
			}
			?>

		</select>
 */
	?>
</div>