<?php
/**
 * Elementor Controls for skin Courses Grid.
 *
 * @since 4.2.5.7
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Thim_EL_Kit\Functions;

/**
 * @var LearnPress\ExternalPlugin\Elementor\Widgets\Course\Skins\CoursesGrid $CoursesGrid
 */
if ( ! isset( $CoursesGrid ) ) {
	return;
}

if ( ! class_exists( Functions::class ) ) {
	return;
}

$CoursesGrid->add_responsive_control(
	'columns',
	array(
		'label'          => esc_html__( 'Columns', 'learnpress' ),
		'type'           => Controls_Manager::NUMBER,
		'default'        => 3,
		'min'            => 1,
		'selectors'      => array(
			'{{WRAPPER}}' => '--lp-el-courses-grid-columns: {{VALUE}}',
		),
	)
);
