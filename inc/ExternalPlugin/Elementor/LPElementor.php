<?php
/**
 * Class LPElementor
 * Register categories
 * Register widgets
 *
 * @since 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor;
use Elementor\Elements_Manager;
use LearnPress\Helpers\Singleton;
use LP_Page_Controller;
use Throwable;

class LPElementor {
	use Singleton;

	protected function init() {
		add_action( 'learn-press/auto-shortcode', array( $this, 'can_auto_load_shortcode' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 10, 1 );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 10, 1 );
	}

	/**
	 * Check if page of LP edit mode Elementor
	 * will not autoload shortcode
	 *
	 * Default Elementor will replace content of page,
	 * but if not cancel autoload shortcode, shortcode still be load, superfluous, make slow.
	 *
	 * @param bool $auto
	 *
	 * @since 4.2.3
	 * @version 1.0.0
	 * @return bool
	 */
	public function can_auto_load_shortcode( bool $auto ): bool {
		try {
			$page_current = LP_Page_Controller::page_current();
			$pages_auto_shortcode = [
				LP_PAGE_CHECKOUT,
				LP_PAGE_INSTRUCTOR,
				LP_PAGE_INSTRUCTORS,
				LP_PAGE_PROFILE,
				LP_PAGE_BECOME_A_TEACHER
			];

			if ( in_array( $page_current, $pages_auto_shortcode ) ) {
				$page_name = str_replace('lp_page_', '', $page_current );
				$page_id = learn_press_get_page_id( $page_name );
				if ( get_post_meta( $page_id, '_elementor_edit_mode', true ) ) {
					$auto = false;
				}
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $auto;
	}

	/**
	 * Register category LearnPress
	 *
	 * @param Elements_Manager $elements_manager
	 *
	 * @return void
	 */
	public function register_category( Elements_Manager $elements_manager ) {
		$categories = [
			'learnpress'            => [
				'title' => esc_html__( 'LearnPress', 'learnpress' ),
				'icon'  => 'eicon-navigator',
			],
			'learnpress_instructor' => [
				'title' => esc_html__( 'LearnPress Instructor Sections', 'learnpress' ),
				'icon'  => 'eicon-navigator',
			],
		];

		$old_categories = $elements_manager->get_categories();
		$categories     = array_merge( $categories, $old_categories );

		$set_categories = function ( $categories ) {
			$this->categories = $categories;
		};
		$set_categories->call( $elements_manager, $categories );
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
