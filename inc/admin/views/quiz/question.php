<?php
/**
 * Question template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/answers' );
learn_press_admin_view( 'quiz/settings' );
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-item">
    <tr class="question-item">
        <td class="lp-column-sort"><i class="fa fa-bars"></i></td>
        <td class="lp-column-order"></td>
        <td class="lp-column-name">{{question_title}}</td>
        <td class="lp-column-type">{{question_type}}</td>
        <td class="lp-column-actions">
            <div class="lp-box-data-actions lp-toolbar-buttons">
                <div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type">
                    <a data-tooltip="Change type of this question"
                       class="lp-btn-icon dashicons dashicons-editor-help"></a>
                    <ul>
                        <li data-type="true_or_false" class="active"
                            ng-class="{active: questionData.type=='true_or_false'}"><a href="">True Or False</a></li>
                        <li data-type="multi_choice" class="" ng-class="{active: questionData.type=='multi_choice'}"><a
                                    href="">Multi Choice</a></li>
                        <li data-type="single_choice" class="" ng-class="{active: questionData.type=='single_choice'}">
                            <a href="">Single Choice</a></li>
                    </ul>
                </div>
                <div class="lp-toolbar-btn">
                    <a target="_blank" data-tooltip="Edit question in new window"
                       href="post.php?post=16&amp;action=edit"
                       class="lp-btn-icon dashicons dashicons-admin-links learn-press-tooltip" original-title=""></a>
                </div>
                <div class="lp-toolbar-btn">
                    <a target="_blank" data-tooltip="Clone this question"
                       class="lp-btn-icon dashicons dashicons-admin-page learn-press-tooltip" original-title=""></a>
                </div>
                <div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown">
                    <a data-tooltip="Remove this question"
                       class="lp-btn-icon dashicons dashicons-trash learn-press-tooltip"
                       ng-click="removeQuestion($event)" original-title=""></a>
                    <ul>
                        <li><a class="learn-press-tooltip" data-tooltip="" data-delete-permanently="yes"
                               original-title=""> Delete permanently </a>
                        </li>
                    </ul>
                </div>
                <span class="lp-toolbar-btn lp-btn-toggle learn-press-tooltip" data-tooltip="Toggle question content"
                      original-title="">
                    <a class="lp-btn-icon dashicons dashicons-arrow-up-alt2"></a>
                    <a class="lp-btn-icon dashicons dashicons-arrow-down-alt2"></a>
                </span>
            </div>
        </td>
    </tr>
</script>

<script>

    (function (Vue, $store) {

        Vue.component('lp-quiz-question-item', {
            template: '#tmpl-lp-quiz-question-item',
            props: ['question'],
            computed: {
                item: function () {
                    return this.question.question_id;
                },
                question_title: function () {
                    return this.question.title;
                },
                question_type: function () {
                    return this.question.type;
                }
            }
        });

    })(Vue, LP_Quiz_Store);

</script>
