<?php
/**
 * Section template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/section-item' );

?>
<script type="text/x-template" id="tmpl-lp-section">
    <div class="section" :class="isOpen ? 'open' : 'close'">
        <div class="section-head">
            <input v-model="section.title" type="text" title="title" class="title-input" placeholder="<?php esc_attr_e( 'Enter the name section', 'learnpress' ); ?>">

            <div class="actions">
                <span class="remove" @click="remove"><span class="dashicons dashicons-trash"></span></span>
                <span class="collapse" :class="isOpen ? 'open' : 'close'" @click.prevent="toggle"></span>
            </div>
        </div>

        <div class="section-content">
            <div class="details">

                <input v-model="section.description" type="text" class="description-input" title="description" placeholder="<?php echo esc_attr( 'Describe about this section', 'learnpress' ); ?>">
            </div>

            <table class="section-list-items">
                <draggable :list="section.items" :element="'tbody'">
                    <lp-section-item v-for="(item, index) in section.items" :item="item" :key="item.id" :order="index+1"></lp-section-item>
                </draggable>
            </table>
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-section', {
            template: '#tmpl-lp-section',
            props: ['section', 'order', 'index'],
            data: function () {
                return {
                    isOpen: true
                };
            },
            methods: {
                toggle: function () {
                    this.isOpen = !this.isOpen;
                },
                remove: function () {
                    $store.dispatch('removeSection', {
                        index: this.index,
                        section: this.section
                    });
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
