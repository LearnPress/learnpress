<?php
/**
 * Template for displaying tab nav
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit();

$tabs = learn_press_get_course_tabs();

if ( empty( $tabs ) ) {
	return;
}
?>
<div id="learn-press-course-tabs" class="course-tabs">
    <ul class="learn-press-nav-tabs course-nav-tabs">
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<?php
			$classes = array( 'course-nav course-nav-tab-' . esc_attr( $key ) );
			if ( ! empty( $tab['active'] ) && $tab['active'] ) {
				$classes[] = 'active default';
			}
			?>
            <li class="<?php echo join( ' ', $classes ); ?>">
                <a href="?tab=<?php echo esc_attr( $tab['id'] ); ?>"
                   data-tab="#<?php echo esc_attr( $tab['id'] ); ?>"><?php echo $tab['title']; ?></a>
            </li>
		<?php endforeach; ?>
    </ul>

	<?php foreach ( $tabs as $key => $tab ) : ?>
        <div class="course-tab-panel-<?php echo esc_attr( $key ); ?> course-tab-panel<?php echo ! empty( $tab['active'] ) && $tab['active'] ? ' active' : ''; ?>"
             id="<?php echo esc_attr( $tab['id'] ); ?>">

			<?php
			if ( apply_filters( 'learn_press_allow_display_tab_section', true, $key, $tab ) ) :
				if ( is_callable( $tab['callback'] ) ) {
					call_user_func( $tab['callback'], $key, $tab );
				} else {
					/**
					 * @since 3.x.x
					 */
					do_action( 'learn-press/course-tab-content', $key, $tab );
				}
			endif;
			?>

        </div>
	<?php endforeach; ?>
</div>