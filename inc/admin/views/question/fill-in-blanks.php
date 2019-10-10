<?php
/**
 * View for displaying fill-in-blanks question editor.
 *
 * @version 4.x.x
 * @author  ThimPress
 * @package LearnPress/Views
 */

defined( 'ABSPATH' ) or die;

/**
 * @var LP_Question $question
 */
if ( ! isset( $question ) ) {
	return;
}

if ( $question->get_type() !== 'fill_in_blanks' ) {
	return;
}

$settings = $question->get_editor_settings();

?>
<div id="fill-in-blanks-editor">
    <div class="lp-place-holder">
        <div class="line-heading"></div>

        <div class="line-sm"></div>
        <div class="line-xs"></div>

        <div class="line-df"></div>
        <div class="line-lgx"></div>
        <div class="line-lg"></div>

        <div class="line-df"></div>
        <div class="line-lg"></div>
        <div class="line-lgx"></div>
    </div>
</div>
<script>
    jQuery(function ($) {
        setTimeout(() => {
            LP.questionEditor.init('#fill-in-blanks-editor', <?php echo json_encode( $settings );?>);
        }, 1000)
    })
</script>
