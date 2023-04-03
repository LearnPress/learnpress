<?php
/**
 * Template for displaying tab nav of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tabs/tabs.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.1
 */

defined( 'ABSPATH' ) || exit();

$tabs = learn_press_get_course_tabs();

if ( empty( $tabs ) ) {
	return;
}

$active_tab = 'overview';

// Show status course
$lp_user = learn_press_get_current_user();

if ( $lp_user && ! $lp_user instanceof LP_User_Guest ) {
	$can_view_course = $lp_user->can_view_content_course( get_the_ID() );

	if ( ! $can_view_course->flag ) {
		if ( LP_BLOCK_COURSE_FINISHED === $can_view_course->key ) {
			learn_press_display_message(
				esc_html__( 'You finished this course. This course has been blocked', 'learnpress' ),
				'warning'
			);
		} elseif ( LP_BLOCK_COURSE_DURATION_EXPIRE === $can_view_course->key ) {
			learn_press_display_message(
				esc_html__( 'This course has been blocked for expiration', 'learnpress' ),
				'warning'
			);
		}
	}
}
?>

<div id="learn-press-course-tabs" class="course-tabs">
	<?php foreach ( $tabs as $key => $tab ) : ?>
		<input type="radio" name="learn-press-course-tab-radio" id="tab-<?php echo esc_attr( $key ); ?>-input"
			<?php checked( $active_tab === $key ); ?> value="<?php echo esc_attr( $key ); ?>"/>
	<?php endforeach; ?>

	<ul class="learn-press-nav-tabs course-nav-tabs" data-tabs="<?php echo esc_attr( count( $tabs ) ); ?>">
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<?php
			$classes = array( 'course-nav course-nav-tab-' . esc_attr( $key ) );

			if ( $active_tab === $key ) {
				$classes[] = 'active';
			}
			?>

			<li class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
				<label for="tab-<?php echo esc_attr( $key ); ?>-input"><?php echo esc_html( $tab['title'] ); ?></label>
			</li>
		<?php endforeach; ?>

	</ul>

	<div class="course-tab-panels">
		<?php foreach ( $tabs as $key => $tab ) : ?>
			<div class="course-tab-panel-<?php echo esc_attr( $key ); ?> course-tab-panel"
				id="<?php echo esc_attr( $tab['id'] ); ?>">
				<?php
				if ( isset( $tab['callback'] ) && is_callable( $tab['callback'] ) ) {
					call_user_func( $tab['callback'], $key, $tab );
				} else {
					do_action( 'learn-press/course-tab-content', $key, $tab );
				}
				?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
