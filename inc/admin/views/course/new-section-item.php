<?php

/**
 * Template new item section.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-new-section-item">
    <tr class="empty-section-item" :class="className">
        <td></td>
        <td>
            <input type="text" placeholder="Type the title">
        </td>
        <td></td>
    </tr>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-new-section-item', {
            template: '#tmpl-lp-new-section-item',
            props: ['empty'],
            data: function () {
                return {};
            },
            computed: {
                className: function () {
                    if (this.empty) {
                        return 'section-item';
                    }

                    return 'section-item-ghost';
                }
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
