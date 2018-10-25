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
    <div class="quiz-progress">
        <div class="progress-items">
            <div class="progress-item quiz-current-question">
                <span class="progress-number">{{getQuestionIndex()+1}} / {{countQuestions()}}</span>
                <span class="progress-label"><?php esc_html_e( 'Question', 'learnpress' ); ?></span>
            </div>
            <div class="progress-item quiz-countdown">
                <span class="progress-number">
                    <span v-if="clock.d">{{clock.d}}</span>
                    <template v-else>
                        <span class="h" v-show="clock.h !== '00'">{{clock.h}}</span>
                        <span class="m">{{clock.m}}</span>
                        <span class="s">{{clock.s}}</span>
                    </template>
                </span>
                <span class="progress-label"><?php esc_html_e( 'Time remaining', 'learnpress' ); ?></span>
            </div>
        </div>
    </div>
    <div class="learn-press-quiz-list-questions">
        <template v-for="(question, questionIndex) in questions" name="" @after-enter="_transitionEnter">
            <div v-show="isLoading || currentQuestion==question.id"
                 class="quiz-question"
                 :key="questionIndex"
                 :id="'quiz-question-' + question.id" :data-id="question.id">
				<?php
				do_action( 'learn-press/question-content-summary' );
				?>
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
        </template>
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