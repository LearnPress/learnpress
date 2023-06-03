<?php

namespace LearnPress\Shortcodes;

use LearnPress\Helpers\Singleton;

/**
 * Class AbstractShortcode
 *
 * @package LearnPress\Shortcodes
 * @since 4.2.3
 * @version 1.0.0
 */
abstract class AbstractShortcode {
	use Singleton;
	protected $prefix = 'learn_press_';
	protected $shortcode_name;

	public function init() {
		// Register shortcode.
		add_shortcode( $this->prefix . $this->shortcode_name, array( $this, 'render' ) );
	}

	/**
	 * Render template of shortcode.
	 * If not set any atrribute on short, $attrs is empty string.
	 *
	 * @param string|array $attrs
	 *
	 * @return string
	 */
	abstract public function render( $attrs ): string;
}

