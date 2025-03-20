<?php
namespace LearnPress\Gutenberg\Blocks;

use WP_Block_Type;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
abstract class AbstractBlockType extends WP_Block_Type {
	public $namespace = 'learnpress';
	/**
	 * @var string path of the file run js - Field of LP.
	 */
	public $source_js = '';
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

	public function __construct( $args = [] ) {
		$this->name = $this->namespace . '/' . $this->name;
		parent::__construct( $this->name, $args );
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 * @param $content
	 * @param $block
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
