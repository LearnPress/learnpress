<?php
/**
 * Section item template.
 *
 * @since 3.0.0
 */
?>

<script type="text/x-template" id="tmpl-lp-section-item">
    <li :class="['section-item',item.type, isEmptyItem() ? 'empty-item' : '', {updating: updating, removing: removing}]"
        :data-item-id="item.id"
        :data-item-order="order">
        <div class="drag lp-sortable-handle">
            <?php learn_press_admin_view('svg-icon');?>
        </div>
        <div class="icon"></div>
        <div class="title">
            <input v-model="item.title" type="text" @change="changeTitle" @blur="updateTitle"
                   @keyup.enter="updateTitle" @keyup="keyUp">
        </div>

        <div class="item-actions">
            <div class="actions">
                <div class="action preview-item lp-title-attr-tip"
                     data-content-tip="<?php echo esc_attr( 'Turn on/off this item is preview', 'learnpress' ); ?>">
                    <a class="lp-btn-icon dashicons" :class="previewClass" @click="togglePreview"></a>
                </div>
                <div class="action edit-item lp-title-attr-tip"
                     data-content-tip="<?php echo esc_attr( 'Edit item', 'learnpress' ); ?>">
                    <a :href="url" target="_blank" class="lp-btn-icon dashicons dashicons-edit"></a>
                </div>
                <div class="action delete-item lp-title-attr-tip" v-if="!disableCurriculum"
                     data-content-tip="<?php echo esc_attr( 'Delete', 'learnpress' ); ?>">
                    <a class="lp-btn-icon dashicons dashicons-trash" @click.prevent="remove"></a>
                    <ul>
                        <li>
                            <a @click.prevent="remove"><?php esc_html_e( 'Remove from course', 'learnpress' ); ?></a>
                        </li>
                        <li>
                            <a @click.prevent="deletePermanently"
                               class="delete-permanently"><?php esc_html_e( 'Move to trash', 'learnpress' ); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </li>
</script>

<script type="text/javascript">
    jQuery(function ($) {

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
                mounted: function () {
                    this.$nextTick(function () {
                        var $ = jQuery;
                        $(this.$el).find('.lp-title-attr-tip').QuickTip({
                            closeInterval: 0,
                            arrowOffset: 'el',
                            tipClass: 'preview-item-tip'
                        });
                    })
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
                            'dashicons-visibility': this.item.preview,
                            'dashicons-hidden': !this.item.preview
                        }
                    }
                },
                methods: {
                    isEmptyItem: function () {
                        return isNaN(this.item.id)
                    },
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
                        //this.removing = true;
                        this.item.temp_id = LP.uniqueId();
                        this.$emit('remove', this.item);
                    },
                    // remove item
                    deletePermanently: function () {
                        if (!confirm($store.getters['i18n/all'].confirm_trash_item.replace('{{ITEM_NAME}}', this.item.title))) {
                            return;
                        }
                        this.item.temp_id = LP.uniqueId();
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
    })
</script>
