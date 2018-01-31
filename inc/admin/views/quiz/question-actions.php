<?php
/**
 * Admin Quiz Editor: Question actions.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-actions">
    <div class="question-actions table-row" :class="status">
        <div class="sort">
            <svg class="svg-icon" viewBox="0 0 32 32">
                <path d="M 14 5.5 a 3 3 0 1 1 -3 -3 A 3 3 0 0 1 14 5.5 Z m 7 3 a 3 3 0 1 0 -3 -3 A 3 3 0 0 0 21 8.5 Z m -10 4 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 12.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 12.5 Z m -10 10 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 22.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 22.5 Z"></path>
            </svg>
        </div>
        <div class="order">{{index +1}}</div>
        <div class="name" @dblclick="toggle">
            <input type="text" class="question-title" v-model="question.title"
                   @change="changeTitle" @blur="updateTitle" @keyup.enter="updateTitle" @keyup="keyUp">
        </div>
        <div class="type">{{question.type.label}}</div>
        <div class="actions">
            <div class="lp-box-data-actions lp-toolbar-buttons">
                <div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type">
                    <a class="lp-btn-icon dashicons dashicons-randomize" title="Change type"></a>
                    <ul>
                        <li v-for="(type, key) in questionTypes" :class="active(key)">
                            <a href="" :data-type="key" @click.prevent="changeType(key)">{{type}}</a>
                        </li>
                    </ul>
                </div>
                <div class="lp-toolbar-btn">
                    <a :href="url" target="_blank" class="lp-btn-icon dashicons dashicons-edit" title="Edit"></a>
                </div>
                <div class="lp-toolbar-btn" v-if="!disableUpdateList">
                    <a href="" class="lp-btn-icon dashicons dashicons-admin-page" @click.prevent="clone"
                       title="Duplicate"></a>
                </div>
                <div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown" v-if="!disableUpdateList">
                    <a class="lp-btn-icon dashicons dashicons-trash" @click.prevent="remove" title="Delete"></a>
                    <ul>
                        <li>
                            <a @click.prevent="remove"
                               class="remove"><?php esc_html_e( 'Remove from quiz', 'learnpress' ); ?></a>
                        </li>
                        <li>
                            <a @click.prevent="deletePermanently"
                               class="delete"><?php esc_html_e( 'Delete permanently', 'learnpress' ); ?></a>
                        </li>
                    </ul>
                </div>
                <span :class="['lp-toolbar-btn lp-btn-toggle', question.open ?'open' : 'close']" @click="toggle"
                      title="Toggle"></span>
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
                    // origin question title
                    title: this.question.title,
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
                },
                // disable update list questions
                disableUpdateList: function () {
                    return $store.getters['lqs/disableUpdateList'];
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
                },
                // navigation questions
                keyUp: function (e) {
                    var keyCode = e.keyCode;
                    // escape update question title
                    if (keyCode === 27) {
                        this.question.title = this.title;
                    } else {
                        this.$emit('nav', {key: e.keyCode, order: this.index});
                    }
                }
            }
        });

    })(Vue, LP_Quiz_Store);
</script>
