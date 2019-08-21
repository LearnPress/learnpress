<?php
/**
 * Admin Quiz Editor: Question actions.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-actions">
    <div class="question-actions table-row" :class="status">
        <div class="sort lp-sortable-handle">
            <i class="fa fa-bars"></i>
        </div>
        <div class="order">{{index +1}}</div>
        <div class="name" @dblclick="toggle">
            <input type="text" class="question-title" v-model="question.title"
                   @change="changeTitle" @blur="updateTitle" @keyup.enter="updateTitle" @keyup="keyUp">
        </div>
        <div class="type">{{question.type.label}}</div>
        <div class="actions">
            <div class="lp-box-data-actions lp-toolbar-buttons">
                <div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type lp-title-attr-tip"
                     data-content-tip="<?php echo esc_attr( 'Change type', 'learnpress' ); ?>">
                    <a class="lp-btn-icon dashicons dashicons-randomize"></a>
                    <ul>
                        <li v-for="(type, key) in questionTypes" :class="active(key)">
                            <a href="" :data-type="key" @click.prevent="changeType(key)">{{type}}</a>
                        </li>
                    </ul>
                </div>
                <div class="lp-toolbar-btn lp-title-attr-tip" v-if="!disableUpdateList"
                     data-content-tip="<?php echo esc_attr( 'Duplicate', 'learnpress' ); ?>">
                    <a href="" class="lp-btn-icon dashicons dashicons-admin-page"
                       @click.prevent="clone"></a>
                </div>
                <div class="lp-toolbar-btn lp-title-attr-tip"
                     data-content-tip="<?php echo esc_attr( 'Edit item', 'learnpress' ); ?>">
                    <a :href="url" target="_blank" class="lp-btn-icon dashicons dashicons-edit"></a>
                </div>
                <div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown"
                     v-if="!disableUpdateList">
                    <a class="lp-btn-icon dashicons dashicons-trash" @click.prevent="remove"></a>
                    <ul>
                        <li>
                            <a @click.prevent="remove"
                               class="remove"><?php esc_html_e( 'Remove from quiz', 'learnpress' ); ?></a>
                        </li>
                        <li>
                            <a @click.prevent="deletePermanently"
                               class="delete"><?php esc_html_e( 'Move to trash', 'learnpress' ); ?></a>
                        </li>
                    </ul>
                </div>
                <span :class="['lp-toolbar-btn lp-btn-toggle', question.open ?'open' : 'close']" @click="toggle"></span>
            </div>
        </div>
    </div>
</script>

<script type="text/javascript">
    jQuery(function ($) {
        var $Vue = window.$Vue || Vue;
        var $store = window.LP_Quiz_Store;

        $Vue.component('lp-quiz-question-actions', {
            template: '#tmpl-lp-quiz-question-actions',
            props: ['question', 'index'],
            data: function () {
                return {
                    // origin question title
                    title: this.question.title,
                    changed: false
                };
            },
            mounted: function () {
                this.$nextTick(function () {
                    var $ = jQuery;
                    $(this.$el).find('.lp-title-attr-tip').LP('QuickTip',{
                        closeInterval: 0,
                        arrowOffset: 'el',
                        tipClass: 'preview-item-tip'
                    });
                    $(document).on('mousedown', '.section-item .drag', function (e) {
                        $('html, body').addClass('moving');
                    }).on('mouseup', function (e) {
                        $('html, body').removeClass('moving');
                    })
                })
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
                    if (!confirm($store.getters['i18n/all'].confirm_trash_question.replace('{{QUESTION_NAME}}', this.question.title))) {
                        return;
                    }
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

    })
</script>
