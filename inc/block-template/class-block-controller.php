<?php

class LP_Block_Template_Controller {

	protected static $instance = null;

	/**
	 * Directory name of the block template directory.
	 *
	 * @var string
	 */
	const TEMPLATES_DIR_NAME = 'block-templates';

	/**
	 * Directory name of the block template parts directory.
	 *
	 * @var string
	 */
	const TEMPLATE_PARTS_DIR_NAME = 'block-template-parts';

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'render_block_template' ) );
		add_filter( 'get_block_templates', array( $this, 'add_block_templates' ), 10, 3 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor' ) );
		add_action( 'init', array( $this, 'register_block_template_post_type' ) );
	}

	public function block_editor() {
		$wp_js = array(
			'wp-block-editor',
			'wp-blocks',
			'wp-components',
			'wp-element',
			'wp-i18n',
			'wp-primitives',
			'lodash',
		);

		wp_enqueue_script( 'learnpress-gutenberg-editor', LP_PLUGIN_URL . 'assets/js/dist/blocks/index.js', $wp_js, LEARNPRESS_VERSION, true );
	}

	public function register_block_template_post_type() {
		register_block_type(
			'learnpress/template',
			array(
				'render_callback' => array( $this, 'render_content_block_template' ),
			)
		);
	}

	public function render_content_block_template( $attributes ) {
		$templates = array( 'archive-course', 'single-course' );

		if ( in_array( $attributes['template'], $templates, true ) ) {
			return learn_press_get_template_content( $attributes['template'], array( 'is_block_theme' => true ) );
		} else {
			ob_start();

			echo "You're using the Template block";

			wp_reset_postdata();
			return ob_get_clean();
		}
	}

	public function render_block_template() {
		if ( is_embed() || ! LP_Block_Template_Utils::instance()->supports_block_templates() ) {
			return;
		}

		if ( is_singular( LP_COURSE_CPT ) && ! LP_Block_Template_Utils::instance()->theme_has_template( 'single-lp_course' ) && $this->block_template_is_available( 'single-lp_course' ) ) {
			add_filter( 'learnpress_has_block_template', '__return_true', 10, 0 );
		} elseif ( ( is_post_type_archive( LP_COURSE_CPT ) || ( ! empty( learn_press_get_page_id( 'courses' ) ) && is_page( learn_press_get_page_id( 'courses' ) ) ) ) && ! LP_Block_Template_Utils::instance()->theme_has_template( 'archive-lp_course' ) && $this->block_template_is_available( 'archive-lp_course' ) ) {
			add_filter( 'learnpress_has_block_template', '__return_true', 10, 0 );
		}
	}

	public function add_block_templates( $query_result, $query, $template_type ) {
		if ( ! LP_Block_Template_Utils::instance()->supports_block_templates() ) {
			return $query_result;
		}

		$post_type      = isset( $query['post_type'] ) ? $query['post_type'] : '';
		$slugs          = isset( $query['slug__in'] ) ? $query['slug__in'] : array();
		$template_files = $this->get_block_templates( $slugs, $template_type );

		// @todo: Add apply_filters to _gutenberg_get_template_files() in Gutenberg to prevent duplication of logic.
		foreach ( $template_files as $template_file ) {

			// Avoid adding the same template if it's already in the array of $query_result.
			if (
				array_filter(
					$query_result,
					function( $query_result_template ) use ( $template_file ) {
						return $query_result_template->slug === $template_file->slug &&
								$query_result_template->theme === $template_file->theme;
					}
				)
			) {
				continue;
			}

			// If the current $post_type is set (e.g. on an Edit Post screen), and isn't included in the available post_types
			// on the template file, then lets skip it so that it doesn't get added. This is typically used to hide templates
			// in the template dropdown on the Edit Post page.
			if ( $post_type &&
				isset( $template_file->post_types ) &&
				! in_array( $post_type, $template_file->post_types, true )
			) {
				continue;
			}

			// It would be custom if the template was modified in the editor, so if it's not custom we can load it from
			// the filesystem.
			if ( 'custom' !== $template_file->source ) {
				$template = LP_Block_Template_Utils::instance()->gutenberg_build_template_result_from_file( $template_file, $template_type );
			} else {
				$template_file->title = ! LP_Block_Template_Utils::instance()->convert_slug_to_title( $template_file->slug );
				$query_result[]       = $template_file;
				continue;
			}

			$is_not_custom   = false === array_search(
				wp_get_theme()->get_stylesheet() . '//' . $template_file->slug,
				array_column( $query_result, 'id' ),
				true
			);
			$fits_slug_query = ! isset( $query['slug__in'] ) || in_array( $template_file->slug, $query['slug__in'], true );
			$fits_area_query = ! isset( $query['area'] ) || $template_file->area === $query['area'];
			$should_include  = $is_not_custom && $fits_slug_query && $fits_area_query;

			if ( $should_include ) {
				$query_result[] = $template;
			}
		}

		$query_result = $this->remove_theme_templates_with_custom_alternative( $query_result );

		return $query_result;
	}

	public function get_block_templates( $slugs = array(), $template_type = 'wp_template' ) {
		$templates_from_db = $this->get_block_templates_from_db( $slugs, $template_type );
		$templates_from_lp = $this->get_block_templates_from_learnpress( $slugs, $templates_from_db, $template_type );

		return array_merge( $templates_from_db, $templates_from_lp );
	}

	public function get_block_templates_from_learnpress( $slugs, $already_found_templates, $template_type = 'wp_template' ) {
		global $wp;

		$directory      = $this->get_templates_directory( $template_type );
		$template_files = LP_Block_Template_Utils::instance()->gutenberg_get_template_paths( $directory );
		$templates      = array();

		if ( 'wp_template_part' === $template_type ) {
			$dir_name = self::TEMPLATE_PARTS_DIR_NAME;
		} else {
			$dir_name = self::TEMPLATES_DIR_NAME;
		}

		foreach ( $template_files as $template_file ) {
			$template_slug = LP_Block_Template_Utils::instance()->generate_template_slug_from_path( $template_file, $dir_name );

			if ( ! empty( $wp->query_vars['course-item'] ) && $template_slug === 'single-lp_course' ) {
				$template_slug = '';
			}
			// This template does not have a slug we're looking for. Skip it.
			if ( is_array( $slugs ) && count( $slugs ) > 0 && ! in_array( $template_slug, $slugs, true ) ) {
				continue;
			}

			// If the theme already has a template, or the template is already in the list (i.e. it came from the
			// database) then we should not overwrite it with the one from the filesystem.
			if (
				LP_Block_Template_Utils::instance()->theme_has_template( $template_slug ) ||
				count(
					array_filter(
						$already_found_templates,
						function ( $template ) use ( $template_slug ) {
							$template_obj = (object) $template; //phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found
							return $template_obj->slug === $template_slug;
						}
					)
				) > 0 ) {
				continue;
			}

			// If the theme has an archive-product.html template, but not a taxonomy-product_cat.html template let's use the themes archive-product.html template.
			if ( 'taxonomy-lp_course_cat' === $template_slug && ! LP_Block_Template_Utils::instance()->theme_has_template( 'taxonomy-lp_course_cat' ) && LP_Block_Template_Utils::instance()->theme_has_template( 'archive-lp_course' ) ) {
				$template_file = get_stylesheet_directory() . '/' . self::TEMPLATES_DIR_NAME . '/archive-course.html';
				$templates[]   = LP_Block_Template_Utils::instance()->create_new_block_template_object( $template_file, $template_type, $template_slug, true );
				continue;
			}

			// If the theme has an archive-product.html template, but not a taxonomy-product_tag.html template let's use the themes archive-product.html template.
			if ( 'taxonomy-lp_course_tag' === $template_slug && ! LP_Block_Template_Utils::instance()->theme_has_template( 'taxonomy-lp_course_tag' ) && LP_Block_Template_Utils::instance()->theme_has_template( 'archive-lp_course' ) ) {
				$template_file = get_stylesheet_directory() . '/' . self::TEMPLATES_DIR_NAME . '/archive-course.html';
				$templates[]   = LP_Block_Template_Utils::instance()->create_new_block_template_object( $template_file, $template_type, $template_slug, true );
				continue;
			}

			// At this point the template only exists in the Blocks filesystem and has not been saved in the DB,
			// or superseded by the theme.
			$templates[] = LP_Block_Template_Utils::instance()->create_new_block_template_object( $template_file, $template_type, $template_slug );
		}

		return $templates;
	}

	public function get_block_templates_from_db( $slugs = array(), $template_type = 'wp_template' ) {
		$invalid_plugin_slug = 'learnpress';

		$check_query_args = array(
			'post_type'      => $template_type,
			'posts_per_page' => -1,
			'no_found_rows'  => true,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => array( $invalid_plugin_slug, LP_Block_Template_Utils::PLUGIN_SLUG, get_stylesheet() ),
				),
			),
		);

		if ( is_array( $slugs ) && count( $slugs ) > 0 ) {
			$check_query_args['post_name__in'] = $slugs;
		}

		$check_query         = new \WP_Query( $check_query_args );
		$saved_woo_templates = $check_query->posts;

		return array_map(
			function( $saved_woo_template ) {
				return LP_Block_Template_Utils::instance()->gutenberg_build_template_result_from_post( $saved_woo_template );
			},
			$saved_woo_templates
		);
	}

	public function remove_theme_templates_with_custom_alternative( $templates ) {

		// Get the slugs of all templates that have been customised and saved in the database.
		$customised_template_slugs = array_map(
			function( $template ) {
				return $template->slug;
			},
			array_values(
				array_filter(
					$templates,
					function( $template ) {
						// This template has been customised and saved as a post.
						return 'custom' === $template->source;
					}
				)
			)
		);

		return array_values(
			array_filter(
				$templates,
				function( $template ) use ( $customised_template_slugs ) {
					// This template has been customised and saved as a post, so return it.
					return ! ( 'theme' === $template->source && in_array( $template->slug, $customised_template_slugs, true ) );
				}
			)
		);
	}

	public function block_template_is_available( $template_name, $template_type = 'wp_template' ) {
		if ( ! $template_name ) {
			return false;
		}

		$directory = $this->get_templates_directory( $template_type ) . '/' . $template_name . '.html';

		return is_readable( $directory ) || $this->get_block_templates( array( $template_name ), $template_type );
	}

	protected function get_templates_directory( $template_type = 'wp_template' ) {
		if ( 'wp_template_part' === $template_type ) {
			return LP_PLUGIN_PATH . self::TEMPLATE_PARTS_DIR_NAME;
		}
		return LP_PLUGIN_PATH . self::TEMPLATES_DIR_NAME;
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

LP_Block_Template_Controller::instance();
