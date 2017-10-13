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

<script type="text/javascript">
    (function (Vue, $store) {

        Vue.component('lp-section-item', {
            template: '#tmpl-lp-section-item',
            props: ['item', 'order'],
            data: function () {
                return {
                    unsaved: false,
                    removing: false
                };
            },
            computed: {
                urlEdit: function () {
                    return $store.getters['ss/urlEdit'] + this.item.id;
                },
                updating: function () {
                    return this.removing || this.saving;
                },
                status: function () {
                    return $store.getters['ss/statusUpdateSectionItem'][this.item.id] || '';
                },
                saving: function () {
                    return this.status === 'updating';
                }
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
                    this.$emit('update', this.item);
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
