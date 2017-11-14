<?php
/**
 * Admin Quiz Editor: Question actions.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-actions">
    <div class="question-actions table-row" :class="status">
        <div class="lp-column-sort"><i class="fa fa-bars"></i></div>
        <div class="lp-column-order">{{index +1}}</div>
        <div class="lp-column-name" @dblclick="toggle">
            <input type="text" class="question-title" v-model="question.title"
                   @change="changeTitle" @blur="updateTitle" @keyup.enter="updateTitle">
        </div>
        <div class="lp-column-type">{{question.type.label}}</div>
        <div class="lp-column-actions">
            <div class="lp-box-data-actions lp-toolbar-buttons">
                <div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type">
                    <a class="lp-btn-icon dashicons dashicons-editor-help"></a>
                    <ul>
                        <li v-for="(type, key) in questionTypes" :class="active(key)">
                            <a href="" :data-type="key" @click.prevent="changeType(key)">{{type}}</a>
                        </li>
                    </ul>
                </div>
                <div class="lp-toolbar-btn">
                    <a :href="url" target="_blank" class="lp-btn-icon dashicons dashicons-admin-links "></a>
                </div>
                <div class="lp-toolbar-btn">
                    <a href="" class="lp-btn-icon dashicons dashicons-admin-page" @click.prevent="clone"></a>
                </div>
                <div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown">
                    <a class="lp-btn-icon dashicons dashicons-trash" @click.prevent="remove"></a>
                    <ul>
                        <li>
                            <a href="" @click.prevent="deletePermanently">
								<?php esc_html_e( 'Delete permanently', 'learnpress' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <span :class="['lp-toolbar-btn lp-btn-toggle', question.open ?'open' : 'close']" @click="toggle"></span>
            </div>
        </div>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-quiz-question-actions', {
            template: '#tmpl-lp-quiz-question-actions',
            props: ['question', 'index'],
            data: function () {
                return {
                    changed: false
                };
            },
            computed: {
                // question status
                status: function () {
                    return $store.getters['lqs/statusUpdateQuestionItem'][this.question.id] || '';
                },
                // url edit question
                url: function () {
                    return 'post.php?post=' + this.question.id + '&action=edit';
                },
                // list question types
                questionTypes: function () {
                    return $store.getters['questionTypes'];
                }
            },
            methods: {
                // check question type active
                active: function (type) {
                    return this.question.type.key === type ? 'active' : '';
                },
                // onchange question title
                changeTitle: function () {
                    this.changed = true;
                },
                // update question title
                updateTitle: function () {
                    if (this.changed) {
                        $store.dispatch('lqs/updateQuestionTitle', this.question);
                    }
                    this.changed = false;
                },
                // change question type
                changeType: function (type) {
                    if (this.question.type !== type) {
                        $store.dispatch('lqs/changeQuestionType', {
                            question_id: this.question.id,
                            type: type
                        });
                    }
                },
                // clone question
                clone: function () {
                    $store.dispatch('lqs/cloneQuestion', this.question);
                },
                // remove question from quiz
                remove: function () {
                    $store.dispatch('lqs/removeQuestion', this.question);
                },
                // delete permanently question
                deletePermanently: function () {
                    $store.dispatch('lqs/deleteQuestion', this.question);
                },
                // toggle question
                toggle: function () {
                    $store.dispatch('lqs/toggleQuestion', this.question);
                }
            }
        });

    })(Vue, LP_Quiz_Store);
</script>
