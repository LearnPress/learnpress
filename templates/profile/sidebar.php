<?php
/**
 * Template for displaying sidebar in user profile.
 *
 * @author ThimPress
 * @package LearnPress/Templates
 * @version 4.0.0
 */

use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit;


$sections = apply_filters(
	'learn-press/profile/sidebar/sections',
	array(
		'profile/sidebar/header.php',
		'profile/sidebar/sidebar.php',
	)
);

Template::instance()->get_frontend_templates( $sections );
