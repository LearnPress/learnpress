<?php
global $post;
$quiz        = learn_press_get_quiz( $post );
$box_classes = array( 'learn-press-box-data' );
?>
<div id="learn-press-quiz-questions" class="<?php echo join( ' ', $box_classes ); ?>" ng-controller="quiz">
    <div class="lp-box-data-head">
            <span class="lp-count-questions hide-if-js">{{countQuestion('<?php esc_attr_e( '%d question', 'learnpress' ); ?>
                ', '<?php esc_attr_e( '%d questions', 'learnpress' ); ?>')}}</span>
        <div class="lp-box-data-actions lp-toolbar-buttons">
            <span class="lp-toolbar-btn" ng-click="saveAllQuestions($event)">
                <a class="lp-btn-icon dashicons dashicons-location"></a>
            </span><!--
            --><span class="lp-toolbar-btn lp-btn-toggle learn-press-tooltip<?php echo learn_press_is_hidden_post_box( $quiz->get_id() ) ? ' closed' : '';?>"
                     data-tooltip="<?php esc_attr_e( 'Save all', 'learnpress' ); ?>"
                     ng-click="toggleContent($event)">
                <a class="lp-btn-icon dashicons dashicons-arrow-up-alt2"></a>
                <a class="lp-btn-icon dashicons dashicons-arrow-down-alt2"></a>
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
		<?php echo json_encode( $quiz->get_admin_config() ); ?>
    </script>
</div>
