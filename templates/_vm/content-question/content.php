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

<div v-show="hasAccessLevel(20, '=') || isReviewing" :id="'learn-press-quiz-'+item.id" :class="mainClass()" tabindex="0"
     @keypress="_questionsNav($event)">
    <div class="quiz-progress">
        <div class="progress-items">
            <div class="progress-item quiz-current-question">
                <span class="progress-number">{{getQuestionIndex()+1}} / {{countQuestions()}}</span>
                <span class="progress-label"><?php esc_html_e( 'Question', 'learnpress' ); ?></span>
            </div>
            <div class="progress-item quiz-countdown" :class="timeWarningClass()">
                <span class="progress-number">
                    <span v-if="clock.d">{{clock.d}}</span>
                    <template v-else>
                        <span class="h" v-show="totalTime >= 3600">{{clock.h}}</span>
                        <span class="m">{{clock.m}}</span>
                        <span class="s">{{clock.s}}</span>
                    </template>
                </span>
                <span class="progress-label"><?php esc_html_e( 'Time remaining', 'learnpress' ); ?></span>
            </div>
        </div>
    </div>
    [{{item.quizData ? item.quizData.historyId : ''}}]
    <div class="learn-press-quiz-list-questions">
        <template v-for="(question, questionIndex) in questions" name="" @after-enter="_transitionEnter">
            <div v-show="isLoading || currentQuestion==question.id"
                 class="quiz-question" :class="[question.type]"
                 :key="questionIndex"
                 :data-type="question.type"
                 :id="'quiz-question-' + question.id" :data-id="question.id">
				<?php
				do_action( 'learn-press/question-content-summary' );
				?>
                <div v-html="question.content"></div>
                <component :is="getQuestionTypeAnswers(question.type)" :question="question" inline-template>
                    <div>
                        {{question.optionAnswers}}
                        <ul v-show="$parent.isDefaultQuestionType(question.type)" :id="'answer-options-'+question.id"
                            :class="question.answersClass">
                            <li v-for="(answer, answerId) in question.optionAnswers" :class="getAnswerClass(answer)" @click="_triggerEvent($event)">
                                <input v-if="question.type==='multi_choice'" type="checkbox" class="option-check"
                                       :name="'learn-press-question-'+question.id" :value="answer.value"
                                       v-model="answer.checked" :key="answerId">
                                <input v-else type="radio" class="option-check"
                                       :name="'learn-press-question-'+question.id"
                                       :value="answer.value" v-model="answer.checked" :key="answerId">
                                <div class="option-title">
                                    <div class="option-title-content" v-html="answer.text"></div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </component>
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

        <button v-if="status!=='completed'" type="button" :data-counter="checkCount"
                :disabled="false && !canCheckQuestion()"
                @click="_doCheckAnswer">
            {{buttonCheckLabel()}}
        </button>

        <button v-if="status!=='completed'" v-show="hasHint()" type="button" :data-counter="hintCount"
                :disabled="!canHintQuestion()"
                @click="_doHintAnswer">
            {{buttonHintLabel()}}
        </button>

        <button v-if="status!=='completed'" type="button"
                @click="_complete($event)"><?php _e( 'Complete', 'learnpress' ); ?></button>
    </div>
    <ul v-show="hasQuestions()" class="question-numbers">
        <template v-for="(question, index) in questionIds">
            <li :class="{current: currentQuestion == question}"><a
                        @click="_moveToQuestion($event, index)">{{index+1}}</a>
            </li>
        </template>
    </ul>
</div>