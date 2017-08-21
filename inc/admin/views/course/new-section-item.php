<?php

/**
 * Template new item section.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-new-section-item">
    <div class="empty-section-item section-item">
       <div class="choose-type"></div>
        <div class="title">
            <input type="text" placeholder="Type the title">
        </div>
    </div>
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
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
