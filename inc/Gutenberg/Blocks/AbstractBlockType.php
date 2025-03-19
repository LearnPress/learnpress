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
}
