<?php
/**
 * Template for displaying tab nav of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tabs/tabs.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$tabs = learn_press_get_course_tabs();

if ( empty( $tabs ) ) {
	return;
}
$all_in_one = false;
?>

<div id="learn-press-course-tabs" class="course-tabs<?php echo $all_in_one ? ' show-all' : ''; ?>">
	<?php if ( ! $all_in_one ) { ?>
        <ul class="learn-press-nav-tabs course-nav-tabs" data-tabs="<?php echo sizeof( $tabs ); ?>">

			<?php foreach ( $tabs as $key => $tab ) { ?>

				<?php $classes = array( 'course-nav course-nav-tab-' . esc_attr( $key ) );
				if ( ! empty( $tab['active'] ) && $tab['active'] ) {
					$classes[] = 'active default';
				} ?>

                <li class="<?php echo join( ' ', $classes ); ?>">
                    <a href="?tab=<?php echo esc_attr( $tab['id'] ); ?>"
                       data-tab="#<?php echo esc_attr( $tab['id'] ); ?>"><?php echo $tab['title']; ?></a>
                </li>

			<?php } ?>

        </ul>

	<?php } ?>
	<?php foreach ( $tabs as $key => $tab ) {

		$is_active = ( ! empty( $tab['active'] ) && $tab['active'] ) || $all_in_one;
		?>

        <div class="course-tab-panel-<?php echo esc_attr( $key ); ?> course-tab-panel<?php echo $is_active ? ' active' : ''; ?>"
             id="<?php echo esc_attr( $tab['id'] ); ?>">

			<?php
			if ( apply_filters( 'learn_press_allow_display_tab_section', $is_active, $key, $tab ) ) {

				if ( $all_in_one ) {
					?>
                    <h3><?php echo $tab['title']; ?></h3>
					<?php
				}

				if ( is_callable( $tab['callback'] ) ) {
					call_user_func( $tab['callback'], $key, $tab );
				} else {
					/**
					 * @since 3.0.0
					 */
					do_action( 'learn-press/course-tab-content', $key, $tab );
				}
			}
			?>

        </div>

	<?php } ?>

</div>