<?php
/**
 * Elementor Controls for widget Become a teacher settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Thim_EL_Kit\Functions;

/**
 * @var LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins\CoursesLoopItem $CoursesLoopItem
 */
if ( ! isset( $CoursesLoopItem ) ) {
	return;
}

if ( ! class_exists( Functions::class ) ) {
	return;
}

$layout_loop_items = Functions::instance()->get_pages_loop_item( 'lp_course' );

$CoursesLoopItem->add_control(
	'template_id',
	array(
		'label'         => esc_html__( 'Choose a template', 'thim-elementor-kit' ),
		'type'          => Controls_Manager::SELECT2,
		'default'       => '0',
		'options'       => [ '0' => esc_html__( 'None', 'thim-elementor-kit' ) ] + $layout_loop_items,
		'prevent_empty' => false,
	)
);
