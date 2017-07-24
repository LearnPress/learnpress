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
    </ul>
</script>
