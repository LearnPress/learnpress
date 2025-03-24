<?php

namespace LearnPress\Gutenberg;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Gutenberg\Templates\AbstractBlockTemplate;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use WP_Block_Template;
use WP_Post;

/**
 * Class GutenbergHandleMain
 *
 * Handle register, render block template
 * @since 4.2.8.2 Convert from old class Block_Template_Handle
 * @version 1.0.0
 */
class GutenbergHandleMain {
	use Singleton;

	/**
	 * Hooks handle block template
	 */
	public function init() {
		// Register block template
		add_action( 'init', array( $this, 'wp_hook_init' ) );
		// Set block template need to show on frontend/backend
		add_filter( 'get_block_templates', array( $this, 'set_blocks_template' ), 10, 3 );
		// Load block template when Edit.
		add_filter( 'pre_get_block_file_template', array( $this, 'edit_block_file_template' ), 10, 3 );
		// Register block category
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 10, 2 );
	}

	/**
	 * Hook init of WordPress
	 * .1 Register blocks Gutenberg
	 *
	 * @return void
	 */
	public function wp_hook_init() {
		$this->register_blocks();
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
		foreach ( $blocks as $block_template ) {
			// Set block maybe display when Edit on Template.
			$postIdEdit = $this->get_edit_post_id();
			if ( ! empty( $postIdEdit ) && ! empty( $block_template->display_on_templates )
				&& ! in_array( $postIdEdit, $block_template->display_on_templates ) ) {
				if ( ! empty( $block_template->ancestor ) ) {
					$block_template->display_on_templates = null;
				} else {
					continue;
				}
			} elseif ( ! empty( $block_template->ancestor )
				&& ! empty( $block_template->display_on_templates ) ) {
				/**
				 * Allow display block on template without click parent block confined via ancestor.
				 * Must set ancestor on PHP block, not config on block.json to apply case.
				 */
				$block_template->ancestor = null;
			}

			// Register script to load on the Backend Edit.
			wp_register_script(
				$block_template->name, // Block name
				$block_template->source_js, // Block script
				array( 'wp-blocks' ), // Dependencies
				uniqid(), // Version,
				[ 'strategy' => 'async' ]
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
	 */
	public function set_blocks_template( array $query_result, array $query, $template_type ): array {
		if ( $template_type === 'wp_template_part' ) { // Template not Template part
			return $query_result;
		}

		/**
		 * @var AbstractBlockTemplate[] $lp_block_templates
		 */
		$lp_block_templates = Config::instance()->get( 'block-templates', 'gutenberg' );
		foreach ( $lp_block_templates as $block_template ) {
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
			$slugs = $query['slug__in'] ?? array();
			if ( in_array( $block_template->slug, $slugs ) ) {
				$query_result[] = $block_template;
			}

			// Admin: Show on list Templates.
			// Link preview https://drive.google.com/file/d/1Gi3LjCQMD731qKBLXTTR2hLi3qjemg6Q/view?usp=sharing
			if ( is_admin() ) {
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
				'slug'  => 'learnpress-category',
				'title' => __( 'LearnPress Global', 'learnpress' ),
				'icon'  => null,
			],
			[
				'slug'  => 'learnpress-course-elements',
				'title' => __( 'LearnPress Course Elements', 'learnpress' ),
				'icon'  => null,
			],
			[
				'slug'  => 'learnpress-legacy',
				'title' => __( 'LearnPress Legacy', 'learnpress' ),
				'icon'  => null,
			],
		];

		foreach ( $lp_block_categories as $block_category ) {
			array_unshift( $block_categories, $block_category );
		}

		return $block_categories;
	}

	public function get_edit_post_id() {
		$postIdEdit = '';
		if ( isset( $_REQUEST['postId'] ) && ! empty( $_REQUEST['postId'] ) ) {
			$postIdEdit = $_REQUEST['postId'];
		} elseif ( isset( $_REQUEST['post'] ) && ! empty( $_REQUEST['post'] ) ) {
			$postIdEdit = $_REQUEST['post'];
		} elseif ( isset( $_REQUEST['post_id'] ) && ! empty( $_REQUEST['post_id'] ) ) {
			$postIdEdit = $_REQUEST['post_id'];
		} elseif ( function_exists( 'get_the_ID' ) && get_the_ID() ) {
			$postIdEdit = get_the_ID();
		} elseif ( isset( $GLOBALS['post'] ) && ! empty( $GLOBALS['post']->ID ) ) {
			$postIdEdit = $GLOBALS['post']->ID;
		}
		if ( ! is_numeric( $postIdEdit ) ) {
			$template_post = get_page_by_path( $postIdEdit, OBJECT, 'wp_template' );
			if ( $template_post ) {
				$postIdEdit = $template_post->ID;
			}
		}

		return $postIdEdit;
	}
}
