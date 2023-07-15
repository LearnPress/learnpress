<?php
/**
 * Template for displaying instructor statistic in user profile overview.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0.0
 */

use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit;

if ( empty( $data ) ) {
	return;
}

$html_wrapper = apply_filters(
	'learn-press/profile/layout/instructor-statistics/wrapper',
	[
		'<div id="dashboard-statistic">'         => '</div>',
		'<div class="dashboard-statistic__row">' => '</div>',
	]
);

ob_start();
do_action( 'learn-press/profile/layout/instructor-statistics', $data );
$inner_html = ob_get_clean();
echo Template::instance()->nest_elements( $html_wrapper, $inner_html );
