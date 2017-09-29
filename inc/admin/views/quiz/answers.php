<?php
/**
 * Question answers template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-answers">
    <div class="quiz-question-data">
        <div class="lp-list-questions">
            <table class="lp-list-options">
                <thead>
                <tr>
                    <th v-for="(heading, key) in answers.heading" class="lp-column-heading" :class="headingClass(key)">
                        {{heading}}
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(answer, key) in answers.options" class="lp-list-option lp-row"
                    :class="answerClass(answer.value)"
                    :data-id="answer.value">
                    <td class="lp-column lp-column-sort"><i class="fa fa-bars"></i></td>
                    <td class="lp-column lp-column-order">{{answer.answer_order}}</td>
                    <td class="lp-column lp-column-answer_text">{{answer.text}}</td>
                    <td class="lp-column lp-column-answer_correct lp-answer-check">
                        <input type="checkbox" :checked="answersChecked(answer.is_true)" :value="answer.value">
                    </td>
                    <td class="lp-column lp-column-actions lp-toolbar-buttons">
                        <div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown">
                            <a class="lp-btn-icon dashicons dashicons-trash learn-press-tooltip"></a>
                        </div>
                        <span class="learn-press-tooltip lp-toolbar-btn lp-btn-move">
                        <a class="lp-btn-icon dashicons dashicons-sort"></a>
                    </span>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {
        Vue.component('lp-question-answers', {
            template: '#tmpl-lp-question-answers',
            props: ['answers'],
            computed: {},
            methods: {
                headingClass: function (heading) {
                    return 'lp-column-heading-' + heading;
                },
                answerClass: function (answer) {
                    return 'lp-list-option-' + answer;
                },
                answersChecked: function (answer) {
                    return (answer === 'yes') ? 'checked' : '';
                }
            }
        })
    })(Vue, LP_Quiz_Store);
</script>
