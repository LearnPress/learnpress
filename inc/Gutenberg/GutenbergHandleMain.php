<?php

namespace LearnPress\Gutenberg;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Gutenberg\Templates\AbstractBlockTemplate;
use LearnPress\Gutenberg\Templates\SingleCourseItemBlockTemplate;
use LearnPress\Gutenberg\Templates\SingleCourseOfflineBlockTemplate;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use WP_Block_Template;
use WP_Post;

/**
 * Class GutenbergHandleMain
 *
 * Handle register, render block template
 * @since 4.2.8.3 Convert from old class Block_Template_Handle
 * @version 1.0.0
 */
class GutenbergHandleMain {
	use Singleton;

	/**
	 * Hooks handle block template
	 */
	public function init() {
		if ( ! wp_is_block_theme() ) {
			return;
		}

		// Register block template and register patterns.
		add_action( 'init', array( $this, 'wp_hook_init' ) );
		// Set block template need to show on frontend/backend
		add_filter( 'get_block_templates', array( $this, 'set_blocks_template' ), 10, 3 );
		// Load block template when Edit.
		add_filter( 'pre_get_block_file_template', array( $this, 'edit_block_file_template' ), 10, 3 );
		// Register block category
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 10, 2 );
		// Fixed case code block of WooCommerce run on screen list Order is error.
		add_filter(
			'current_theme_supports-block-templates',
			function ( $flag ) {
				if ( ! is_admin() ) {
					return $flag;
				}

				if ( ! function_exists( 'get_current_screen' ) ) {
					return $flag;
				}

				$wp_screen = get_current_screen();
				if ( $wp_screen->id === 'edit-lp_order' ) {
					$flag = false;
				}

				return $flag;
			}
		);
	}

	/**
	 * Hook init of WordPress
	 * .1 Register blocks Gutenberg
	 *
	 * @return void
	 */
	public function wp_hook_init() {
		$this->register_blocks();
		$this->add_block_pattern_categories();
		$this->add_block_patterns();
	}

	/**
	 * Register block, render content of block
	 *
	 * @return void
	 */
	public function register_blocks() {
		/**
		 * @var AbstractBlockType[] $blocks
		 */
		$blocks = Config::instance()->get( 'block-elements', 'gutenberg' );
		//$template_current = $this->get_edit_template();

		foreach ( $blocks as $block_template ) {
			// Set block maybe display when Edit on Template.
			/*if ( ! empty( $template_current ) && ! empty( $block_template->display_on_templates )
				&& ! in_array( $template_current, $block_template->display_on_templates ) ) {
				if ( ! empty( $block_template->ancestor ) ) {
					$block_template->display_on_templates = [];
				} else {
					continue;
				}
			} elseif ( ! empty( $block_template->ancestor )
				&& ! empty( $block_template->display_on_templates ) ) {
				$block_template->ancestor = null;
			}*/

			// Register script to load on the Backend Edit.
			wp_register_script(
				$block_template->name, // Block name
				$block_template->source_js, // Block script
				[], // Dependencies
				uniqid(), // Version,
				[ 'strategy' => 'defer' ]
			);

			$args = [];
			foreach ( get_object_vars( $block_template ) as $property => $value ) {
				$args[ $property ] = $value;
			}

			$block_type = $block_template->name;
			/**
			 * register_block_type_from_metadata
			 *
			 * For case declare path to file block.json
			 */
			if ( ! empty( $block_template->path_block_json ) ) {
				$block_type = $block_template->path_block_json;
			}

			register_block_type(
				$block_type,
				$args
			);
		}
	}

	/**
	 * Set block template need to show on frontend/backend
	 * 1. Get all templates of LP declare.
	 * 2. Frontend: check blocks match with type page. Ex: single course, archive course....
	 * 3. If correct, set Block to $query_result.
	 *
	 * @param array $query_result Array of template objects.
	 * @param array $query Optional. Arguments to retrieve templates.
	 * @param mixed $template_type wp_template or wp_template_part.
	 *
	 * @return array
	 * @since 4.2.2.3
	 * @version 1.0.6
	 */
	public function set_blocks_template( array $query_result, array $query, $template_type ): array {
		if ( $template_type === 'wp_template_part' ) { // Template not Template part
			return $query_result;
		}

		if ( ! is_admin() && post_password_required() ) {
			return $query_result;
		}

		// Check is course item.
		if ( ! is_admin() ) {
			global $wp;
			$vars           = $wp->query_vars;
			$item_type      = $vars['item-type'] ?? '';
			$lp_course_item = learn_press_get_post_by_name( $vars['course-item'] ?? '', $item_type );
			$item_types     = CourseModel::item_types_support();
			if ( $lp_course_item && in_array( $lp_course_item->post_type, $item_types ) ) {
				$singleCourseItemBlockTemplate = new SingleCourseItemBlockTemplate();
				$block_custom                  = $this->is_custom_block_template( $template_type, $singleCourseItemBlockTemplate->slug );
				if ( $block_custom ) {
					$singleCourseItemBlockTemplate->is_custom = true;
					$singleCourseItemBlockTemplate->source    = 'custom';
					$singleCourseItemBlockTemplate->content   = traverse_and_serialize_blocks( parse_blocks( $block_custom->post_content ) );
				}

				/**
				 * Set slug to single course, to compare with slug in query.
				 * Because don't have slug 'single-lp_course_item'.
				 * Slug 'single-lp_course_item' is for save content of block template.
				 */
				$singleCourseItemBlockTemplate->slug = 'single-lp_course';
				$query_result[]                      = $singleCourseItemBlockTemplate;
				return $query_result;
			}
		}
		// End check course item.

		// Check is course offline.
		if ( ! is_admin() ) {
			global $wp;
			$object = get_queried_object();
			if ( $object && isset( $object->ID ) ) {
				$courseModel = CourseModel::find( $object->ID, true );
				if ( $courseModel && $courseModel->is_offline() ) {
					$singleCourseOfflineBlockTemplate = new SingleCourseOfflineBlockTemplate();
					$block_custom                     = $this->is_custom_block_template( $template_type, $singleCourseOfflineBlockTemplate->slug );
					if ( $block_custom ) {
						$singleCourseOfflineBlockTemplate->is_custom = true;
						$singleCourseOfflineBlockTemplate->source    = 'custom';
						$singleCourseOfflineBlockTemplate->content   = traverse_and_serialize_blocks( parse_blocks( $block_custom->post_content ) );
					}

					$singleCourseOfflineBlockTemplate->slug = 'single-lp_course';
					$query_result[]                         = $singleCourseOfflineBlockTemplate;
					return $query_result;
				}
			}
		}
		// End check course offline.

		// wp_enqueue_script( 'editor-check' );

		/**
		 * @var AbstractBlockTemplate[] $lp_block_templates
		 */
		$lp_block_templates = Config::instance()->get( 'block-templates', 'gutenberg' );
		foreach ( $lp_block_templates as $block_template ) {

			$instance = new $block_template();
			if ( isset( $query['post_type'] ) &&
				isset( $instance->post_types ) &&
				! in_array( $query['post_type'], $instance->post_types, true )
				) {
					continue;
			}

			// Get block template if custom - save on table posts - with post_name = slug of block.
			$block_custom = $this->is_custom_block_template( $template_type, $block_template->slug );
			if ( $block_custom ) {
				$block_template->is_custom = true;
				$block_template->source    = 'custom';
				if ( version_compare( get_bloginfo( 'version' ), '6.4-beta', '>=' ) ) {
					$block_template->content = traverse_and_serialize_blocks( parse_blocks( $block_custom->post_content ) );
				} else {
					$block_template->content = _inject_theme_attribute_in_block_template_content( $block_custom->post_content );
				}
			}

			// Frontend: check block template match with slug in query will display.
			$fits_slug_query = ! isset( $query['slug__in'] ) || in_array( $instance->slug, $query['slug__in'], true );
			$fits_area_query = ! isset( $query['area'] ) || ( property_exists( $instance, 'area' ) && $instance->area === $query['area'] );
			$should_include  = $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$query_result[] = $block_template;
			}
		}

		return $query_result;
	}

	/**
	 * Load template block when edit and save.
	 *
	 * @param WP_Block_Template|null|mixed $template
	 * @param string $id
	 * @param string $template_type
	 *
	 * @return WP_Block_Template|null
	 */
	public function edit_block_file_template( $template = null, string $id = '', string $template_type = '' ) {
		/**
		 * @var AbstractBlockTemplate[] $template
		 */
		$lp_block_templates = Config::instance()->get( 'block-templates', 'gutenberg' );
		foreach ( $lp_block_templates as $block_template ) {
			if ( $id === $block_template->id ) {
				$template = $block_template;
				break;
			}
		}

		return $template;
	}

	/**
	 * Check is custom block template
	 *
	 * @param $template_type
	 * @param $post_name
	 *
	 * @return WP_Post|null
	 */
	public function is_custom_block_template( $template_type, $post_name ) {
		$post_block_theme = null;

		$check_query_args = array(
			'post_type'      => $template_type,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
			'post_name__in'  => array( $post_name ),
		);

		$check = new \WP_Query( $check_query_args );

		if ( count( $check->get_posts() ) > 0 ) {
			$post_block_theme = $check->get_posts()[0];
		}

		return $post_block_theme;
	}

	/**
	 * Register block category
	 *
	 * @param array $block_categories
	 * @param $editor_context
	 *
	 * @return array
	 */
	public function add_block_category( array $block_categories, $editor_context ) {
		$lp_block_categories = [
			[
				'slug'  => 'learnpress-legacy',
				'title' => __( 'LearnPress Legacy', 'learnpress' ),
				'icon'  => null,
			],
			[
				'slug'  => 'learnpress-category',
				'title' => __( 'LearnPress Global', 'learnpress' ),
				'icon'  => null,
			],
			[
				'slug'  => 'learnpress-course-elements',
				'title' => __( 'LearnPress Course Elements', 'learnpress' ),
				'icon'  => null,
			],
		];

		foreach ( $lp_block_categories as $block_category ) {
			array_unshift( $block_categories, $block_category );
		}

		return $block_categories;
	}

	public function get_edit_template() {
		$template_current = '';

		if ( is_admin() && isset( $_GET['p'] ) ) {
			if ( function_exists( '\get_current_screen' ) ) {
				$screen = \get_current_screen();
				if ( $screen && (
					$screen->base === 'site-editor' ||
					$screen->id === 'appearance_page_gutenberg-edit-site' ||
					strpos( $screen->id, 'edit-site' ) !== false
				) ) {
					$template_path    = urldecode( $_GET['p'] );
					$template_path    = str_replace( '/wp_template/', '', $template_path );
					$template_current = trim( $template_path, '/' );
				}
			} else {
				$template_path    = urldecode( $_GET['p'] );
				$template_path    = str_replace( '/wp_template/', '', $template_path );
				$template_current = trim( $template_path, '/' );
			}
		}

		if ( empty( $template_current ) ) {
			if ( ! empty( $_REQUEST['postId'] ) ) {
				$template_current = $_REQUEST['postId'];
			} elseif ( ! empty( $_REQUEST['post'] ) ) {
				$template_current = $_REQUEST['post'];
			} elseif ( ! empty( $_REQUEST['post_id'] ) ) {
				$template_current = $_REQUEST['post_id'];
			} elseif ( function_exists( 'get_the_ID' ) && get_the_ID() ) {
				$template_current = get_the_ID();
			} elseif ( isset( $GLOBALS['post'] ) && ! empty( $GLOBALS['post']->ID ) ) {
				$template_current = $GLOBALS['post']->ID;
			}
			if ( ! is_numeric( $template_current ) ) {
				$template_post = get_page_by_path( $template_current, OBJECT, 'wp_template' );
				if ( $template_post ) {
					$template_current = $template_post->ID;
				}
			}
		}

		return $template_current;
	}

	public function add_block_patterns() {
		$list_course_pattern = file_get_contents( Template::instance( false )->get_frontend_template_type_block( 'patterns/list-courses-pattern.html' ) );
		register_block_pattern(
			'learnpress/list-course-pattern',
			array(
				'title'       => __( 'Layout List Course', 'learnpress' ),
				'description' => __( 'List Course Learnpress', 'learnpress' ),
				'categories'  => array( 'learnpress-patterns' ),
				'content'     => $list_course_pattern,
			)
		);
		$grid_course_pattern = file_get_contents( Template::instance( false )->get_frontend_template_type_block( 'patterns/grid-courses-pattern.html' ) );
		register_block_pattern(
			'learnpress/grid-course-pattern',
			array(
				'title'       => __( 'Layout Grid Course', 'learnpress' ),
				'description' => __( 'List Course Learnpress - layout grid', 'learnpress' ),
				'categories'  => array( 'learnpress-patterns' ),
				'content'     => $grid_course_pattern,
			)
		);

		$single_instructor_pattern = file_get_contents( Template::instance( false )->get_frontend_template_type_block( 'patterns/single-instructor-pattern.html' ) );
		register_block_pattern(
			'learnpress/single-instructor-pattern',
			array(
				'title'       => __( 'Single Instructor', 'learnpress' ),
				'description' => __( 'Single Instructor Learnpress', 'learnpress' ),
				'categories'  => array( 'learnpress-patterns' ),
				'content'     => $single_instructor_pattern,
			)
		);
	}

	public function add_block_pattern_categories() {
		register_block_pattern_category(
			'learnpress-patterns',
			array( 'label' => __( 'LearnPress', 'learnpress' ) )
		);
	}
}
