<?php
/**
 * Template list sections.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/section' );
?>
<script type="text/x-template" id="tmpl-lp-list-sections">
    <ul class="curriculum-sections">
        <lp-section v-for="section in sections" :section="section" :key="section.id"></lp-section>

        <li class="add-new-section">
            <button type="button" class="button button-primary" @click.prevent="clickNewSection"><?php esc_html_e( 'Add new section', 'learnpress' ); ?></button>
        </li>
    </ul>
</script>
