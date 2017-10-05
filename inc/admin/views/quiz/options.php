<?php
/**
 * Question options template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-options">
    <div class="quiz-question-options">
        <div>
            <div class="postbox">
                <h2 class="hndle"><span><?php esc_html_e( 'Settings', 'learnpress' ); ?></span></h2>
                <div class="inside">
                    <div>
                        <div class="rwmb-field rwmb-textarea-wrapper">
                            <div class="rwmb-label">
                                <label for=""><?php esc_html_e( 'Question Content', 'learnpress' ); ?></label>
                            </div>
                            <div class="rwml-input">
                                <div>
                                   <textarea name="" id="" cols="60" rows="3" class="rwmb-textarea large-text"
                                             @change="updateContent"
                                             v-model="question.settings.content.value"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="rwmb-field rwmb-textarea-wrapper">
                            <div class="rwmb-label">
                                <label for=""><?php esc_html_e( 'Mark for this question', 'learnpress' ); ?></label>
                            </div>
                            <div class="rwml-input">
                                <div>
                                    <input name="mark" type="number" v-model="question.settings.mark.value"
                                           @change="updateMeta">
                                    <p class="description">{{question.settings.mark.desc}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rwmb-field rwmb-textarea-wrapper">
                            <div class="rwmb-label">
                                <label for=""><?php esc_html_e( 'Question Explanation', 'learnpress' ); ?></label>
                            </div>
                            <div class="rwml-input">
                                <div>
                                   <textarea name="explanation" id="" cols="60" rows="3"
                                             class="rwmb-textarea large-text"
                                             @change="updateMeta"
                                             v-model="question.settings.explanation.value"></textarea>
                                    <p class="description">{{question.settings.explanation.desc}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="rwmb-field rwmb-textarea-wrapper">
                            <div class="rwmb-label">
                                <label for=""><?php esc_html_e( 'Question Hint', 'learnpress' ); ?></label>
                            </div>
                            <div class="rwml-input">
                                <div>
                                   <textarea name="hint" id="" cols="60" rows="3" class="rwmb-textarea large-text"
                                             @change="updateMeta"
                                             v-model="question.settings.hint.value"></textarea>
                                    <p class="description">{{question.settings.hint.desc}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-question-options', {
                template: '#tmpl-lp-question-options',
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
