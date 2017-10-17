<?php
/**
 * Question actions template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-actions">
    <div class="question-actions table-row">
        <div class="lp-column-sort"><i class="fa fa-bars"></i></div>
        <div class="lp-column-order">{{index +1}}</div>
        <div class="lp-column-name">
            <input type="text" class="question-title"
                   v-model="question.title"
                   @keyup.enter='updateTitle'
                   @blur="updateTitle"
                   @input="onChangeTitle">
        </div>
        <div class="lp-column-type">{{question.type.label}}</div>
        <div class="lp-column-actions">
            <div class="lp-box-data-actions lp-toolbar-buttons">
                <div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type">
                    <a class="lp-btn-icon dashicons dashicons-editor-help"></a>
                    <ul>
                        <li v-for="(type, key) in questionTypes" :class="isAcitve(key) ? 'active' : ''">
                            <a href="" :data-type="key" @click.prevent="changeQuestionType">{{type}}</a>
                        </li>
                    </ul>
                </div>
                <div class="lp-toolbar-btn">
                    <a target="_blank" :href="urlEdit"
                       class="lp-btn-icon dashicons dashicons-admin-links "></a>
                </div>
                <div class="lp-toolbar-btn">
                    <a target="_blank" class="lp-btn-icon dashicons dashicons-admin-page" @click="clone"></a>
                </div>
                <div class="lp-toolbar-btn lp-btn-remove lp-toolbar-btn-dropdown">
                    <a class="lp-btn-icon dashicons dashicons-trash" @click="remove"></a>
                    <ul>
                        <li><a class=""
                               @click="deletePermanently"><?php esc_html_e( 'Delete permanently', 'learnpress' ); ?></a>
                        </li>
                    </ul>
                </div>
                <span @click="toggle" :class="question.open ?'open' : 'close'"
                      class="lp-toolbar-btn lp-btn-toggle "></span>
            </div>
        </div>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-question-actions', {
            template: '#tmpl-lp-question-actions',
            props: ['question', 'index'],
            data: function () {
                return {
                    unsaved: false,
                    removing: false
                };
            },
            computed: {
                urlEdit: function () {
                    return 'post.php?post=' + this.question.id + '&action=edit';
                },
                questionTypes: function () {
                    return $store.getters['questionTypes'];
                }
            },
            methods: {
                toggle: function () {
                    $store.dispatch('lqs/toggleQuestion', this.question);
                },
                clone: function () {
                    $store.dispatch('lqs/cloneQuestion', this.question);
                },
                remove: function () {
                    $store.dispatch('lqs/removeQuestion', this.question);
                },
                deletePermanently: function () {
                    $store.dispatch('lqs/deleteQuestion', this.question);
                },
                isAcitve: function (type) {
                    return this.question.type.key === type;
                },
                onChangeTitle: function () {
                    this.unsaved = true;
                },
                updateTitle: function () {
                    this.update();
                },
                update: function (e) {
                    this.unsaved = false;
                    var request = {
                        'action': 'update-title',
                        'question': this.question
                    };
                    $store.dispatch('lqs/updateQuestion', request);
                },
                changeQuestionType: function (e) {
                    var request = {
                        question: this.question,
                        newType: e.target.dataset.type
                    };

                    $store.dispatch('lqs/changeQuestionType', request);
                }
            }
        });

    })(Vue, LP_Quiz_Store);
</script>
