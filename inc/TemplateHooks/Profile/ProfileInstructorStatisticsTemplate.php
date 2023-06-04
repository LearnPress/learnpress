<?php
/**
 * Template hooks Archive Package.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
namespace LearnPress\TemplateHooks\Profile;

use LearnPress\Helpers\Template;

class ProfileInstructorStatisticsTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'learn-press/profile/layout/instructor-statistics', [ $this, 'sections' ], 2 );
	}

	public function sections( $data ) {
		$template_path = apply_filters(
			'learn-press/profile/layout/instructor-statistics/item-count',
			LP_PLUGIN_PATH . 'templates/profile/tabs/statistics/item-count.php'
		);
		Template::instance()->get_template(
			$template_path,
			compact( 'data' )
		);
	}
}
