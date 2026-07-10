<?php
/**
 * Role default
 *
 * @package LearnPress/Config
 * @version 1.0.0
 * @since 4.4.2
 */

use LearnPress\Models\UserModel;

defined( 'ABSPATH' ) || exit;

$config = [];

$config[ UserModel::ROLE_INSTRUCTOR ] = apply_filters(
	'learn-press/role/instructor/capabilities_default',
	[
		'label'               => 'Instructor',
		'prefix_capabilities' => [
			'publish',
			'edit',
			'delete',
			'edit_published',
			'delete_published',
			'read_private',
			'edit_private',
			'delete_private',
		],
	],
);

$config[ UserModel::ROLE_ADMINISTRATOR ] = apply_filters(
	'learn-press/role/admin/capabilities_default',
	[
		'label'               => 'Administrator',
		'prefix_capabilities' => array_merge(
			$config[ UserModel::ROLE_INSTRUCTOR ]['prefix_capabilities'],
			[
				'edit_others',
				'delete_others',
			]
		),
	]
);

return apply_filters( 'learn-press/roles/capabilities_default', $config );
