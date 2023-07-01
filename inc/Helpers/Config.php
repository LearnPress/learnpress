<?php

namespace LearnPress\Helpers;

/**
 * Class Config
 * Read data config from file
 *
 * @package LP\Helpers
 * @since 4.1.6.4
 * @version 1.0.0
 */
class Config {
	/*
	 * All of configurations of items
	 *
	 * @var array
	 */
	protected static $instance;
	protected $file_name = '';
	protected $items     = array();
	/**
	 * @var array Array name files config.
	 */
	protected $config_files = array();
	/**
	 * @var string Folder store files config
	 */
	protected $dir;

	/**
	 * Config constructor.
	 *
	 * @param array $items
	 */
	protected function __construct( array $items = array() ) {
		$this->dir = LP_PLUGIN_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR;

		$this->items = $items;
	}

	/**
	 * Get the specified configuration value
	 *
	 * @param string $key | Format key: file_name:key_name:key_item:...
	 * @param string $path | from folder 'config'
	 *
	 * @return array|mixed
	 * @version 1.0.0
	 * @since 4.1.6.4
	 */
	public function get( string $key = '', string $path = '', array $args = [] ) {
		extract( $args );
		$data_config        = array();
		$data_config_by_key = array();

		if ( empty( $key ) ) {
			return $data_config;
		}

		$keys      = explode( ':', $key );
		$file_name = $keys[0];
		$file_path = $this->dir . $path . DIRECTORY_SEPARATOR . $file_name . '.php';

		if ( ! file_exists( $file_path ) ) {
			return $data_config;
		}

		$store_file_config = $this->config_files[ $file_name ] ?? array();
		if ( empty( $store_file_config ) ) {
			$this->config_files[ $file_name ] = include $file_path;
		}

		$data_config = $this->config_files[ $file_name ];

		$number_keys = count( $keys );

		if ( 1 === $number_keys ) {
			return $data_config;
		} else {
			$data_config_by_key = $data_config;
			for ( $i = 1; $i < $number_keys; $i ++ ) {
				$data_config_by_key = $data_config_by_key[ $keys[ $i ] ] ?? array();
			}

			return $data_config_by_key;
		}
	}

	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
