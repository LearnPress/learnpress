<?php

/**
 * Class Singleton
 */

namespace LearnPress\Helpers;

trait Singleton {
	private static $instance = null;
	final public static function instance(): self {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	final private function __construct() {
		$this->init();
	}

	abstract function init();
}
