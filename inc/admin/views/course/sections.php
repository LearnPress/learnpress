<?php
/**
 * Template list sections.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/section' );
learn_press_admin_view( 'course/new-section' );
?>

<script type="text/x-template" id="tmpl-lp-list-sections">

    <div class="curriculum-sections">
<!--        <draggable :list="sections" :options="{handle: '.movable'}" @end="sort">-->
            <lp-section v-for="(section, index) in sections"
                        :section="section" :index="index" :disableCurriculum="disableCurriculum"
                        :key="index"></lp-section>
<!--        </draggable>-->

        <div class="add-new-section" v-if="!disableCurriculum">
            <lp-new-section></lp-new-section>
        </div>
    </div>

</script>

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-list-sections', {
            template: '#tmpl-lp-list-sections',
            computed: {
                // all sections
                sections: function () {
                    return $store.getters['ss/sections'];
                },
                // disable edit curriculum
                disableCurriculum: function () {
                    return $store.getters['disable_curriculum'];
                }
            },
            created: function(){
                var _self = this;
                setTimeout(function () {
                    var $el = jQuery('.curriculum-sections');
                    $el.sortable({
                        update: function () {
                            _self.sort();
                        }
                    });
                }, 1000)

            },
            methods: {
                // sort sections
                sort: function () {

                    var order = [];
                    this.sections.forEach(function (section, index) {
                        order.push(parseInt(section.id));
                    });

                    $store.dispatch('ss/updateSectionsOrder', order);
                }
            }
        });
    })(Vue, LP_Curriculum_Store);
</script>
