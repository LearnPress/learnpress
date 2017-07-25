<?php
/**
 * Section template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/section-item' );

?>
<script type="text/x-template" id="tmpl-lp-section">
    <div class="section" :class="isOpen ? 'open' : 'close'">
        <div class="section-head">
            <input type="text" v-model="section.title" class="name" title="name" placeholder="<?php echo esc_attr( 'Enter section name and hit enter', 'learnpress' ); ?>">

            <div class="actions">
                <span class="collapse" :class="isOpen ? 'open' : 'close'" @click="toggle"></span>
            </div>
        </div>

        <div class="section-content">
            <div class="description">
                <input v-model="section.description" type="text" class="description-input" title="description" placeholder="<?php echo esc_attr( 'Describe about this section', 'learnpress' ); ?>">
            </div>

            <table class="section-list-items">
                <tbody>
                <lp-section-item v-for="item in section.items" :item="item" :key="item.id"></lp-section-item>
                </tbody>
            </table>
        </div>
    </div>
</script>
