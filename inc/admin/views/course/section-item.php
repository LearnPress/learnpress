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
        </td>
    </tr>
</script>
