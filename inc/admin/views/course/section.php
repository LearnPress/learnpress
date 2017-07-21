<?php
/**
 * Section template.
 *
 * @since 3.0.0
 */

learn_press_admin_view( 'course/section-item' );

?>
<script type="text/x-template" id="tmpl-lp-section">
    <div class="section">
        <div class="section-head">
            <input type="text" name="name" title="name" placeholder="<?php echo esc_attr( 'Enter section name and hit enter', 'learnpress' ); ?>">

            <div class="actions">

            </div>
        </div>

        <div class="section-content">
            <div class="description">
                <input type="text" name="description" title="description" placeholder="<?php echo esc_attr( 'Describe about this section', 'learnpress' ); ?>">
            </div>
        </div>
    </div>
</script>
