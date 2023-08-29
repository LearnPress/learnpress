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
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CourseCategoryDynamicElementor;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\Dynamic\CoursePriceDynamicElementor;
use LearnPress\Helpers\Singleton;

class LPElementor {
	use Singleton;
	public static $group_dynamic = 'learn-press-dynamic';
	public static $cate_course   = 'learnpress_course';

	protected function init() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 10, 1 );
		add_action( 'elementor/dynamic_tags/register', array( $this, 'register_tags' ) );
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
			self::$cate_course      => [
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

	/**
	 *
	 * @param Manager $dynamic_tags
	 *
	 * @return void
	 */
	public function register_tags( Manager $dynamic_tags ) {
		// Register group learn-press-dynamic
		$dynamic_tags->register_group(
			self::$group_dynamic,
			array(
				'title' => esc_html__(
					'LearnPress',
					'learnpress'
				),
			)
		);

		$tag_classes_names = [
			CoursePriceDynamicElementor::class,
			CourseCategoryDynamicElementor::class,
		];

		foreach ( $tag_classes_names as $tag_class_name ) {
			$dynamic_tags->register( new $tag_class_name );
		}
	}
}
