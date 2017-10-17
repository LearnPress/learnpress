<?php
/**
 * Quiz editor template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'quiz/list-questions' );
learn_press_admin_view( 'quiz/modal-choose-items' );
?>


<script type="text/x-template" id="tmpl-lp-quiz-editor">
    <div id="quiz-editor-v2" class="learn-press-box-data" :class="{'need-reload': !heartbeat}">
        <div class="lp-box-data-head heading">
            <h3><?php echo __( 'Questions', 'learnpress' ); ?></h3>
            <span class="collapse-list-questions dashicons " @click="toggle"
                  :class="isOpen ? 'dashicons-arrow-down' : 'dashicons-arrow-up'"></span>
        </div>
        <div class="lp-box-data-content">
            <div class="lp-list-questions">
                <div class="header">
                    <div class="table-row">
                        <div class="lp-column-sort"></div>
                        <div class="lp-column-order">#</div>
                        <div class="lp-column-name"><?php esc_html_e( 'Name', 'learnpress' ); ?></div>
                        <div class="lp-column-type"><?php esc_html_e( 'Type', 'learnpress' ); ?></div>
                        <div class="lp-column-actions"><?php esc_html_e( 'Actions', 'learnpress' ); ?></div>
                    </div>
                </div>

                <lp-list-quiz-questions></lp-list-quiz-questions>

                <div class="footer">
                    <div class="table-row">
                        <div class="lp-column-sort"><i class="fa fa-bars"></i></div>
                        <div class="lp-column-order"></div>
                        <div class="lp-column-name lp-column-quick-add">
                            <div class="new-question-title">
                                <input type="text" name="new-question-title" :value="newQuestion.title"
                                       v-model="newQuestion.title"
                                       ref="newQuestionTitle">
                            </div>
                            <div class="add-new-button" :class="addNewEnable">
                                <button type="button" class="button"
                                        @click.stop="addNewItem"
                                        @keyup.enter.stop="addNewItem"
                                        :class="addNewEnable"><?php esc_html_e( 'Add as New', 'learnpress' ); ?> </button>
                                <ul class="lp-dropdown-items" ref="newQuestionType">
                                    <li v-for="(type, key) in questionTypes">
                                        <a href="#" :data-type="key" @click.stop="addNewItem">{{type}}</a>
                                    </li>
                                </ul>
                            </div>
                            <button type="button" class="button"
                                    @click.stop="openChooseItems"><?php esc_html_e( 'Select', 'learnpress' ); ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <lp-quiz-choose-items></lp-quiz-choose-items>
        </div>

        <div class="notify-reload">
            <div class="inner"><?php esc_html_e( 'Something went wrong! Please reload to keep editing list quiz questions.', 'learnpress' ); ?></div>
        </div>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store, $) {

        Vue.component('lp-quiz-editor', {
            template: '#tmpl-lp-quiz-editor',
            data: function () {
                return {
                    newQuestion: {
                        'title': '',
                        'type': 'true_or_false'
                    }
                }
            },
            created: function () {
                setInterval(function () {
                    $store.dispatch('heartbeat');
                }, 60 * 1000);
            },
            computed: {
                heartbeat: function () {
                    return $store.getters['heartbeat'];
                },
                isOpen: function () {
                    return $store.getters['lqs/isHiddenListQuestions'];
                },
                quizId: function () {
                    return $store.getters['id'];
                },
                addNewEnable: function () {
                    return this.newQuestion.title ? 'visible' : 'disabled';
                },
                questionTypes: function () {
                    return $store.getters['questionTypes'];
                }
            },
            methods: {
                toggle: function () {
                    $store.dispatch('lqs/toggleListQuestions');
                },
                addNewItem: function (e) {
                    e.preventDefault();
                    this.newQuestion.type = e.target.dataset.type;

                    var request = {
                        'newQuestion': this.newQuestion,
                        'quizId': this.quizId
                    };

                    $store.dispatch('lqs/addNewQuestion', request);

                    this.newQuestion.title = '';
                    this.$refs.newQuestionTitle.focus();
                },
                openChooseItems: function () {
                    $store.dispatch('cqi/open', parseInt(this.quizId));
                }
            }
        })

    })(Vue, LP_Quiz_Store, jQuery);
</script>
