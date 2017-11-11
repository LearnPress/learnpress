<?php
/**
 * Question options template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-meta">
    <div class="quiz-question-options">
        <div>
            <div class="postbox">
                <h2 class="hndle"><span><?php _e( 'Settings', 'learnpress' ); ?></span></h2>
                <div class="inside">
                    <div class="rwmb-meta-box">
                        <div class="rwmb-field rwmb-textarea-wrapper">
                            <div class="rwmb-label">
                                <label for=""><?php _e( 'Question Content', 'learnpress' ); ?></label>
                            </div>
                            <div class="rwml-input">
                                <div>
                                   <textarea name="" id="" cols="60" rows="3" class="rwmb-textarea large-text"
                                             @change="updateContent"
                                             v-model="question.settings.content"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="rwmb-field rwmb-number-wrapper">
                            <div class="rwmb-label">
                                <label for=""><?php _e( 'Mark for this question', 'learnpress' ); ?></label>
                            </div>
                            <div class="rwml-input">
                                <div>
                                    <input name="mark" type="number" v-model="question.settings.mark"
                                           @change="updateMeta">
                                    <p class="description"><?php _e( 'Mark for choosing the right answer.', 'learnpress' ); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="rwmb-field rwmb-textarea-wrapper">
                            <div class="rwmb-label">
                                <label for=""><?php _e( 'Question Explanation', 'learnpress' ); ?></label>
                            </div>
                            <div class="rwml-input">
                                <div>
                                   <textarea name="explanation" id="" cols="60" rows="3"
                                             class="rwmb-textarea large-text"
                                             @change="updateMeta"
                                             v-model="question.settings.explanation"></textarea>
                                    <p class="description"><?php _e( 'Explain why an option is true and other is false. The text will be shown when user click on \'Check answer\' button.', 'learnpress' ); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="rwmb-field rwmb-textarea-wrapper">
                            <div class="rwmb-label">
                                <label for=""><?php _e( 'Question hint', 'learnpress' ); ?></label>
                            </div>
                            <div class="rwml-input">
                                <div>
                                   <textarea name="hint" id="" cols="60" rows="3" class="rwmb-textarea large-text"
                                             @change="updateMeta"
                                             v-model="question.settings.hint"></textarea>
                                    <p class="description"><?php _e( 'Instruction for user to select the right answer. The text will be shown when user clicking \'Hint\' button.', 'learnpress' ); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-quiz-question-meta', {
                template: '#tmpl-lp-quiz-question-meta',
                props: ['question'],
                methods: {
                    updateContent: function () {
                        var request = {
                            'action': 'update-content',
                            'question': this.question
                        };
                        $store.dispatch('lqs/updateQuestion', request);
                    },
                    updateMeta: function (e) {
                        var request = {
                            'action': 'update-meta',
                            'question': this.question,
                            'meta': e.target.name
                        };
                        $store.dispatch('lqs/updateQuestion', request);
                    }
                }
            }
        )

    })(Vue, LP_Quiz_Store);
</script>
