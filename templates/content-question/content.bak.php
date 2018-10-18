<?php
/**
 * Template for displaying content of question.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-question/content.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

global $lp_quiz_question;

$user         = LP_Global::user();
$quiz         = LP_Global::course_item_quiz();
$old_question = LP_Global::quiz_question();
?>

    <div class="content-question-summary" id="content-question-<?php echo $old_question->get_id(); ?>">
		<?php
		/**
		 * @see learn_press_content_item_summary_question_title()
		 * @see learn_press_content_item_summary_question_content()
		 * @see learn_press_content_item_summary_question()
		 */
		//do_action( 'learn-press/question-content-summary' ); ?>
    </div>
    <style>
        #learn-press-quiz {
            opacity: 0;
        }

        #learn-press-quiz.is-loaded {
            opacity: 1;
        }
    </style>
    <div id="learn-press-quiz" :class="mainClass()">
        <ul id="learn-press-quiz-list-questions">
			<?php
			$json = array(
				'checkCount'      => $user->can_check_answer( $quiz->get_id() ),
				'hintCount'       => $user->can_hint_answer( $quiz->get_id() ),
				'currentQuestion' => $user->get_current_question( $quiz->get_id(), get_the_ID() ),
				'questions'       => array()
			);

			$questions = $quiz->get_question_ids();
			foreach ( $questions as $question_id ) {
				$question = learn_press_get_question( $question_id );

				$lp_quiz_question = $question;
				?>
                <li v-show="isLoading || currentQuestion==<?php echo $question_id; ?>" class="quiz-question"
                    id="quiz-question-<?php echo $question_id; ?>" :data-id="<?php echo $question_id; ?>">
					<?php
					do_action( 'learn-press/question-content-summary' );
					?>
                    <template v-if="isCheckedQuestion(<?php echo $question_id; ?>) && getQuestionExplanation()">
                        <div class="question-explanation-content">
                            <strong class="explanation-title"><?php esc_html_e( 'Explanation:', 'learnpress' ); ?></strong>
                            {{getQuestionExplanation(<?php echo $question_id; ?>)}}
                        </div>
                    </template>
                    <template v-if="isHintedQuestion(<?php echo $question_id; ?>) && getQuestionHint()">
                        {{getQuestionHint(<?php echo $question_id; ?>)}}
                    </template>
                </li>
				<?php

				$checked             = $user->has_checked_answer( $question->get_id(), $quiz->get_id(), get_the_ID() );
				$hinted              = $user->has_hinted_answer( $question->get_id(), $quiz->get_id(), get_the_ID() );
				$json['questions'][] = array(
					'id'             => absint( $question_id ),
					'checked'        => $checked,
					'hinted'         => $hinted,
					'explanation'    => $checked ? $question->get_explanation() : '',
					'hint'           => $checked || $hinted ? $question->get_hint() : '',
					'hasExplanation' => ! ! $question->get_explanation(),
					'hasHint'        => ! ! $question->get_hint(),
					'permalink'      => $quiz->get_question_link( $question_id ),
					'userAnswers'    => false
				);
			}
			?>
        </ul>
        <div id="question-nav">
            <button type="button" @click="_prev($event)"
                    v-show="!isFirst"><?php esc_html_e( 'Prev', 'learnpress' ); ?></button>
            <button type="button" @click="_next($event)"
                    v-show="!isLast"><?php esc_html_e( 'Next', 'learnpress' ); ?></button>

            <form v-show="hasExplanation()" name="check-answer-question"
                  class="check-answer-question form-button lp-form lp-form-ajax"
                  method="post"
                  enctype="multipart/form-data">

                <button type="button" :data-counter="checkCount"
                        :disabled="!canCheckQuestion()"
                        @click="_doCheckAnswer">
                    {{buttonCheckLabel()}}
                </button>

            </form>

            <form v-show="hasHint()" name="question-hint" class="question-hint form-button lp-form" method="post"
                  enctype="multipart/form-data">

                <button type="button" :data-counter="hintCount" :disabled="!canHintQuestion()"
                        @click="_doHintAnswer">
                    {{buttonHintLabel()}}
                </button>
            </form>

            <form name="complete-quiz"
                  data-confirm="<?php LP_Strings::esc_attr_e( 'confirm-complete-quiz', '', array( $quiz->get_title() ) ); ?>"
                  data-action="complete-quiz"
                  class="complete-quiz form-button lp-form" method="post" enctype="multipart/form-data">

				<?php do_action( 'learn-press/quiz/begin-complete-button' ); ?>

                <button type="button" @click="_complete($event)"><?php _e( 'Complete', 'learnpress' ); ?></button>

				<?php do_action( 'learn-press/quiz/end-complete-button' ); ?>

				<?php LP_Nonce_Helper::quiz_action( 'complete', $quiz->get_id(), get_the_ID() ); ?>
                <input type="hidden" name="noajax" value="yes">

            </form>
        </div>
        <ul class="question-numbers">
            <template v-for="(question, index) in questionIds">
                <li :class="{current: currentQuestion == question}"><a
                            @click="_moveToQuestion($event, index)">{{index+1}}</a></li>

            </template>
        </ul>

        {{answers}}
        {{questions}}
    </div>

<?php $lp_quiz_question = $old_question;