<?php
/**
 * Template for displaying quiz result.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/content-quiz/result.php.
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

<div class="quiz-result" :class="[results.grade || results.grade_text]"
     v-show="hasAccessLevel(30, '=') && !isReviewing">

    <h3><?php _e( 'Your Result', 'learnpress' ); ?></h3>
    <div class="result-grade">
        <span class="result-achieved">{{getResultFormatted(2)}}%</span>
        <span class="result-require">{{passingGrade}}</span>
        <p class="result-message" v-html="getResultMessage()"></p>
    </div>

    <ul class="result-statistic">
        <li class="result-statistic-field">
            <label><?php echo _x( 'Time spend', 'quiz-result', 'learnpress' ); ?></label>
            <p>{{results.time_spend}}</p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Questions', 'quiz-result', 'learnpress' ); ?></label>
            <p>{{countQuestions()}}</p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Correct', 'quiz-result', 'learnpress' ); ?></label>
            <p>{{results.question_correct}}</p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Wrong', 'quiz-result', 'learnpress' ); ?></label>
            <p>{{results.question_wrong}}</p>
        </li>
        <li class="result-statistic-field">
            <label><?php echo _x( 'Skipped', 'quiz-result', 'learnpress' ); ?></label>
            <p>{{results.question_empty}}</p>
        </li>
    </ul>

</div>