<?php
/**
 * Template for displaying curriculum tab of single course.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( empty( $section ) ) {
	return;
}
?>

<li id="section-<?php echo esc_attr( $section['section_id'] ); ?>" class="section" data-section-id="<?php echo esc_attr( $section['section_id'] ); ?>">
	<div class="section-header">
		<div class="section-left">
			<h3 class="section-title">
				<?php echo ! empty( $section['section_name'] ) ? esc_html( $section['section_name'] ) : _x( 'Untitled', 'template title empty', 'learnpress' ); ?>

				<?php if ( ! empty( $section['section_description'] ) ) : ?>
					<p class="section-desc"><?php echo wp_kses_post( $section['section_description'] ); ?></p>
				<?php endif; ?>
			</h3>

			<span class="section-toggle">
				<i class="fas fa-caret-down"></i>
				<i class="fas fa-caret-up"></i>
			</span>
		</div>
	</div>

	<?php
	$controller = new LP_REST_Lazy_Load_Controller();
	$request    = new WP_REST_Request();
	$request->set_param( 'sectionId', $section['section_id'] );
	$response    = $controller->course_curriculum_items( $request );
	$object_data = $response->get_data();
	?>

	<div class="section-item" data-section-id="<?php echo esc_attr( $section['section_id'] ); ?>">
		<ul class="section-content">
			<?php echo isset( $object_data->data ) ? wp_kses_post( $object_data->data ) : ''; ?>
		</ul>

		<?php if ( ! empty( $object_data->pages ) && $object_data->pages > 1 ) : ?>
			<div class="section-item__loadmore" data-page="1">
				<button><?php esc_html_e( 'Show more items', 'learnpress' ); ?></button>
			</div>
		<?php endif; ?>
	</div>
</li>
