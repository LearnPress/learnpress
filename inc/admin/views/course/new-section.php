<?php

/**
 * Template new section.
 *
 * @since 3.0.0
 */

?>

<script type="text/x-template" id="tmpl-lp-new-section">
	<div class="section new-section">
        <div class="section-head">
            <span class="movable"></span>
            <input
                   type="text"
                   title="title"
                   class="title-input"
                   placeholder="<?php esc_attr_e( 'Add a new section', 'learnpress' ); ?>">
        </div>
    </div>
</script>

<script>
    (function (Vue, $store) {

        Vue.component('lp-new-section', {
            template: '#tmpl-lp-new-section',
            data: function () {
                return {}
            }
        });

    })(Vue, LP_Curriculum_Store);
</script>
