<?php

/**
 * Class Singleton
 */

namespace LearnPress;

trait Singleton {
	private static $instance = null;
	final public static function getInstance() {
		if ( is_null( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	final private function __construct() {
		parent::__construct();
	}
}
