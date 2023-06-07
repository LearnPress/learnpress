<?php
namespace LearnPress\ExternalPlugin\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
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
		wp_enqueue_style( 'learnpress', LP_PLUGIN_URL . 'assets/css/learnpress.css', array(), uniqid() );
		wp_enqueue_style( 'learnpress-widgets', LP_PLUGIN_URL . 'assets/css/widgets.css' );

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
		$keywords = $this->keywords;

		return array_push( $keywords, 'learnpress' );
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
		$this->print_fields( $this->controls );
	}

	/**
	 * Register controls.
	 *
	 * @param $fields
	 *
	 * @return void
	 */
	protected function print_fields( $fields ) {
		foreach ( $fields as $id => $field ) {
			if ( isset( $field['method'] ) && is_callable( [ $this, $field['method'] ] ) && is_array( $field ) ) {
				$params = $field;
				unset( $params['method'] );
				call_user_func_array( [ $this, $field['method'] ], $params );
			}
		}
	}
}
