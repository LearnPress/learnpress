<?php
/**
 * Class LP_Elementor_Widgets
 *
 * @since 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor;
use Elementor\Elements_Manager;
use LearnPress\Helpers\Singleton;

class LPElementor {
	use Singleton;

	protected function init() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 10, 1 );
	}

	/**
	 * Register category LearnPress
	 *
	 * @param Elements_Manager $elements_manager
	 *
	 * @return void
	 */
	public function register_category( Elements_Manager $elements_manager ) {
		$elements_manager->add_category(
			'learnpress',
			array(
				'title' => esc_html__( 'LearnPress', 'learnpress' ),
				'icon'  => 'eicon-navigator',
			)
		);
	}

	/**
	 * Register widgets
	 *
	 * @param $widgets_manager
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		$widgets = require_once 'lp-elementor-widgets-config.php';
		foreach ( $widgets as $widget => $class ) {
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}
}
