<?php
namespace LearnPress\Gutenberg\Blocks;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use WP_Block;
use WP_Block_Type;

/**
 * Class AbstractBlockType
 *
 * Handle register, render block template
 */
abstract class AbstractBlockType extends WP_Block_Type {
	public $namespace   = 'learnpress';
	public $textdomain  = 'learnpress';
	public $api_version = 3;
	/**
	 * @var string block name - Field of LP.
	 */
	public $block_name = '';
	/**
	 * @var string path of the file run js - Field of LP.
	 */
	public $source_js = '';
	/**
	 * Metadata of block.
	 * @var string path of the file block.js - Field of LP.
	 */
	public $path_block_json = '';
	/**
	 * @var string Screen Template want to display - Field of LP
	 */
	public $display_on_templates = [];
	/**
	 * Tracks if assets have been enqueued.
	 *
	 * @var boolean
	 */
	protected $enqueued_assets = false;
	/**
	 * @var string Class hash - Field of LP
	 */
	protected $class_hash = '';

	public function __construct() {
		$this->source_js = $this->get_source_js();
		parent::__construct( $this->get_block_type() );
		$this->editor_script_handles = $this->get_editor_script_handles();
		$this->render_callback       = $this->get_render_callback();
		$this->supports              = $this->get_supports();
		$this->attributes            = $this->get_attributes();
		$this->ancestor              = $this->get_ancestor();
		$this->provides_context      = $this->get_provides_context();
	}

	/**
	 * Set to name block.
	 * Or set path js handle of block.
	 * For Backend
	 *
	 * @return string[]
	 */
	protected function get_editor_script_handles(): array {
		return [ $this->name ];
	}

	/**
	 * Set render callback for block.
	 *
	 * @return array
	 */
	protected function get_render_callback(): array {
		return [ $this, 'render_content_block_template' ];
	}

	/**
	 * Get supports.
	 *
	 * @return array|null
	 */
	protected function get_supports() {
		return null;
	}

	/**
	 * Get supports.
	 *
	 * @return array|null
	 */
	protected function get_ancestor() {
		return null;
	}

	/**
	 * Get supports.
	 *
	 * @return array|null
	 */
	protected function get_provides_context() {
		return null;
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 * @param string $content
	 * @param WP_Block $block
	 *
	 * @return string
	 */
	abstract public function render_content_block_template( array $attributes, $content, $block ): string;

	protected function enqueue_assets() {
		if ( $this->enqueued_assets ) {
			return;
		}
		wp_enqueue_style( 'lp-blocks-style', get_stylesheet_uri() );
		$this->enqueued_assets = true;
	}

	protected function get_block_type() {
		return $this->namespace . '/' . $this->block_name;
	}

	protected function get_source_js() {
		return LP_PLUGIN_URL . 'assets/js/dist/blocks/' . $this->block_name . '.js';
	}

	protected function get_class_hash() {
		$hash             = bin2hex( random_bytes( 16 ) );
		$timestamp        = time();
		$this->class_hash = 'lp-elements-' . $timestamp . '-' . $hash;
	}

	/**
	 * Wrap content in a block tag.
	 * Will get the attributes, supports, classes... from the block.
	 * The block's supports must be configured in both block.json and PHP to be consistent.
	 * If method generate not provide enough to handle special logic, you can override this method.
	 *
	 * @param string $content
	 * @param string $tag
	 * @param array $extra_attributes
	 *
	 * @return string
	 */
	protected function get_output( string $content, string $tag = 'div', array $extra_attributes = [] ): string {
		$wrapper = get_block_wrapper_attributes( $extra_attributes );
		return sprintf(
			"<$tag %s>%s</$tag>",
			$wrapper,
			$content
		);
	}

	protected function get_output_with_class_hash( $attributes, $content, $properties = array(), $exclude = array() ) {
		$output                    = '';
		$class_hash                = $this->class_hash ?? '';
		$classes                   = $attributes['className'] ?? '';
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, $properties, $exclude );
		$class_default             = 'wp-block-' . $this->namespace . '-' . $this->block_name;
		$class                     = $class_default ? $class_default : '';
		$style                     = '';

		if ( ! empty( $classes ) ) {
			$class .= ' ' . $classes;
		}

		if ( ! empty( $class_hash ) ) {
			$class .= ' ' . $class_hash;
		}

		if ( ! empty( $border_classes_and_styles['classes'] ) ) {
			$class .= ' ' . $border_classes_and_styles['classes'];
		}

		if ( ! empty( $border_classes_and_styles['classes'] ) ) {
			$style = sprintf( 'style="%s"', $border_classes_and_styles['styles'] );
		}

		ob_start();
		echo sprintf(
			'<div class="%s" %s>%s</div>',
			$class,
			$style,
			$content
		);
		$output = ob_get_clean();
		return $output;
	}
}
