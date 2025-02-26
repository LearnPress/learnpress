<?php

namespace LearnPress\Gutenberg;

use LearnPress\Gutenberg\Blocks\BlockAbstract;
use LearnPress\Gutenberg\Blocks\SingleCourse\BlockSingleCourseLegacy;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use WP_Block_Template;
use WP_Post;

/**
 * Class GutenbergHandleMain
 *
 * Handle register, render block template
 * @since 4.2.8 Convert from old class Block_Template_Handle
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
		// Set block template need to show on frontend
		add_filter( 'get_block_templates', array( $this, 'set_blocks_template_on_frontend' ), 10, 3 );
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
		$block_templates = Config::instance()->get( 'block-templates' );

		/**
		 * @var BlockAbstract|BlockSingleCourseLegacy $block_template
		 */
		foreach ( $block_templates as $block_template ) {
			// Register script to load on the Backend Edit.
			wp_register_script(
				$block_template->name, // Block name
				$block_template->source_js, // Block script
				array( 'wp-blocks' ), // Dependencies
				uniqid(), // Version,
				[ 'strategy' => 'async' ]
			);

			// Render content block template child of parent block
			if ( $block_template->inner_block ) {
				/**
				 * @see Block_Title_Course::render_content_inner_block_template
				 */
				register_block_type_from_metadata(
					$block_template->inner_block,
					[
						'render_callback' => [ $block_template, 'render_content_inner_block_template' ],
					]
				);
				continue;
			}

			// Render content block template parent
			register_block_type(
				$block_template->name,
				[
					'render_callback' => [ $block_template, 'render_content_block_template' ],
					'editor_script'   => $block_template->name,
				]
			);
		}
	}

	/**
	 * Set block template need to show on frontend
	 * 1. Get all block of LP declare.
	 * 2. Check blocks match with type page. Ex: single course, archive course....
	 * 3. If correct, set Block to $query_result.
	 *
	 * @param array $query_result Array of template objects.
	 * @param array $query Optional. Arguments to retrieve templates.
	 * @param mixed $template_type wp_template or wp_template_part.
	 *
	 * @return array
	 */
	public function set_blocks_template_on_frontend( array $query_result, array $query, $template_type ): array {
		if ( $template_type === 'wp_template_part' ) { // Template not Template part
			return $query_result;
		}

		$lp_block_templates = Config::instance()->get( 'block-templates' );

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

			if ( empty( $query ) ) { // For Admin and rest api call to this function, so $query is empty
				$query_result[] = $block_template;
			} else {
				// Check block template match with slug in query will show on frontend
				$slugs = $query['slug__in'] ?? array();
				if ( in_array( $block_template->slug, $slugs ) ) {
					$query_result[] = $block_template;
				}
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
		$lp_block_templates = Config::instance()->get( 'block-templates' );

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
		$lp_category_block = array(
			'slug'  => 'learnpress-category',
			'title' => __( 'LearnPress Category', 'learnpress' ),
			'icon'  => null,
		);

		array_unshift( $block_categories, $lp_category_block );

		return $block_categories;
	}
}
