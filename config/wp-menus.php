<?php
/**
 * LearnPress admin submenu definitions.
 *
 * @package  LearnPress
 * @version  1.0.0
 * @since 4.4.5
 */

use LearnPress\TemplateHooks\Admin\AdminAddonsPage;
use LearnPress\TemplateHooks\Admin\AdminListStudentsEnrolled;
use LearnPress\TemplateHooks\Admin\AdminSettingsPage;
use LearnPress\TemplateHooks\Admin\AdminStatisticsReportTable;
use LearnPress\TemplateHooks\Admin\AdminToolsPage;

defined( 'ABSPATH' ) || exit;

$menu_items = apply_filters(
	'learn-press/wp-menus',
	[
		// Statistics - tabbed page.
		'statistic'        => [
			'id'         => 'learn-press-statistics',
			'menu_title' => __( 'Statistics', 'learnpress' ),
			'page_title' => __( 'Statistics', 'learnpress' ),
			'capability' => 'manage_options',
			'priority'   => 10,
			'callback'   => [ AdminStatisticsReportTable::instance(), 'html_statistics' ],
		],
		// Students.
		'student-enrolled' => [
			'id'         => 'learn-press-students-enrolled',
			'menu_title' => __( 'Students', 'learnpress' ),
			'page_title' => __( 'Students', 'learnpress' ),
			'capability' => 'edit_' . LP_COURSE_CPT . 's',
			'priority'   => 20,
			'callback'   => [ AdminListStudentsEnrolled::instance(), 'admin_page_output' ],
		],
		// Add-ons.
		'addons'           => [
			'id'         => 'learn-press-addons',
			'menu_title' => __( 'Add-ons', 'learnpress' ) . '<span class="lp-notify has-addon-update"></span>',
			'page_title' => __( 'LearnPress Add-ons', 'learnpress' ),
			'capability' => 'manage_options',
			'priority'   => 20,
			'callback'   => [ AdminAddonsPage::instance(), 'html_page' ],
		],
		// Themes.
		'themes'           => [
			'id'         => 'learn-press-themes',
			'menu_title' => __( 'Themes', 'learnpress' ),
			'page_title' => __( 'LearnPress Themes', 'learnpress' ),
			'capability' => 'manage_options',
			'priority'   => 20,
			'callback'   => function () {
				learn_press_admin_view( 'addons/html-themes' );
			},
		],
		// Settings.
		'settings'         => [
			'id'         => 'learn-press-settings',
			'menu_title' => esc_html__( 'Settings', 'learnpress' ),
			'page_title' => esc_html__( 'LearnPress Settings', 'learnpress' ),
			'capability' => 'manage_options',
			'priority'   => 30,
			'callback'   => [ AdminSettingsPage::instance(), 'html_page' ],
		],
		// Help Center.
		'help-center'      => [
			'id'         => 'learn-press-help-center',
			'menu_title' => __( 'Help Center', 'learnpress' ),
			'page_title' => __( 'LearnPress Help Center', 'learnpress' ),
			'capability' => 'manage_options',
			'priority'   => 25,
			'callback'   => function () {
				learn_press_admin_view( 'help-center/html-help-center' );
			},
		],
		// Tools.
		'tools'            => [
			'id'         => 'learn-press-tools',
			'menu_title' => __( 'Tools', 'learnpress' ),
			'page_title' => __( 'LearnPress Tools', 'learnpress' ),
			'capability' => 'manage_options',
			'priority'   => 40,
			'callback'   => [ AdminToolsPage::instance(), 'html_page' ],
		],
		// Taxonomy sub-menus - no callback, only the URL as the slug.
		'categories'       => [
			'id'         => 'edit-tags.php?taxonomy=course_category',
			'menu_title' => __( 'Categories', 'learnpress' ),
			'page_title' => __( 'Categories', 'learnpress' ),
			'capability' => 'manage_options',
			'priority'   => 1,
			'callback'   => false,
		],
		'tags'             => [
			'id'         => 'edit-tags.php?taxonomy=course_tag',
			'menu_title' => __( 'Tags', 'learnpress' ),
			'page_title' => __( 'Tags', 'learnpress' ),
			'capability' => 'manage_options',
			'priority'   => 2,
			'callback'   => false,
		],
	]
);

$group_menus = apply_filters(
	'learn-press/wp-admin/menu-group',
	[
		'content'    => [
			'courses'         => 'edit.php?post_type=' . LP_COURSE_CPT,
			'lessons'         => 'edit.php?post_type=' . LP_LESSON_CPT,
			'quizzes'         => 'edit.php?post_type=' . LP_QUIZ_CPT,
			'questions'       => 'edit.php?post_type=' . LP_QUESTION_CPT,
			'course_category' => 'edit-tags.php?taxonomy=course_category',
			'course_tag'      => 'edit-tags.php?taxonomy=course_tag',
		],
		'operations' => [
			'orders'     => 'edit.php?post_type=' . LP_ORDER_CPT,
			'students'   => 'learn-press-students-enrolled',
			'statistics' => 'learn-press-statistics',
		],
		'system'     => [
			'addons'      => 'learn-press-addons',
			'themes'      => 'learn-press-themes',
			'settings'    => 'learn-press-settings',
			'tools'       => 'learn-press-tools',
			'help_center' => 'learn-press-help-center',
		],
	]
);

return [
	'menus'       => $menu_items,
	'group_menus' => $group_menus,
];
