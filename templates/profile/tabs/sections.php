<?php
/**
 * Template for displaying sections in the top of user profile tab content.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/tabs/sections.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();


if ( ! isset( $tab_key, $tab_data ) ) {
	return;
}

if ( empty( $tab_data['sections'] ) || ( sizeof( $tab_data['sections'] ) < 2 ) ) {
	return;
}

$link         = $profile->get_tab_link( $tab_key );
$unique_group = uniqid( 'course-tab-' );
$sections     = $tab_data['sections'];
$visible_tabs = array();
$active_tab   = '';
?>

<div class="learn-press-tabs">
	<?php
	foreach ( $sections as $section_key => $section_data ) {
		if ( $profile->is_hidden( $section_data ) ) {
			continue;
		}

		$visible_tabs[] = $section_key;
		$checked        = '';

		if ( $profile->is_current_section( $section_key, $section_key ) ) {
			$active_tab = $section_key;
			$checked    = checked( true, true, false );
		}
		?>

		<input type="radio" name="<?php echo $unique_group; ?>" class="learn-press-tabs__checker" <?php echo $checked; ?> id="<?php echo esc_attr( $unique_group . '__' . $section_key ); ?>"/>
	<?php } ?>

	<ul class="learn-press-tabs__nav" data-tabs="<?php echo count( $visible_tabs ); ?>">
		<?php
		foreach ( $sections as $section_key => $section_data ) {
			if ( ! in_array( $section_key, $visible_tabs ) ) {
				continue;
			}

			$classes = array( 'learn-press-tabs__tab', esc_attr( $section_key ) );

			if ( $active_tab == $section_key ) {
				$classes[] = 'active';
			}

			$section_slug = $profile->get_slug( $section_data, $section_key );
			$section_link = $profile->get_tab_link( $tab_key, $section_slug );
			?>

			<li class="<?php echo implode( ' ', $classes ); ?>">
				<label><a href="<?php echo esc_url( $section_link ); ?>"><?php echo esc_html( $section_data['title'] ); ?></a></label>
			</li>

		<?php } ?>

	</ul>
</div>

