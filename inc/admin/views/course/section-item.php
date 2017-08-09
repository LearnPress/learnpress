<?php
/**
 * Section item template.
 *
 * @since 3.0.0
 */
?>
<script type="text/x-template" id="tmpl-lp-section-item">
    <tr class="section-item" :data-item-id="item.id" :class="item.type">
        <td class="icon"></td>
        <td>
            <input type="text" title="title" v-model="item.title">
        </td>

        <td class="item-actions">
            <div class="actions">
                <a class="edit" :href="urlEdit" target="_blank">
                    <span class="dashicons dashicons-edit"></span>
                </a>
                <a class="remove">
                    <span class="dashicons dashicons-trash"></span>
                </a>
            </div>
        </td>
    </tr>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-section-item', {
            template: '#tmpl-lp-section-item',
            props: ['item', 'order'],
            computed: {
                urlEdit: function () {
                    return $store.getters.urlEdit + this.item.id;
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
