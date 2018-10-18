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

?>


    <div :id="'learn-press-quiz-'+item.id" :class="mainClass()">
        <ul id="learn-press-quiz-list-questions">

            <li v-for="(question, questionIndex) in questions" v-show="isLoading || currentQuestion==question.id" class="quiz-question"
                :id="'quiz-question-' + question.id" :data-id="question.id">
			    <?php
			    do_action( 'learn-press/question-content-summary' );
			    ?>
                {{answers}}
                <div v-html="question.content"></div>
                <template v-if="isCheckedQuestion(question.id) && getQuestionExplanation()">
                    <div class="question-explanation-content">
                        <strong class="explanation-title"><?php esc_html_e( 'Explanation:', 'learnpress' ); ?></strong>
                        {{getQuestionExplanation(question.id)}}
                    </div>
                </template>
                <template v-if="isHintedQuestion(question.id) && getQuestionHint()">
                    {{getQuestionHint(question.id)}}
                </template>
            </li>
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
                  data-confirm="Do you want to complete quiz?"
                  data-action="complete-quiz"
                  class="complete-quiz form-button lp-form" method="post" enctype="multipart/form-data">

			    <?php do_action( 'learn-press/quiz/begin-complete-button' ); ?>

                <button type="button" @click="_complete($event)"><?php _e( 'Complete', 'learnpress' ); ?></button>

			    <?php do_action( 'learn-press/quiz/end-complete-button' ); ?>

			    <?php //LP_Nonce_Helper::quiz_action( 'complete', $quiz->get_id(), get_the_ID() ); ?>
                <input type="hidden" name="noajax" value="yes">

            </form>
        </div>
        <ul class="question-numbers">
            <template v-for="(question, index) in questionIds">
                <li :class="{current: currentQuestion == question}"><a
                            @click="_moveToQuestion($event, index)">{{index+1}}</a></li>

            </template>
        </ul>
    </div>