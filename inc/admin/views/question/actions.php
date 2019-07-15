<?php
/**
 * Admin question editor: question actions template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-question-actions">

    <div class="lp-box-data-head lp-row">
        <h3 class="heading">
            <?php esc_html_e( 'Question Answers', 'learnpress' ); ?>
            <div class="section-item-counts"><span>{{typeLabel()}}</span></div>
        </h3>
        <div class="lp-box-data-actions lp-toolbar-buttons">
            <div class="lp-toolbar-btn question-actions">
                <div class="question-types">
                    <a href="" class="lp-btn-icon dashicons dashicons-randomize"></a>
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

    jQuery(function ($) {
        var $store = window.LP_Question_Store;

        window.$Vue = window.$Vue || Vue;

        $Vue.component('lp-question-actions', {
            template: '#tmpl-lp-question-actions',
            props: ['type'],
            computed: {
                // all question types
                types: function () {
                    return $store.getters['types']
                }
            },
            methods: {
                typeLabel: function () {
                    var types = this.types;
                    return types[this.type];
                },
                // check question type active
                active: function (type) {
                    return this.type === type ? 'active' : '';
                },
                // change question type
                changeType: function (type) {
                    if (this.type !== type) {
                        this.$emit('changeType', type);
                    }
                }
            }
        })

    });

</script>
