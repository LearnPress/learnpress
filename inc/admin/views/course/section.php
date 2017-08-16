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
            <span class="movable"></span>
            <input v-model="section.title"
                   type="text"
                   title="title"
                   class="title-input"
                   @blur="maybeUpdate"
                   @change="shouldBeStore"
                   @keyup.enter="update"
                   placeholder="<?php esc_attr_e( 'Enter the name section', 'learnpress' ); ?>">

            <div class="actions">
                <span class="collapse" :class="isOpen ? 'open' : 'close'" @click.prevent="toggle"></span>
            </div>
        </div>

        <div class="section-content">
            <div class="details">

                <input v-model="section.description"
                       type="text"
                       class="description-input"
                       title="description"
                       @blur="maybeUpdate"
                       @change="shouldBeStore"
                       @keyup.enter="update"
                       placeholder="<?php echo esc_attr( 'Describe about this section', 'learnpress' ); ?>">
            </div>

            <table class="section-list-items">
                <draggable :list="section.items" :element="'tbody'" :options="{handle: '.icon'}">
                    <lp-section-item v-for="(item, index) in section.items" :item="item" :key="item.id" :order="index+1"></lp-section-item>
                </draggable>
            </table>
        </div>

        <div class="section-actions">
            <button type="button" class="button button-secondary" @click="openChooseItems"><?php esc_html_e( 'Add items', 'learnpress' ); ?></button>

            <div class="remove" @click="remove">
                <span class="dashicons dashicons-trash"></span>
            </div>
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-section', {
            template: '#tmpl-lp-section',
            props: ['section', 'index'],
            data: function () {
                return {
                    isOpen: true,
                    unsaved: false
                };
            },
            methods: {
                toggle: function () {
                    this.isOpen = !this.isOpen;
                },
                remove: function () {
                    var r = window.confirm('Are you sure remove this section?');

                    if (!r) {
                        return;
                    }

                    $store.dispatch('removeSection', {
                        index: this.index,
                        section: this.section
                    });
                },
                shouldBeStore: function () {
                    this.unsaved = true;
                },
                maybeUpdate: function () {
                    if (this.unsaved) {
                        this.update();
                    }
                },
                update: function () {
                    this.unsaved = false;
                    $store.dispatch('updateSection', JSON.stringify(this.section));
                },
                openChooseItems: function () {
                    $store.dispatch('ci/open', parseInt(this.section.id));
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
