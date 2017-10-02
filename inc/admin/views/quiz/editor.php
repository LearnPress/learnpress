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
            <span class="collapse-list-questions" @click="toggle" :class="isOpen ? 'open' : 'close'"></span>
        </div>
        <div class="lp-box-data-content">
            <table class="lp-list-questions">
                <thead>
                <tr>
                    <th class="lp-column-sort"></th>
                    <th class="lp-column-order">#</th>
                    <th class="lp-column-name">Name</th>
                    <th class="lp-column-type">Type</th>
                    <th class="lp-column-actions">Actions</th>
                </tr>
                </thead>
                <lp-list-quiz-questions></lp-list-quiz-questions>

                <tfoot>
                <tr>
                    <th class="lp-column-sort"><i class="fa fa-bars"></i></th>
                    <th class="lp-column-order"></th>
                    <th class="lp-column-name lp-column-quick-add" colspan="3">
                        <div class="modal-search">
                            <div class="modal-search-questions">
                                <input type="text" class="search-input">
                            </div>
                        </div>
                        <button type="button" class="button"
                                @click.stop="addNewItem"><?php esc_html_e( 'Add as New', 'learnpress' ); ?> </button>
                        <button type="button" class="button"
                                @click.stop="openChooseItems"><?php esc_html_e( 'Select', 'learnpress' ); ?></button>
                    </th>
                </tr>
                </tfoot>
            </table>
        </div>

        <div class="notify-reload">
            <div class="inner"><?php esc_html_e( 'Something went wrong! Please reload to keep editing list quiz questions.', 'learnpress' ); ?></div>
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-quiz-editor', {
            template: '#tmpl-lp-quiz-editor',
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
                }
            },
            methods: {
                toggle: function () {
                    $store.dispatch('lqs/toggleListQuestions');
                },
                addNewItem: function () {

                },
                openChooseItems: function () {
                    $store.dispatch('cqi/open', parseInt(this.quizId));
                }
            }
        })

    })(Vue, LP_Quiz_Store);
</script>
