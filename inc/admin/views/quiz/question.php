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
        <td class="lp-column-sort movable"><i class="fa fa-bars"></i></td>
        <td class="lp-column-order">{{index + 1}}</td>
        <td class="lp-column-name">
            <input type="text" class="question-title lp-question-heading-title"
                   v-model="question.title"
                   @keyup.enter='updateTitle'
                   @blur="updateTitle"
                   @input="onChangeTitle">
        </td>
        <td class="lp-column-type">{{question.type.label}}</td>
        <td class="lp-column-actions">
            <div class="lp-box-data-actions lp-toolbar-buttons">
                <div class="lp-toolbar-btn lp-toolbar-btn-dropdown lp-btn-change-type">
                    <a class="lp-btn-icon dashicons dashicons-editor-help"></a>
                    <ul>
                        <li v-for="(type, key) in questionTypes" :data-type="key"
                            :class="isAcitve(key) ? 'active' : ''">
                            <a href="">{{type}}</a>
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
                        <li><a class="" @click="deletePermanently"> Delete permanently </a></li>
                    </ul>
                </div>
                <span @click="toggle" :class="question.open ?'open' : 'close'"
                      class="lp-toolbar-btn lp-btn-toggle "></span>
            </div>
        </td>
    </tr>
</script>

<script>

    (function (Vue, $store) {

        Vue.component('lp-quiz-question-item', {
            template: '#tmpl-lp-quiz-question-item',
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
                update: function () {
                    this.unsaved = false;
                    $store.dispatch('lqs/updateQuestion', this.question);
                }
            }
        });

    })(Vue, LP_Quiz_Store);

</script>
