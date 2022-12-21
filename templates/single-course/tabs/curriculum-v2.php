<?php
/**
 * Template for displaying curriculum tab of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/tabs/curriculum.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.1.6
 */

defined( 'ABSPATH' ) || exit();

if ( empty( $args ) ) {
	return;
}

if ( isset( $args['sections'] ) && isset( $args['filters'] ) ) {
	$sections = $args['sections'];
	$filters  = $args['filters'];
} else {
	return;
}
?>

<div class="course-curriculum" id="learn-press-course-curriculum">
	<div class="curriculum-scrollable">
		<?php if ( $sections['total'] > 0 ) : ?>
			<ul class="curriculum-sections">
				<?php
				foreach ( $sections['results'] as $section ) :
					$args['section'] = $section;
					learn_press_get_template( 'loop/single-course/loop-section', $args );
				endforeach;
				?>
			</ul>
		<?php else : ?>
			<?php
			echo wp_kses_post( apply_filters( 'learnpress/course/curriculum/empty', esc_html__( 'The curriculum is empty', 'learnpress' ) ) );
			?>
		<?php endif; ?>
	</div>

	<?php if ( $sections['pages'] > 1 && $sections['pages'] > $filters->page ) : ?>
		<div class="curriculum-more">
			<button class="curriculum-more__button" data-page="<?php echo esc_attr( $filters->page ); ?>">
				<?php esc_html_e( 'Show more Sections', 'learnpress' ); ?>
			</button>
		</div>
	<?php endif; ?>
</div>
