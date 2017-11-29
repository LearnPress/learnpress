<?php
/**
 * Section item template.
 *
 * @since 3.0.0
 */
?>
<!--<div class="drag"><svg class="Icon DragIcon DragHandle-icon" viewBox="0 0 32 32"><path d="M 14 5.5 a 3 3 0 1 1 -3 -3 A 3 3 0 0 1 14 5.5 Z m 7 3 a 3 3 0 1 0 -3 -3 A 3 3 0 0 0 21 8.5 Z m -10 4 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 12.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 12.5 Z m -10 10 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 22.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 22.5 Z"></path></svg></div>-->

<script type="text/x-template" id="tmpl-lp-section-item">
    <li :class="['section-item',item.type, {updating: updating, removing: removing}]" :data-item-id="item.id">
        <div class="icon"></div>
        <div class="title">
            <input v-model="item.title" type="text" title="title no-submit"
                   @change="changeTitle" @blur="updateTitle" @keyup.enter="updateTitle">
        </div>

        <div class="item-actions">
            <div class="actions">
                <div class="action edit-item"><a :href="url" target="_blank" class="lp-btn-icon dashicons dashicons-edit"></a></div>
                <div class="action delete-item">
                    <a class="lp-btn-icon dashicons dashicons-trash" @click.prevent="remove"></a>
                    <ul>
                        <li>
                            <a @click.prevent="deletePermanently"><?php esc_html_e( 'Delete permanently', 'learnpress' ); ?></a>
                        </li>
                    </ul>
                </div>
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
                },
                // remove item
                deletePermanently: function () {
                    this.removing = true;
                    this.$emit('delete', this.item);
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
