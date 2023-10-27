<?php
namespace LearnPress\ExternalPlugin\Elementor;

use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Throwable;
use function PHPUnit\Framework\callback;

class LPElementorWidgetBase extends Widget_Base {
	/**
	 * @var string $title
	 */
	public $title = '';
	/**
	 * @var string $prefix_name;
	 */
	private $prefix_name = 'learnpress_';
	/**
	 * @var string $name;
	 */
	public $name = '';
	/**
	 * @var string $icon
	 */
	public $icon;
	/**
	 * @var string[] key search widget
	 */
	public $keywords = array();
	/**
	 * @var array Controls
	 */
	public $controls = array();

	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
	}


	public function get_title() {
		return $this->title;
	}

	public function get_name() {
		return $this->prefix_name . $this->name;
	}

	public function get_icon() {
		return $this->icon ?? 'eicon-site-logo';
	}

	public function get_keywords() {
		return array_push( $this->keywords, 'learnpress' );
	}

	public function get_categories() {
		return array( 'learnpress' );
	}

	public function get_help_url() {
		return '';
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		if ( ! is_array( $this->controls ) ) {
			error_log( __METHOD__ . ': ' . json_encode( $this->controls ) );
			return;
		}
		$this->print_fields( $this->controls );
	}

	/**
	 * Register controls.
	 *
	 * @param array $fields
	 *
	 * @since 4.2.3
	 * @version 1.0.0
	 * @return void
	 */
	protected function print_fields( array $fields ) {
		try {
			foreach ( $fields as $id => $field ) {
				if ( isset( $field['method'] ) && is_callable( [ $this, $field['method'] ] ) && is_array( $field ) ) {
					$params = [];
					foreach ( $field as $key => $value ) {
						if ( 'method' === $key ) {
							continue;
						}
						$params[] = $value;
					}

					// Register control type Repeater
					if ( isset( $params[1]['type'] ) && Controls_Manager::REPEATER === $params[1]['type'] ) {
						$repeater = new Repeater();

						foreach ( $params[1]['fields'] as $key => $value ) {
							// For call method add_responsive_control, and another method
							if ( isset( $value['method'] ) ) {
								$prms = $value;
								unset( $prms['method'] );

								if ( 'add_group_control' === $value['method'] ) {
									$args = [ $value['type'] ?? '', $prms ];
								} else {
									unset( $prms['name'] );
									$args = [ $value['name'] ?? '', $prms ];
								}

								call_user_func_array( [ $repeater, $value['method'] ], $args );
							} else {
								$repeater->add_control( $value['name'], $value );
							}
						}

						$params[1]['fields']      = $repeater->get_controls();
						$params[1]['title_field'] = $params[1]['title_field'] ?? '';
						$this->add_control(
							$params[0], // string id of control
							$params[1] // array args of control
						);
					} else {
						call_user_func_array( [ $this, $field['method'] ], $params );
					}
				}
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
