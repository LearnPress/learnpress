<?php

/**
 * Class LP_Settings
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LP_Settings' ) ) {
	return;
}

class LP_Settings {
	/**
	 * @var array
	 */
	protected $_options = array();

	/**
	 * @var string
	 */
	protected $_prefix = '';

	/**
	 * @var bool
	 */
	protected $_load_data = false;

	/**
	 * @var bool
	 */
	static protected $_instance = false;

	/**
	 * Constructor.
	 *
	 * @param array|mixed $data
	 * @param string $prefix
	 *
	 */
	protected function __construct( $data = false, $prefix = 'learn_press_' ) {

		$this->_prefix = $prefix;

		if ( false === $data ) {
			$this->_load_data = true;
			$this->_load_options();
		} else {
			settype( $data, 'array' );
			$this->_options = $data;
		}
		self::load_site_options();
		//add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {

		//LP_Background_Global::add( 'load-site-options', '', array( __CLASS__, 'load_site_options' ) );
		//LP_Settings::load_site_options();
	}

	/**
	 * @param string $group
	 * @param string $prefix
	 *
	 * @return LP_Settings
	 */
	public function get_group( $group, $prefix = '' ) {
		$options = ! empty( $this->_options[ $this->_prefix . $group ] ) ? $this->get( $this->_prefix . $group ) : array();

		return new LP_Settings( $options, $prefix );
	}

	/**
	 * Load options from database.
	 *
	 * @param bool $force
	 */
	protected function _load_options( $force = false ) {

		//$this->_options = wp_load_alloptions();

		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT option_name, option_value
			FROM {$wpdb->options}
			WHERE option_name LIKE %s
		", $wpdb->esc_like( $this->_prefix ) . '%' );

		if ( $options = $wpdb->get_results( $query ) ) {
			foreach ( $options as $option ) {
				$this->_options[ $option->option_name ] = LP_Helper::maybe_unserialize( $option->option_value );
			}
		}
	}

	/**
	 * Set new value for a key
	 *
	 * @param $name
	 * @param $value
	 */
	public function set( $name, $value ) {
		if ( $this->_prefix && strpos( $name, $this->_prefix ) === false ) {
			$name = $this->_prefix . $name;
		}
		$this->_set_option( $this->_options, $name, $value );
	}

	private function _set_option( &$obj, $var, $value ) {
		$var         = (array) explode( '.', $var );
		$current_var = array_shift( $var );
		if ( is_object( $obj ) ) {
			if ( isset( $obj->{$current_var} ) ) {
				$obj->{$current_var} = LP_Helper::maybe_unserialize( $obj->{$current_var} );
				if ( count( $var ) ) {
					$this->_set_option( $obj->{$current_var}, join( '.', $var ), $value );
				} else {
					$obj->{$current_var} = $value;
				}
			} else {
				$obj->{$current_var} = $value;
			}
		} else {
			if ( isset( $obj[ $current_var ] ) ) {
				$obj[ $current_var ] = LP_Helper::maybe_unserialize( $obj[ $current_var ] );
				if ( count( $var ) ) {
					$this->_set_option( $obj[ $current_var ], join( '.', $var ), $value );
				} else {
					$obj[ $current_var ] = $value;
				}
			} else {
				$obj[ $current_var ] = $value;
			}
		}
	}

	/**
	 * Get option recurse separated by DOT
	 *
	 * @param string $var
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function get( $var = null, $default = null ) {
		if ( ! $var ) {
			return $this->_options;
		}

		if ( $this->_prefix && strpos( $var, $this->_prefix ) === false ) {
			$var = $this->_prefix . $var;
		}
		$segs   = explode( '.', $var );
		$return = $this->_get_option( $this->_options, $var, $default );
		if ( $return == '' || is_null( $return ) ) {
			$return = $default;
		}

		return $return;
	}

	/**
	 * @param      $obj
	 * @param      $var
	 * @param null $default
	 *
	 * @return null
	 */
	public function _get_option( $obj, $var, $default = null ) {
		$var         = (array) explode( '.', $var );
		$current_var = array_shift( $var );
		if ( is_object( $obj ) ) {
			if ( isset( $obj->{$current_var} ) ) {
				$obj->{$current_var} = LP_Helper::maybe_unserialize( $obj->{$current_var} );
				if ( count( $var ) ) {
					return $this->_get_option( $obj->{$current_var}, join( '.', $var ), $default );
				} else {
					return $obj->{$current_var};
				}
			} else {
				return $default;
			}
		} else {
			if ( isset( $obj[ $current_var ] ) ) {
				$obj[ $current_var ] = LP_Helper::maybe_unserialize( $obj[ $current_var ] );
				if ( count( $var ) ) {
					return $this->_get_option( $obj[ $current_var ], join( '.', $var ), $default );
				} else {
					return $obj[ $current_var ];
				}
			} else {
				return $default;
			}
		}
	}

	public function update( $key, $value = '' ) {
		if ( func_num_args() == 1 ) {
			$value = $this->get( $key );
		}
		update_option( $this->_prefix . $key, $value );
		$this->refresh();
	}

	public function refresh() {
		if ( $this->_load_data ) {
			$this->_load_options( true );
		}

		return $this;
	}

	/**
	 * Update option with default prefix is learn_press_
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param string $prefix
	 */
	public static function update_option( $name, $value, $prefix = 'learn_press_' ) {
		update_option( "{$prefix}{$name}", $value );
	}

	/**
	 * Get option with default prefix is learn_press_
	 *
	 * @param string $name
	 * @param mixed $default
	 *
	 * @return mixed
	 * @since 3.2.8
	 * @editor tungnx
	 *
	 */
	public static function get_option( $name, $default = false ) {
		return get_option( "learn_press_{$name}", $default );
	}

	public function get_int( $key ) {
		$value = $this->get( $key );

		return intval( $value );
	}

	/**
	 * @return bool|LP_Settings
	 */
	public static function instance() {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Load all 'no' options from other plugins for caching purpose.
	 *
	 * @since 3.0.0
	 */
	public static function load_site_options() {
		static $loaded = false;

		if ( $loaded ) {
			return;
		}

		$options = array(
			'pmpro_updates',
			'pmpro_stripe_billingaddress',
			'pmpro_only_filter_pmpro_emails',
			'pmpro_email_member_notification',
			'pmpro_hideads',
			'pmpro_hideadslevels',
			'_bbp_enable_group_forums',
			'_bbp_theme_package_id',
			'_bbp_root_slug',
			'_bbp_include_root',
			'_bbp_forum_slug',
			'_bbp_topic_slug',
			'_bbp_show_on_root',
			'_bbp_topic_archive_slug',
			'_bbp_reply_slug',
			'_bbp_topic_tag_slug',
			'_bbp_allow_topic_tags',
			'_bbp_use_autoembed',
			'_bbp_user_slug',
			'_bbp_view_slug',
			'_bbp_search_slug',
			'_bbp_reply_archive_slug',
			'_bbp_user_favs_slug',
			'_bbp_user_subs_slug',
			'pmpro_nuclear_HTTPS',
			'pmpro_gateway',
			'pmpro_recaptcha',
			'pmpro_use_ssl',
			'_bbp_enable_favorites',
			'_bbp_enable_subscriptions',
			'_bbp_allow_search',
			'_bbp_use_wp_editor',
			'pmpro_hide_footer_link',
			'learn-press-flush-rewrite-rules',
			'_lp_tabs_data',
			'learn_press_permalinks'
		);
		global $wpdb;

		$format = array_fill( 0, sizeof( $options ), '%s' );
		$q      = $wpdb->prepare( "
			SELECT option_name, option_value 
			FROM $wpdb->options 
			WHERE 1
			AND option_name IN(" . join( ',', $format ) . ")
		", $options );

		$alloptions_db = $wpdb->get_results( $q, OBJECT_K );
		$notoptions    = wp_cache_get( 'notoptions', 'options' );

		foreach ( $options as $o_name ) {
			if ( ! empty( $alloptions_db[ $o_name ] ) ) {
				$o_value = LP_Helper::maybe_unserialize( $alloptions_db[ $o_name ]->option_value );
				wp_cache_set( $o_name, $o_value, 'options' );
			} else {
				if ( ! is_array( $notoptions ) ) {
					$notoptions = array();
				}
				$notoptions[ $o_name ] = '';
			}
		}

		wp_cache_set( 'notoptions', $notoptions, 'options' );
		$loaded = true;
	}

	/**
	 * Get settings endpoints for checkout page.
	 *
	 * @return array
	 * @since 3.0.0
	 *
	 */
	public function get_checkout_endpoints() {
		if ( false === ( $endpoints = LP_Object_Cache::get( 'checkout', 'learn-press-endpoints' ) ) ) {
			$defaults = array(
				'lp-order-received' => 'lp-order-received'
			);

			$endpoints = array();
			if ( $settings = LP()->settings->get( 'checkout_endpoints' ) ) {
				foreach ( $settings as $k => $v ) {
					$k               = preg_replace( '!_!', '-', $k );
					$endpoints[ $k ] = $v;
				}
			}

			foreach ( $defaults as $k => $v ) {
				if ( empty( $endpoints[ $k ] ) ) {
					$endpoints[ $k ] = $v;
				}
			}

			LP_Object_Cache::set( 'checkout', $endpoints, 'learn-press-endpoints' );
		}

		return apply_filters( 'learn-press/endpoints/checkout', $endpoints );
	}

	/**
	 * Get settings endpoints for profile page.
	 *
	 * @return array
	 * @since 3.0.0
	 *
	 */
	public function get_profile_endpoints() {
		if ( false === ( $endpoints = LP_Object_Cache::get( 'profile', 'learn-press-endpoints' ) ) ) {
			$defaults = array();

			$endpoints = array();
			if ( $settings = LP()->settings->get( 'profile_endpoints' ) ) {
				foreach ( $settings as $k => $v ) {
					$k               = preg_replace( '!_!', '-', $k );
					$endpoints[ $k ] = $v;
				}
			}

			foreach ( $defaults as $k => $v ) {
				if ( empty( $endpoints[ $k ] ) ) {
					$endpoints[ $k ] = $v;
				}
			}

			LP_Object_Cache::set( 'profile', $endpoints, 'learn-press-endpoints' );
		}

		return apply_filters( 'learn-press/endpoints/profile', $endpoints );
	}
}

if ( ! function_exists( 'lp_settings' ) ) {
	/**
	 * @return LP_Settings|null
	 */
	function lp_settings() {
		return LP_Settings::instance();
	}

	lp_settings();
}
