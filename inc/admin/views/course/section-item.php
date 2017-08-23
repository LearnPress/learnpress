<?php
/**
 * Section item template.
 *
 * @since 3.0.0
 */
?>
<script type="text/x-template" id="tmpl-lp-section-item">
    <li :data-item-id="item.id"
            :class="[item.type, {updating: updating, removing: removing}]"
            class="section-item">

        <div class="icon"></div>
        <div class="title">
            <input type="text" title="title"
                   @blur="updateTitle"
                   @keyup.enter="updateTitle"
                   @input="onChangeTitle"
                   v-model="item.title">
        </div>

        <div class="item-actions">
            <div class="actions">
                <a class="edit" :href="urlEdit" target="_blank">
                    <span class="dashicons dashicons-edit"></span>
                </a>
                <a class="remove" @click.prevent="remove">
                    <span class="dashicons dashicons-trash"></span>
                </a>
            </div>
        </div>

    </li>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-section-item', {
            template: '#tmpl-lp-section-item',
            props: ['item', 'order'],
            computed: {
                urlEdit: function () {
                    return $store.getters.urlEdit + this.item.id;
                },
                updating: function () {
                    return this.removing || this.saving;
                }
            },
            created: function() {
                var vm = this;

                $store.subscribe(function(mutation) {
                    if (mutation.type !== 'UPDATE_SECTION_ITEM' ) {
                        return;
                    }

                    vm.saving = false;
                });
            },
            data: function () {
                return {
                    unsaved: false,
                    removing: false,
                    saving: false
                };
            },
            methods: {
                onChangeTitle: function () {
                    this.unsaved = true;
                },
                remove: function () {
                    this.removing = true;
                    this.$emit('remove', this.item);
                },
                updateTitle: function () {
                    this.update();
                },
                update: function () {
                    if (!this.unsaved) {
                        return;
                    }

                    this.unsaved = false;
                    this.saving = true;
                    this.$emit('update', this.item);
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
