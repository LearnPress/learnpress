<?php
/**
 * Template curriculum course.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/sections' );
?>

<script type="text/x-template" id="tmpl-lp-course-curriculum">

    <div class="lp-course-curriculum">
        <div class="heading">
            <h4><?php _e( 'Curriculum', 'learnpress' ); ?> <span :class="['status', status]"></span></h4>
            <div class="section-item-counts"><span>{{countAllItems()}}</span></div>
            <span class="collapse-sections" @click="toggle" :class="isOpen ? 'open' : 'close'"></span>
        </div>

        <lp-list-sections></lp-list-sections>
    </div>

</script>

<script type="text/javascript">
    jQuery(function ($) {

        (function (Vue, $store) {

            Vue.component('lp-curriculum', {
                template: '#tmpl-lp-course-curriculum',
                computed: {
                    status: function () {
                        return $store.getters.status;
                    },
                    isOpen: function () {
                        return !$store.getters['ss/isHiddenAllSections'];
                    }
                },
                methods: {
                    // toggle all sections
                    toggle: function () {
                        $store.dispatch('ss/toggleAllSections');
                    },

                    countAllItems: function () {
                        var count = 0,
                            labels = $store.getters['i18n/all'].item_labels;
                        $.each($store.getters['ss/sections'], function (i, section) {
                            count += section.items.length;
                        });

                        return count + ' ' + (count <= 1 ? labels.singular : labels.plural);
                    }
                }
            });

        })(Vue, LP_Curriculum_Store);

    })
</script>
