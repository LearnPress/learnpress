<?php

/**
 * Class Block_Template_Item_Example
 *
 * Handle register, render block template
 */
class Block_Template_Item_Example extends Abstract_Block_Template {
	public $slug                          = 'item-example';
	public $name                          = 'learnpress/item-example';
	public $title                         = 'Item example (LearnPress)';
	public $description                   = 'Item Example Block Template';
	public $path_html_block_template_file = 'html/item-example.html';
	public $path_template_render_default  = 'content-single-item.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/item-example.js';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes ) {
		return parent::render_content_block_template( $attributes );
	}
}