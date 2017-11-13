<?php
/**
 * Section item template.
 *
 * @since 3.0.0
 */
?>
<script type="text/x-template" id="tmpl-lp-section-item">
    <li :class="['section-item',item.type, {updating: updating, removing: removing}]" :data-item-id="item.id">

        <div class="icon"></div>
        <div class="title">
            <input v-model="item.title" type="text" title="title no-submit"
                   @change="changeTitle" @blur="updateTitle" @keyup.enter="updateTitle">
        </div>

        <div class="item-actions">
            <div class="actions">
                <a class="edit" :href="url" target="_blank">
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
                    changed: false,
                    removing: false
                };
            },
            computed: {
                // edit item url
                url: function () {
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
                changeTitle: function () {
                    this.changed = true;
                },
                // update item title
                updateTitle: function () {
                    if (this.changed) {
                        this.$emit('update', this.item);
                        this.changed = false;
                    }
                },
                // remove item
                remove: function () {
                    this.removing = true;
                    this.$emit('remove', this.item);
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
