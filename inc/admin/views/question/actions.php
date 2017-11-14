<?php
/**
 * Admin question editor: question actions template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-actions">

    <div class="lp-box-data-head lp-row">
        <h3 class="heading"><?php esc_html_e( 'Question Answers', 'learnpress' ); ?></h3>
        <div class="lp-box-data-actions lp-toolbar-buttons">
            <div class="lp-toolbar-btn question-actions">
                <div class="question-types">
                    <a href="" class="lp-btn-icon dashicons dashicons-editor-help"></a>
                    <ul>
                        <li v-for="(type, key) in types" :data-type="key" :class="active(key)">
                            <a href="" @click.prevent="changeType(key)">{{type}}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</script>

<script type="text/javascript">

    (function (Vue, $store) {

        Vue.component('lp-question-actions', {
            template: '#tmpl-lp-question-actions',
            props: ['type'],
            computed: {
                // all question types
                types: function () {
                    return $store.getters['types']
                }
            },
            methods: {
                // check question type active
                active: function (type) {
                    return this.type === type ? 'active' : '';
                },
                // change question type
                changeType: function (type) {
                    if (this.type !== type) {
                        $store.dispatch('changeQuestionType', type);
                    }
                }
            }
        })

    })(Vue, LP_Question_Store);

</script>
