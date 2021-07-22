<?php
/**
 * Template for displaying top-bar in archive course page.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

$layouts = learn_press_courses_layouts();
$active  = learn_press_get_courses_layout();
$s       = LP_Request::get( 's' );
?>

<div class="lp-courses-bar <?php echo esc_attr( $active ); ?>">
	<form class="search-courses" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<input type="hidden" name="post_type" value="<?php echo esc_attr( LP_COURSE_CPT ); ?>">
		<input type="hidden" name="taxonomy" value="<?php echo esc_attr( get_queried_object()->taxonomy ?? $_GET['taxonomy'] ?? '' ); ?>">
		<input type="hidden" name="term_id" value="<?php echo esc_attr( get_queried_object()->term_id ?? $_GET['term_id'] ?? '' ); ?>">
		<input type="hidden" name="term" value="<?php echo esc_attr( get_queried_object()->slug ?? $_GET['term'] ?? '' ); ?>">
		<input type="text" placeholder="<?php esc_attr_e( 'Search courses...', 'learnpress' ); ?>" name="s" value="<?php echo esc_attr( $s ); ?>">
		<button type="submit"><i class="fas fa-search"></i></button>
	</form>

	<div class="switch-layout">
		<?php foreach ( $layouts as $layout ) : ?>
			<input type="radio" name="lp-switch-layout-btn" value="<?php echo esc_attr( $layout ); ?>" id="lp-switch-layout-btn-<?php echo esc_attr( $layout ); ?>" <?php checked( $layout, $active ); ?>>
			<label class="switch-btn <?php echo $layout; ?>" title="<?php echo sprintf( esc_attr__( 'Switch to %s', 'learnpress' ), $layout ); ?>" for="lp-switch-layout-btn-<?php echo esc_attr( $layout ); ?>"></label>
		<?php endforeach; ?>
	</div>
</div>
