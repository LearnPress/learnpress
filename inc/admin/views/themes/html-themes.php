<?php
/**
 * Admin View: Themes page.
 *
 * @package LearnPress/Admin/Views
 * @since 4.4.7
 * @version 1.0.0
 */

use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\Admin\AdminThemesDataTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) || exit;

$content = TemplateAJAX::load_content_via_ajax(
	array(
		'id_url' => 'data-themes',
	),
	array(
		'class'  => AdminThemesDataTemplate::class,
		'method' => 'html_data_online',
	)
);
echo AdminTemplate::html_on_wp_admin_screen(
	array(
		'content' => $content,
		'title'   => __( 'Recommended Themes for LearnPress', 'learnpress' ),
		'id'      => 'learn-press-themes',
	)
);
