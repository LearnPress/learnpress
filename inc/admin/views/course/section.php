<?php
/**
 * Section template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/section-item' );
learn_press_admin_view( 'course/new-section-item' );
?>

<script type="text/x-template" id="tmpl-lp-section">
    <div class="section" :class="[isOpen ? 'open' : 'close', status]">
        <div class="section-head" @dblclick="toggle" :input="input">
            <span class="movable"></span>
            <!--Section title-->
            <input v-model="section.title" type="text" title="title" class="title-input"
                   @change="updating" @blur="completed" @keyup.enter="completed"
                   placeholder="<?php esc_attr_e( 'Enter the name section', 'learnpress' ); ?>">
            <!--Section toggle-->
            <div class="actions">
                <span class="collapse" :class="isOpen ? 'open' : 'close'" @click.prevent="toggle"></span>
            </div>
        </div>

        <div class="section-collapse" ref="collapse">
            <div class="section-content">
                <div class="details">
                    <!--Section description-->
                    <input v-model="section.description" type="text" class="description-input no-submit"
                           title="description"
                           @change="updating" @blur="completed" @keyup.enter="completed" ref="description"
                           placeholder="<?php echo esc_attr( 'Describe about this section', 'learnpress' ); ?>">
                </div>

                <div class="section-list-items" :class="{'no-item': !section.items.length}">
                    <draggable v-model="items" :element="'ul'" :options="optionDraggable">
                        <!--Section items-->
                        <lp-section-item v-for="(item, index) in section.items" :item="item" :key="item.id"
                                         @update="updateItem" @remove="removeItem" :order="index+1"></lp-section-item>
                    </draggable>

                    <lp-new-section-item @create="newSectionItem"></lp-new-section-item>
                </div>
            </div>

            <div class="section-actions">
                <button type="button" class="button button-secondary"
                        @click="openChooseItems"><?php esc_html_e( 'Add items', 'learnpress' ); ?></button>

                <div class="remove" :class="{confirm: confirmRemove}">
                    <span class="icon" @click="removeSection"><span class="dashicons dashicons-trash"></span></span>
                    <div class="confirm" @click="remove"><?php esc_html_e( 'Are you sure?', 'learnpress' ); ?></div>
                </div>
            </div>
        </div>
    </div>
</script>

<script type="text/javascript">
    (function (Vue, $store, $) {

        Vue.component('lp-section', {
            template: '#tmpl-lp-section',
            props: ['section', 'index'],
            data: function () {
                return {
                    unsaved: false,
                    confirmRemove: false
                };
            },
            mounted: function () {
                var vm = this;

                this.prepareToggle();

                this.$watch('section.open', function (open) {
                    vm.toggleAnimation(open);
                });
            },
            computed: {
                status: function () {
                    return $store.getters['ss/statusUpdateSection'][this.section.id] || '';
                },
                isOpen: function () {
                    return this.section.open;
                },

                items: {
                    get: function () {
                        return this.section.items;
                    },
                    set: function (items) {
                        this.section.items = items;

                        $store.dispatch('ss/updateSectionItems', {
                            sectionId: this.section.id,
                            items: items
                        });
                    }
                },

                input: function () {
//                    $(this.$refs.collapse).find('input.no-submit');
//                    console.log($(this.$refs.collapse));
                },

                optionDraggable: function () {
                    return {
                        handle: '.icon',
                        draggable: '.section-item',
                        group: {
                            name: 'lp-section-items',
                            put: true,
                            pull: true
                        }
                    };
                }
            },
            methods: {
                toggle: function () {
                    $store.dispatch('ss/toggleSection', this.section);
                },
                prepareToggle: function () {
                    var display = 'none';
                    if (this.isOpen) {
                        display = 'block';
                    }

                    this.$refs.collapse.style.display = display;
                },
                toggleAnimation: function (open) {

                    console.log($(this.$refs.collapse));

                    if (open) {
                        $(this.$refs.collapse).slideDown();
                    } else {
                        $(this.$refs.collapse).slideUp();
                    }
                },
                newSectionItem: function (item) {
                    $store.dispatch('ss/newSectionItem', {
                        sectionId: this.section.id,
                        item: item
                    });
                },
                removeItem: function (item) {
                    $store.dispatch('ss/removeSectionItem', {
                        sectionId: this.section.id,
                        itemId: item.id
                    });
                },
                updateItem: function (item) {
                    $store.dispatch('ss/updateSectionItem', {
                        sectionId: this.section.id,
                        item: item
                    });
                },
                removeSection: function () {
                    this.confirmRemove = true;
                    var vm = this;

                    setTimeout(function () {
                        vm.confirmRemove = false;
                    }, 3000);
                },
                remove: function () {
                    if (!this.confirmRemove) {
                        return;
                    }

                    this.confirmRemove = false;
                    $store.dispatch('ss/removeSection', {
                        index: this.index,
                        section: this.section
                    });
                },
                updating: function () {
                    this.unsaved = true;
                },
                completed: function () {
                    if (this.unsaved) {
                        $store.dispatch('ss/updateSection', this.section);
                        this.unsaved = false;
                    }
                },
                openChooseItems: function () {
                    $store.dispatch('ci/open', parseInt(this.section.id));
                }
            }
        });

    })(Vue, LP_Curriculum_Store, jQuery);
</script>
