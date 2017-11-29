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
            <span class="collapse-sections" @click="toggle" :class="isOpen ? 'open' : 'close'"></span>
        </div>

        <lp-list-sections></lp-list-sections>
    </div>

</script>

<script type="text/javascript">

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
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
