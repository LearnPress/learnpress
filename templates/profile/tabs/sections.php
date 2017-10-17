<?php
/**
 * Template for displaying sections in the top of profile tab content.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
?>

<?php
global $profile;

if ( ! isset( $tab_key, $tab_data ) ) {
	return;
}

if ( empty( $tab_data['sections'] ) ) {
	return;
}

$link = $profile->get_tab_link( $tab_key );
?>

<ul class="lp-tab-sections">

	<?php foreach ( $tab_data['sections'] as $section_key => $section_data ) {
		$classes = array( 'section-tab', esc_attr( $section_key ) );
		if ( $profile->is_current_section( $section_key, $section_key ) ) {
			$classes[] = 'active';
		}

		$section_slug = $profile->get_slug( $section_data, $section_key );
		$section_link = $profile->get_tab_link( $tab_key, $section_slug );
		?>

        <li class="<?php echo join( ' ', $classes ); ?>">
			<?php if ( $profile->is_current_section( $section_key, $section_key) ) { ?>
                <span><?php echo $section_data['title']; ?></span>
			<?php } else { ?>
                <a href="<?php echo $section_link; ?>"><?php echo $section_data['title']; ?></a>
			<?php } ?>
        </li>

	<?php } ?>

</ul>

