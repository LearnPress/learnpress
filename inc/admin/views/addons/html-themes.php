<?php
$templates = LP_Outdated_Template_Helper::get_theme_templates();
$theme     = wp_get_theme();
usort( $templates, '_learn_press_sort_templates' );

$template_dir       = get_template_directory();
$stylesheet_dir     = get_stylesheet_directory();
$child_theme_folder = '';
$theme_folder       = '';
if ( $template_dir != $stylesheet_dir ) {
$child_theme_folder = basename( $stylesheet_dir );
$theme_folder       = basename( $template_dir );
}
?>
<table class="lp-template-overrides widefat" cellspacing="0">
    <thead>
    <tr>
        <th colspan="3">
            <h4><?php printf( __( 'Override Templates (%s)', 'learnpress' ), esc_html( $theme['Name'] ) ); ?></h4>
        </th>
    </tr>
    </thead>
    <tbody id="learn-press-template-files">
	<?php if ( $templates ): ?>
        <tr>
            <th>
				<?php _e( 'File', 'learnpress' ); ?>
                <p>
                    <a href="" class="learn-press-filter-template current" data-template=""><?php _e( 'All', 'learnpress' ); ?></a>
					<?php if ( $theme_folder && $child_theme_folder ) { ?>
                        <a href="" class="learn-press-filter-template" data-template="<?php echo esc_attr( $theme_folder ); ?>"><?php echo learn_press_get_theme_name( $theme_folder ); ?></a>
                        <a href="" class="learn-press-filter-template" data-template="<?php echo esc_attr( $child_theme_folder ); ?>"><?php echo learn_press_get_theme_name( $child_theme_folder ); ?></a>
					<?php } ?>
                    <a href="" class="learn-press-filter-template" data-outdated="yes"><?php _e( 'Outdated', 'learnpress' ); ?></a>
                </p>
            </th>
            <th>
				<?php _e( 'Version', 'learnpress' ); ?>
            </th>
            <th><?php _e( 'Core version', 'learnpress' ); ?></th>
        </tr>
		<?php foreach ( $templates as $template ): ?>
			<?php
			$template_folder = '';
			if ( $child_theme_folder && strpos( $template[0], $child_theme_folder ) !== false ) {
				$template_folder = $child_theme_folder;
			} else {
				$template_folder = $theme_folder;
			}
			?>

            <tr data-template="<?php echo esc_attr( $template_folder ); ?>" <?php if ( $template[3] ) {
				echo 'data-outdated="yes"';
			} ?> class="template-row">
                <td class="lp-template-file"><code><?php echo $template[0]; ?></code></td>
                <td class="lp-template-version<?php echo $template[3] ? ' outdated' : ( $template[1] == '-' && $template[2] == '-' ? '' : ' up-to-date' ); ?>">
                    <span><?php echo $template[1]; ?></span>
                </td>
                <td class="lp-core-version"><span><?php echo $template[2]; ?></span></td>
            </tr>
		<?php endforeach; ?>
	<?php endif; ?>
    <tr id="learn-press-no-templates" class="<?php echo $templates ? 'hide-if-js' : ''; ?>">
        <td colspan="3">
            <p><?php _e( 'There is no template file has overwritten', 'learnpress' ); ?></p>
        </td>
    </tr>
    </tbody>
</table>
<script type="text/javascript">
    jQuery(function ($) {
        $(document).on('click', '.learn-press-filter-template', function () {
            var $link = $(this),
                template = $link.data('template'),
                outdated = $link.data('outdated');
            if ($link.hasClass('current')) {
                return;
            }
            $link.addClass('current').siblings('a').removeClass('current');
            if (!template) {
                if (!outdated) {
                    $('#learn-press-template-files tr[data-template]').removeClass('hide-if-js');
                } else {
                    $('#learn-press-template-files tr[data-template]').map(function () {
                        $(this).toggleClass('hide-if-js', $(this).data('outdated') != outdated);
                    })
                }
            } else {
                $('#learn-press-template-files tr[data-template]').map(function () {
                    $(this).toggleClass('hide-if-js', $(this).data('template') != template);
                })
            }
            $('#learn-press-no-templates').toggleClass('hide-if-js', !!$('#learn-press-template-files tr.template-row:not(.hide-if-js):first').length)
            return false;
        })
    })
</script>