<?php

use LearnPress\Helpers\Template;

/**
 * Abstract_Block_Template class.
 *
 */
abstract class Abstract_Block_Template extends \WP_Block_Template {
	public $theme = 'learnpress/learnpress';
	public $type  = 'wp_template';
	/**
	 * @var string name of the block
	 */
	public $name                          = '';
	public $origin                        = 'plugin';
	public $source                        = 'plugin'; // plugin|custom|theme, if custom save on db will be use 'custom'.
	public $content                       = ''; // Set content will be show on edit block and the frontend.
	public $has_theme_file                = true;
	public $is_custom                     = false;
	public $path_html_block_template_file = '';
	public $path_template_render_default  = '';
	/**
	 * @var string path of the file run js.
	 */
	public $source_js = '';
	/**
	 * @var bool|string path of the file block.json metadata.
	 */
	public $inner_block = false;

	public function __construct() {
		if ( ! wp_is_block_theme() ) {
			return;
		}
		$this->id      = $this->theme . '//' . $this->slug;
		$template_file = '';

		if ( ! empty( $this->path_html_block_template_file ) ) {
			$template_file = Template::instance( false )->get_frontend_template_type_block(
				$this->path_html_block_template_file
			);
		}

		// Set content from theme file.
		if ( file_exists( $template_file ) ) {
			$content = file_get_contents( $template_file );
			if ( version_compare( get_bloginfo( 'version' ), '6.4-beta', '>=' ) ) {
				$this->content = traverse_and_serialize_blocks( parse_blocks( $content ) );
			} else {
				$this->content = _inject_theme_attribute_in_block_template_content( $content );
			}
		}
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			ob_start();
			$template = $attributes['template'] ?? $this->path_template_render_default;
			Template::instance()->get_frontend_template( $template, compact( 'attributes' ) );

			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}
