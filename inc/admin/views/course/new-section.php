<?php

/**
 * Template new section.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-new-section">
    <div class="section new-section">
        <form @submit.prevent="">
            <div class="section-head">
                <span class="creatable"></span>
                <input
                        v-model="title"
                        type="text"
                        title="title"
                        class="title-input"
                        @keyup.enter.prevent="addNewSection"
                        placeholder="<?php esc_attr_e( 'Create a new section', 'learnpress' ); ?>">
            </div>
        </form>
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
                    $store.dispatch('ss/addNewSection', {
                        title: this.title
                    });

                    this.title = '';
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
