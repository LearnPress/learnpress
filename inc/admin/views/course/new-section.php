<?php

/**
 * Template new section.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-new-section">
    <div class="section new-section">
        <div class="section-head">
            <span class="creatable"></span>
            <input
                    v-model="title"
                    type="text"
                    title="title"
                    class="title-input"
                    @keyup.enter.prevent="addNewSection"
                    placeholder="<?php esc_attr_e( 'Add a new section', 'learnpress' ); ?>">
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-new-section', {
            template: '#tmpl-lp-new-section',
            data: function () {
                return {
                    title: ''
                };
            },
            methods: {
                addNewSection: function () {
                    $store.dispatch('addNewSection', {
                        title: this.title
                    });

                    this.title = '';
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
