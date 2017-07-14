<?php
global $post, $wp_meta_boxes;
$quiz = learn_press_get_quiz( $post );
?>
<div id="learn-press-quiz-questions" class="learn-press-box-data" ng-controller="quiz">
    <div class="lp-box-data-head">
        <h3>{{htmlCountQuestions('<?php _e( '%d question', 'learnpress' ); ?>',
            '<?php _e( '%d questions', 'learnpress' ); ?>')}}</h3>
    </div>
    <div class="lp-box-data-content">
        <table id="lp-list-questions" class="lp-list-questions">
            <thead>
            <tr>
                <th class="lp-column-sort"></th>
                <th class="lp-column-order">#</th>
                <th class="lp-column-name"><?php _e( 'Name', 'learnpress' ); ?></th>
                <th class="lp-column-type"><?php _e( 'Type', 'learnpress' ); ?></th>
                <th class="lp-column-actions"><?php _e( 'Actions', 'learnpress' ); ?></th>
            </tr>
            </thead>
			<?php
			$questions = $quiz->get_questions();
			$index     = 0;
			foreach ( $questions as $question_id ) {
				if ( $question = learn_press_get_question( $question_id ) ) {
					include "html-loop-question.php";
				}
			} ?>
            <tfoot>
            <tr>
                <th class="lp-column-sort">
                    <i class="fa fa-bars"></i>
                </th>
                <th class="lp-column-order">{{newQuestionIndex()}}</th>
                <th class="lp-column-name lp-column-quick-add" colspan="3">
					<?php learn_press_admin_view( 'quiz/html-search-questions' ); ?>
                    <button type="button" class="button"
                            ng-click="addNewQuestion($event)"><?php _e( 'Add as New', 'learnpress' ); ?></button>
                    <button type="button" class="button"><?php _e( 'Select', 'learnpress' ); ?></button>
                </th>
            </tr>
            </tfoot>
        </table>
    </div>
    <div class="update-overlay ng-hide" ng-show="isSubmitting">
        <div class="progress">
            <div class="progress-bar">
                <div class="progress-bar current"></div>
            </div>
            <div class="progress-percent">0%</div>
        </div>
    </div>
</div>
<script type="text/ng-template" id="learn-press-empty-question-template">
    <tbody ng-controller="question"
           id="learn-press-question-{{questionData.id}}"
           data-type="{{questionData.id}}"
           data-dbid="{{questionData.id}}"
           ng-click="elementClick($event)"
           ng-class="{'invalid-type': !isValidQuestionType()}">
    <tr>
        <td class="lp-column-sort"><i class="fa fa-bars"></i></td>
        <td class="lp-column-order">{{getQuestionIndex(this)}}</td>
        <td class="lp-column-name">
            <input type="text" class="lp-question-heading-title"
                   value=""
                   autocomplete="off">
        </td>
        <td class="lp-column-type">...</td>
        <td class="lp-column-actions">
            <div class="lp-box-data-actions lp-toolbar-buttons">
				<?php
				//echo join( "<!--\n-->", $top_buttons );
				?>
            </div>
        </td>
    </tr>
    <tr class="edit-inline hide-if-js">
        <td colspan="5">

        </td>
    </tr>
    </tbody>
</script>
