<?php
/**
 * Admin View: Themes page.
 *
 * @package LearnPress/Admin/Views
 * @since 4.4.7
 * @version 1.0.0
 */

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Admin\AdminThemesDataTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) || exit;

$section = apply_filters(
	'learn-press/admin/themes/page-section',
	array(
		'wrapper'     => '<div class="lp-themes-page">',
		'title'       => sprintf(
			'<h1 class="lp-be-page-title">%s</h1>',
			esc_html__( 'Recommended Themes for LearnPress', 'learnpress' )
		),
		'subtitle'    => sprintf(
			'<p class="lp-be-page-subtitle">%s</p>',
			esc_html__(
				'Discover high-performance Premium & Education themes optimized 100% for LearnPress LMS.',
				'learnpress'
			)
		),
		'themes'      => TemplateAJAX::load_content_via_ajax(
			array(
				'id_url' => 'data-themes',
			),
			array(
				'class'  => AdminThemesDataTemplate::class,
				'method' => 'html_data_online',
			)
		),
		'wrapper_end' => '</div>',
	)
);

echo AdminTemplate::html_on_wp_admin_screen(
	array(
		'content' => Template::combine_components( $section ),
		'title'   => '',
		'id'      => 'learn-press-themes',
	)
);
