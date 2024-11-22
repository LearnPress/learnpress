<?php

use LearnPress\Helpers\Template;

/**
 * Abstract_Block_Layout class.
 *
 */
abstract class Abstract_Block_Layout extends Abstract_Block_Template {
	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_assets' ) );
		parent::__construct();
	}

	public function render_content_block_template( array $attributes, $inner_content = '' ) {
		$content = '';

		try {
			ob_start();
			$template = $this->path_template_render_default;
			Template::instance()->get_frontend_template( $template, compact( 'attributes', 'inner_content' ) );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}

	public function enqueue_block_assets() {
		wp_enqueue_script(
			'my-block-script',
			plugins_url( 'block.js', __FILE__ ),
			array( 'wp-blocks', 'wp-editor', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' )
		);
	}
}
