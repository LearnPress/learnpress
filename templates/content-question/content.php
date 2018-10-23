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
    <div class="learn-press-quiz-list-questions">

        <div v-for="(question, questionIndex) in questions" v-show="isLoading || currentQuestion==question.id"
             class="quiz-question"
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
        </div>
    </div>
    <div v-show="hasQuestions()" class="question-nav">
        <button type="button" @click="_prev($event)"
                v-show="!isFirst"><?php esc_html_e( 'Prev', 'learnpress' ); ?></button>
        <button type="button" @click="_next($event)"
                v-show="!isLast"><?php esc_html_e( 'Next', 'learnpress' ); ?></button>

        <button type="button" :data-counter="checkCount"
                :disabled="!canCheckQuestion()"
                @click="_doCheckAnswer">
            {{buttonCheckLabel()}}
        </button>

        <button v-show="hasHint()" type="button" :data-counter="hintCount" :disabled="!canHintQuestion()"
                @click="_doHintAnswer">
            {{buttonHintLabel()}}
        </button>

        <button type="button" @click="_complete($event)"><?php _e( 'Complete', 'learnpress' ); ?></button>

    </div>
    <ul v-show="hasQuestions()" class="question-numbers">
        <template v-for="(question, index) in questionIds">
            <li :class="{current: currentQuestion == question}"><a
                        @click="_moveToQuestion($event, index)">{{index+1}}</a></li>

        </template>
    </ul>
</div>