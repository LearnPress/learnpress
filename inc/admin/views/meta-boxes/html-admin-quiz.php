<?php
global $post;
$quiz = learn_press_get_quiz( $post );
?>
<div id="learn-press-quiz-questions" class="learn-press-box-data" ng-controller="quiz">
    <div class="lp-box-data-head">
            <span class="lp-count-questions hide-if-js">{{countQuestion('<?php esc_attr_e( '%d question', 'learnpress' ); ?>
                ', '<?php esc_attr_e( '%d questions', 'learnpress' ); ?>')}}</span>
        <div class="lp-box-data-actions lp-toolbar-buttons">
            <span class="lp-toolbar-btn lp-btn-toggle learn-press-tooltip" data-tooltip="%s"
                  ng-click="toggleContent($event)">
                <a class="lp-btn-icon dashicons dashicons-arrow-up"></a>
                <a class="lp-btn-icon dashicons dashicons-arrow-down"></a>
            </span>
        </div>
    </div>
    <div class="lp-box-data-content">
        <div id="learn-press-questions">
			<?php
			$questions = $quiz->get_questions();
			foreach ( $questions as $question_id ) {
				if ( $question = learn_press_get_question( $question_id ) ) {
					$question->admin_interface();
				} else {

				}
			}
			?>
        </div>
        <div class="lp-quiz-no-question-msg"
             ng-show="countQuestion() < 1"><?php esc_html_e( 'No question.', 'learnpress' ); ?></div>
        <div class="lp-toolbar-buttons">
            <div class="button lp-group-button">
                <button class="button" type="button"
                        ng-click="addQuestion($event);"><?php _e( 'Add Question', 'learnprress' ); ?></button>
                <div class="lp-toolbar-btn lp-toolbar-btn-dropdown" id="learn-press-button-add-question">
                    <button class="button" type="button"
                            ng-click="addQuestion($event);"><?php _e( '+', 'learnprress' ); ?></button>
					<?php
					LP_Question_Factory::list_question_types( array( 'li_attr' => 'ng-click="addQuestion($event, {type: \'{{type}}\'})"' ) );
					?>
                </div>
            </div>
        </div>
    </div>
    <script type="text/html" class="quiz-element-data">
		<?php echo json_encode( array( 'id' => $post->ID ) ); ?>
    </script>
</div>
<script type="text/javascript">
    var quizQuestions = <?php echo json_encode( $questions );?>;
</script>
