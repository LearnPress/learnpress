<?php
/**
 * Question options template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-quiz-question-meta">
    <div class="quiz-question-options">
        <div class="postbox" @click="openSettings($event)">
            <h2 class="hndle"><span><?php _e( 'Details', 'learnpress' ); ?></span>
            </h2>
            <a class="toggle" @click.prevent="openSettings($event)"></a>
            <div class="inside">
                <div class="rwmb-meta-box">
                    <div class="rwmb-field rwmb-textarea-wrapper">
                        <div class="rwmb-label">
                            <label :for="'content-'+question.id"><?php _e( 'Describe More', 'learnpress' ); ?></label>
                        </div>
                        <div class="rwml-input">
                            <div>
                                   <textarea name="" :id="'content-'+question.id" cols="60" rows="3" class="rwmb-textarea large-text"
                                             @change="updateContent"
                                             v-model="question.settings.content"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="rwmb-field rwmb-number-wrapper">
                        <div class="rwmb-label">
                            <label :for="'marking-'+question.id"><?php _e( 'Marking', 'learnpress' ); ?></label>
                        </div>
                        <div class="rwml-input">
                            <div>
                                <input name="mark" :id="'marking-'+question.id" type="number" min="1" v-model="question.settings.mark"
                                       @change="updateMeta">
                                <p class="description"><?php _e( 'Set question points.', 'learnpress' ); ?></p>
                            </div>
                        </div>
                    </div>
	                <div class="rwmb-field rwmb-textarea-wrapper">
		                <div class="rwmb-label">
			                <label :for="'hint-'+question.id"><?php _e( 'Hint', 'learnpress' ); ?></label>
		                </div>
		                <div class="rwml-input">
			                <div>
                                   <textarea name="hint" :id="'hint-'+question.id" cols="60" rows="3" class="rwmb-textarea large-text"
                                             @change="updateMeta"
                                             v-model="question.settings.hint"></textarea>
				                <p class="description"><?php _e( 'A little help for students to get the right answer.', 'learnpress' ); ?></p>
			                </div>
		                </div>
	                </div>
                    <div class="rwmb-field rwmb-textarea-wrapper">
                        <div class="rwmb-label">
                            <label :for="'explanation-'+question.id"><?php _e( 'Explanation', 'learnpress' ); ?></label>
                        </div>
                        <div class="rwml-input">
                            <div>
                                   <textarea name="explanation" :id="'explanation-'+question.id" cols="60" rows="3"
                                             class="rwmb-textarea large-text"
                                             @change="updateMeta"
                                             v-model="question.settings.explanation"></textarea>
                                <p class="description"><?php _e( 'Explanation will be showed after students Instant Check.', 'learnpress' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/javascript">
    jQuery(function ($) {
        var $Vue = window.$Vue || Vue;
        var $store = window.LP_Quiz_Store;

        $Vue.component('lp-quiz-question-meta', {
            template: '#tmpl-lp-quiz-question-meta',
            props: ['question'],
            methods: {
                // update question content
                updateContent: function () {
                    $store.dispatch('lqs/updateQuestionContent', this.question);
                },
                // update question meta
                updateMeta: function (e) {
                    $store.dispatch('lqs/updateQuestionMeta', {
                        question: this.question,
                        meta_key: e.target.name
                    });
                },
                openSettings: function (e) {
                    e.stopPropagation();
                    var $root = $(this.$el).closest('.question-settings'),
                        $postbox = $root.find('.postbox');
                    $postbox.removeClass('closed');
                    if (!$(e.target).hasClass('toggle')) {
                        return;
                    }

                    var isClosed = $root.toggleClass('closed').hasClass('closed');

                    $store.dispatch('lqs/updateQuizQuestionsHidden', {
                        hidden: $('.question-settings.closed').map(function () {
                            return $(this).closest('.question-item').data('item-id')
                        }).get()
                    })
                }
            }
        })

    });
</script>
