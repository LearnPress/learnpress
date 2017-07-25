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
        <input type="hidden" :value="encode" name="_lp_curriculum_sections[]">

        <div class="section-head">
            <div class="section-title" data-empty="<?php esc_attr_e( 'Empty name section', 'learnpress' ); ?>">{{section.title}}</div>

            <div class="actions">
                <span class="collapse" :class="isOpen ? 'open' : 'close'" @click.prevent="toggle"></span>
            </div>
        </div>

        <div class="section-content">
            <div class="details">
                <input v-model="section.title" type="text" title="title" class="title-input" placeholder="<?php esc_attr_e( 'Enter the name section', 'learnpress' ); ?>">

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
