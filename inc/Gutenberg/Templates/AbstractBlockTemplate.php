<?php

namespace LearnPress\Gutenberg\Templates;

use LearnPress\Helpers\Template;
use WP_Block_Template;

/**
 * Abstract_Block_Template class.
 *
 * @since 4.2.8 Convert from old class Abstract_Block_Template
 * @version 1.0.1
 */
abstract class AbstractBlockTemplate extends WP_Block_Template {
	public $theme = 'learnpress/learnpress';
	public $type  = 'wp_template';
	/**
	 * @var string name of the block
	 */
	public $origin                        = 'plugin';
	public $source                        = 'plugin'; // plugin|custom|theme, if custom save on db will be use 'custom'.
	public $content                       = ''; // Set content will be shown on edit block and the frontend.
	public $has_theme_file                = true;
	public $is_custom                     = false;
	public $path_html_block_template_file = '';

	public function __construct() {
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
}
