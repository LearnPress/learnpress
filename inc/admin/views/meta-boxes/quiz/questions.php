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
		<?php _e( 'Questions', 'learnpress' ); ?>
		<p align="right" class="questions-toggle">
			<a href="" data-action="expand" class="dashicons dashicons-arrow-down <?php echo $hidden_all ? '' : ' hide-if-js';?>" title="<?php _e( 'Expand All', 'learnpress' ); ?>"></a>
			<a href="" data-action="collapse" class="dashicons dashicons-arrow-up <?php echo !$hidden_all ? '' : ' hide-if-js';?>" title="<?php _e( 'Collapse All', 'learnpress' ); ?>"></a>
		</p>
	</h3>

	<div id="learn-press-list-questions">
		<?php if ( $questions ): $index = 0; ?>
			<?php foreach ( $questions as $question ): ?>
				<?php
				$question      = LP_Question_Factory::get_question($question );
				$question_view = learn_press_get_admin_view( 'meta-boxes/quiz/question.php' );
				include $question_view;
				?>
			<?php endforeach; ?>
			<?php $exclude_ids = array_keys( $questions ); endif; ?>
	</div>
	<div class="question-actions">
		<input type="text" class="regular-text no-submit" name="lp-new-question-name" placeholder="<?php _e( 'Add question title and press Enter', 'learnpress' );?>" />
		<div class="button lp-button-dropdown lp-button-add-question disabled">
			<span class="lp-dropdown-label lp-add-new-item"><?php _e( 'Add New', 'learnpress' );?></span>
			<span class="lp-dropdown-arrow">+</span>
			<ul class="lp-dropdown-items">
				<?php foreach( learn_press_question_types() as $slug => $name ){?>
					<li>
						<a href="" data-type="<?php echo $slug;?>"><?php echo $name;?></a>
					</li>
				<?php } ?>
			</ul>
		</div>
		<?php _e( '-OR-', 'learnpress' ); ?>
		<button id="learn-press-button-add-question" class="button" type="button"><?php _e( 'Add Existing Question', 'learnpress' ); ?></button>
	</div>
</div>
<?php wp_reset_postdata(); ?>