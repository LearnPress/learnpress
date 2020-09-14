<?php
/**
 * Template for displaying outdated template files in theme/child-themes
 *
 * @author ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || die();

$templates          = LP_Outdated_Template_Helper::get_theme_templates();
$counts             = LP_Outdated_Template_Helper::$counts;
$theme              = wp_get_theme();
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

	<?php if ( $templates ) : ?>
		<tr>
			<th class="template-file">
				<?php _e( 'File', 'learnpress' ); ?>
				<p>
					<a href="" class="learn-press-filter-template current"
					   data-template=""><?php printf( __( 'All (%d)', 'learnpress' ), $counts['all'] ); ?></a>
					<?php if ( $theme_folder && $child_theme_folder ) { ?>
						<a href="" class="learn-press-filter-template"
						   data-template="<?php echo esc_attr( $theme_folder ); ?>"><?php echo learn_press_get_theme_name( $theme_folder ); ?></a>
						<a href="" class="learn-press-filter-template"
						   data-template="<?php echo esc_attr( $child_theme_folder ); ?>"><?php echo learn_press_get_theme_name( $child_theme_folder ); ?></a>
					<?php } ?>
					<a href="" class="learn-press-filter-template"
					   data-filter="outdated"><?php printf( __( 'Outdated (%d)', 'learnpress' ), $counts['outdated'] ); ?></a>
					<a href="" class="learn-press-filter-template"
					   data-filter="unversioned"><?php printf( __( 'Unversioned (%d)', 'learnpress' ), $counts['unversioned'] ); ?></a>
				</p>
			</th>
			<th class="template-version">
				<?php _e( 'Version', 'learnpress' ); ?>
			</th>
			<th class="core-version"><?php _e( 'Core version', 'learnpress' ); ?></th>
		</tr>

		<?php foreach ( $templates as $template ) : ?>
			<?php
			$template_folder = '';
			if ( $child_theme_folder && strpos( $template[0], $child_theme_folder ) !== false ) {
				$template_folder = $child_theme_folder;
			} else {
				$template_folder = $theme_folder;
			}
			$template_class = $template[3] ? 'outdated' : ( $template[1] == '-' && $template[2] == '-' ? '' : 'up-to-date' );
			$filter         = $template[3] ? 'outdated' : ( $template[1] == '-' ? 'unversioned' : '' );
			?>

			<tr data-template="<?php echo esc_attr( $template_folder ); ?>"
				<?php
				if ( $template[3] ) {
					echo 'data-filter-outdated="yes"';
				}
				?>
				<?php
				if ( $template[1] == '-' ) {
					echo 'data-filter-unversioned="yes"';
				}
				?>
				class="template-row <?php echo $template_class; ?>">
				<td class="template-file"><code><?php echo $template[0]; ?></code></td>
				<td class="template-version">
					<span><?php echo $template[1]; ?></span>
				</td>
				<td class="core-version"><span><?php echo $template[2]; ?></span></td>
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

