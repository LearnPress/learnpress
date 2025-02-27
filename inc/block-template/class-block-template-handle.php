<?php

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Template;

/**
 * Class Block_Template_Handle
 *
 * Handle register, render block template
 */
class Block_Template_Handle {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Hooks handle block template
	 */
	protected function __construct() {
		add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
		add_filter( 'pre_get_block_file_template', array( $this, 'edit_block_file_template' ), 10, 3 );
		add_action( 'init', array( $this, 'register_tag_block' ) );
		// Register block category
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 10, 2 );
	}

	/**
	 * Register block, render content of block
	 *
	 * @return void
	 */
	public function register_tag_block() {
		$block_templates = Config::instance()->get( 'block-templates' );

		/**
		 * @var Abstract_Block_Template $block_template
		 */
		foreach ( $block_templates as $block_template ) {
			// Register script.
			wp_register_script(
				$block_template->name, // Block name
				$block_template->source_js, // Block script
				array( 'wp-blocks' ), // Dependencies
				uniqid() // Version
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
	 * Add blocks template
	 *
	 * @param array $query_result Array of template objects.
	 * @param array $query Optional. Arguments to retrieve templates.
	 * @param mixed $template_type wp_template or wp_template_part.
	 *
	 * @return array
	 */
	public function add_block_templates( array $query_result, array $query, $template_type ): array {
		if ( $template_type === 'wp_template_part' ) { // Template not Template part
			return $query_result;
		}

		$post_type          = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$lp_block_templates = Config::instance()->get( 'block-templates' );

		foreach ( $lp_block_templates as $block_template ) {
			$new = new $block_template();

			if ( ! in_array( $new->slug, [ 'archive-lp_course' ,'single-lp_course' ] ) ) {
				continue;
			}

			if ( $post_type &&
			isset( $new->post_types ) &&
			! in_array( $post_type, $new->post_types, true )
			) {
				continue;
			}

			// Get block template if custom - save on table posts.
			$block_custom = $this->is_custom_block_template( $template_type, $new->slug );
			if ( $block_custom ) {
				$new->is_custom = true;
				$new->source    = 'custom';
				if ( version_compare( get_bloginfo( 'version' ), '6.4-beta', '>=' ) ) {
					$new->content = traverse_and_serialize_blocks( parse_blocks( $block_custom->post_content ) );
				} else {
					$new->content = _inject_theme_attribute_in_block_template_content( $block_custom->post_content );
				}
			}

			$fits_slug_query = ! isset( $query['slug__in'] ) || in_array( $new->slug, $query['slug__in'], true );
			$fits_area_query = ! isset( $query['area'] ) || ( property_exists( $new, 'area' ) && $new->area === $query['area'] );
			$should_include  = $fits_slug_query && $fits_area_query;
			if ( $should_include ) {
				$query_result[] = $new;
			}
		}

		return $query_result;
	}

	/**
	 * Load template block when edit and save.
	 *
	 * @param WP_Block_Template|null $template
	 * @param string $id
	 * @param $template_type
	 *
	 * @return WP_Block_Template|null
	 */
	public function edit_block_file_template( WP_Block_Template $template = null, string $id, $template_type ) {
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

		$lp_category_single_course_block = array(
			'slug'  => 'lp-single-course-category',
			'title' => __( 'Category Single Course', 'learnpress' ),
			'icon'  => null,
		);

		$lp_category_archive_course_block = array(
			'slug'  => 'lp-archive-course-category',
			'title' => __( 'Category Archive Course', 'learnpress' ),
			'icon'  => null,
		);

		$lp_category_profile_block = array(
			'slug'  => 'lp-profile-category',
			'title' => __( 'Category Profile', 'learnpress' ),
			'icon'  => null,
		);

		$lp_category_instructor_block = array(
			'slug'  => 'lp-instructor-category',
			'title' => __( 'Category Instructor', 'learnpress' ),
			'icon'  => null,
		);

		array_unshift( $block_categories, $lp_category_archive_course_block );
		array_unshift( $block_categories, $lp_category_single_course_block );
		array_unshift( $block_categories, $lp_category_profile_block );
		array_unshift( $block_categories, $lp_category_instructor_block );
		array_unshift( $block_categories, $lp_category_block );

		return $block_categories;
	}
}

Block_Template_Handle::instance();
