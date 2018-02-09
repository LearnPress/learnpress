<?php
/**
 * Section item template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-section-item">
    <li :class="['section-item',item.type, {updating: updating, removing: removing}]" :data-item-id="item.id"
        :data-item-order="order">
        <div class="drag">
            <svg class="svg-icon" viewBox="0 0 32 32">
                <path d="M 14 5.5 a 3 3 0 1 1 -3 -3 A 3 3 0 0 1 14 5.5 Z m 7 3 a 3 3 0 1 0 -3 -3 A 3 3 0 0 0 21 8.5 Z m -10 4 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 12.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 12.5 Z m -10 10 a 3 3 0 1 0 3 3 A 3 3 0 0 0 11 22.5 Z m 10 0 a 3 3 0 1 0 3 3 A 3 3 0 0 0 21 22.5 Z"></path>
            </svg>
        </div>
        <div class="icon"></div>
        <div class="title">
            <input v-model="item.title" type="text" @change="changeTitle" @blur="updateTitle"
                   @keyup.enter="updateTitle" @keyup="keyUp">
        </div>

        <div class="item-actions">
            <div class="actions">
                <div class="action preview-item">
                    <a class="lp-btn-icon dashicons" :class="previewClass" @click="togglePreview"></a>
                </div>
                <div class="action edit-item">
                    <a :href="url" target="_blank"
                       class="lp-btn-icon dashicons dashicons-edit"></a>
                </div>
                <div class="action delete-item" v-if="!disableCurriculum">
                    <a class="lp-btn-icon dashicons dashicons-trash" @click.prevent="remove"></a>
                    <ul>
                        <li>
                            <a @click.prevent="remove"><?php esc_html_e( 'Remove from course', 'learnpress' ); ?></a>
                        </li>
                        <li>
                            <a @click.prevent="deletePermanently"
                               class="delete-permanently"><?php esc_html_e( 'Delete permanently', 'learnpress' ); ?></a>
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
            props: ['item', 'order', 'disableCurriculum'],
            data: function () {
                return {
                    // origin course item title
                    title: this.item.title,
                    changed: false,
                    removing: false
                };
            },
            created: function () {
                this.$ = jQuery;
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
                },
                previewClass: function () {
                    return {
                        'dashicons-unlock': this.item.preview,
                        'dashicons-lock': !this.item.preview
                    }
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
                },
                // navigation course items
                keyUp: function (event) {
                    var keyCode = event.keyCode;
                    // escape update course item title
                    if (keyCode === 27) {
                        this.item.title = this.title;
                    } else {
                        this.$emit('nav', {key: event.keyCode, order: this.order});
                    }
                },
                togglePreview: function (evt) {
                    this.item.preview = !this.item.preview;
                    this.changed = true;
                    this.updateTitle();
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
