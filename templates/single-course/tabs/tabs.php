<?php
/**
 * Template for displaying tab nav of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tabs/tabs.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.3.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$tabs = learn_press_get_course_tabs();

if ( empty( $tabs ) ) {
	return;
}

$active_tab = learn_press_cookie_get( 'course-tab' );

if ( ! $active_tab ) {
	$tab_keys   = array_keys( $tabs );
	$active_tab = reset( $tab_keys );
}

?>

<div id="learn-press-course-tabs" class="course-tabs">
	<?php foreach ( $tabs as $key => $tab ) { ?>
        <input type="radio" name="learn-press-course-tab-radio" id="tab-<?php echo $key; ?>-input"
			<?php checked( $active_tab === $key ); ?>
               value="<?php echo $key; ?>"/>
	<?php } ?>

    <ul class="learn-press-nav-tabs course-nav-tabs" data-tabs="<?php echo sizeof( $tabs ); ?>">

		<?php foreach ( $tabs as $key => $tab ) { ?>

			<?php
			$classes = array( 'course-nav course-nav-tab-' . esc_attr( $key ) );

			if ( $active_tab === $key ) {
				$classes[] = 'active';
			}
			?>

            <li class="<?php echo join( ' ', $classes ); ?>">
                <label for="tab-<?php echo $key; ?>-input"><?php echo $tab['title']; ?></label>
            </li>

		<?php } ?>

    </ul>

    <div class="course-tab-panels">
		<?php foreach ( $tabs as $key => $tab ) { ?>

            <div class="course-tab-panel-<?php echo esc_attr( $key ); ?> course-tab-panel"
                 id="<?php echo esc_attr( $tab['id'] ); ?>">

				<?php
				if ( is_callable( $tab['callback'] ) ) {
					call_user_func( $tab['callback'], $key, $tab );
				} else {
					/**
					 * @since 3.0.0
					 */
					do_action( 'learn-press/course-tab-content', $key, $tab );
				}
				?>

            </div>

		<?php } ?>
    </div>
</div>