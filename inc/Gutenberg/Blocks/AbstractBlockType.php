<?php
namespace LearnPress\Gutenberg\Blocks;

use WP_Block_Type;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
abstract class AbstractBlockType extends WP_Block_Type {
	public $namespace   = 'learnpress';
	public $textdomain  = 'learnpress';
	public $api_version = 3;
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

	public function __construct() {
		$this->name = $this->namespace . '/' . $this->name;
		parent::__construct( $this->name );

		$this->editor_script_handles = $this->get_editor_script_handles();
		$this->render_callback       = $this->get_render_callback();
		$this->supports              = $this->get_supports();
		$this->attributes            = $this->get_attributes();
		$this->ancestor              = $this->get_ancestor();
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
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 * @param $content
	 * @param \WP_Block $block
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

	protected function get_class_hash() {
		$hash  = bin2hex( random_bytes( 16 ) );
		$class = 'lp-elements-' . $hash;
		return $class;
	}
}
