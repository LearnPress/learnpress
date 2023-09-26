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
use Elementor\Core\DynamicTags\Manager;
use Elementor\Elements_Manager;
use LearnPress\Helpers\Singleton;

class LPElementor {
	use Singleton;
	const GROUP_DYNAMIC   = 'learnpress_dynamic';
	const CATE_LP         = 'learnpress';
	const CATE_COURSE     = 'learnpress_course';
	const CATE_INSTRUCTOR = 'learnpress_instructor';
	public $config        = [];

	protected function init() {
		add_action( 'elementor/init', array( $this, 'load_widgets_config' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 10, 1 );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_tags' ) );
	}

	/**
	 * Load widgets config of LP
	 *
	 * @return void
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function load_widgets_config() {
		$this->config = require_once 'lp-elementor-widgets-config.php';
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
			self::CATE_LP         => [
				'title' => esc_html__( 'LearnPress', 'learnpress' ),
				'icon'  => 'eicon-navigator',
			],
			self::CATE_INSTRUCTOR => [
				'title' => esc_html__( 'LearnPress Instructor Sections', 'learnpress' ),
				'icon'  => 'eicon-navigator',
			],
			self::CATE_COURSE     => [
				'title' => esc_html__( 'LearnPress Course Sections', 'learnpress' ),
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
	 * Register widgets for elementor
	 *
	 * @param $widgets_manager
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		foreach ( $this->config['widgets'] as $widget => $class ) {
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}

	/**
	 * Register dynamic tags for elementor
	 *
	 * @param Manager $dynamic_tags
	 *
	 * @return void
	 */
	public function register_tags( Manager $dynamic_tags ) {
		// Register group learn-press-dynamic
		$dynamic_tags->register_group(
			self::GROUP_DYNAMIC,
			array(
				'title' => esc_html__(
					'LearnPress',
					'learnpress'
				),
			)
		);

		foreach ( $this->config['dynamic'] as $key => $tag_class_name ) {
			$dynamic_tags->register( new $tag_class_name() );
		}
	}
}
