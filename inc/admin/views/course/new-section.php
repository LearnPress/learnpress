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
                <input v-model="section_title" type="text" title="title" class="title-input"
                       placeholder="<?php esc_attr_e( 'Write section name and press Enter', 'learnpress' ); ?>"
                       @keyup.enter.prevent="newSection">
            </div>
        </form>
    </div>
</script>

<script type="text/javascript">
    window.$Vue = window.$Vue || Vue;

    jQuery(function ($) {

        (function ($store) {

            $Vue.component('lp-new-section', {
                template: '#tmpl-lp-new-section',
                data: function () {
                    return {
                        section_title: ''
                    };
                },
                methods: {
                    // draft new course
                    draftCourse: function () {
                        if ($store.getters['autoDraft']) {
                            $store.dispatch('draftCourse', {
                                title: $('input[name=post_title]').val(),
                                content: $('textarea[name=content]').val()
                            });
                        }
                    },
                    newSection: function () {
                        // prevent create no title section
                        if (this.section_title) {

                            // create draft course if auto draft
                            this.draftCourse();

                            // new section
                            $store.dispatch('ss/newSection', this.section_title);
                            this.section_title = '';
                        }
                    }
                }
            });

        })(LP_Curriculum_Store);
    });
</script>
