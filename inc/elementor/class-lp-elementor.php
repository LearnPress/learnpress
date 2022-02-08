<?php

class LP_Elementor_Widgets {

	protected static $instance = null;

	const WIDGETS = array(
		'become-a-teacher' => 'LP_Elementor_Widget_Become_A_Teacher',
		'login-form'       => 'LP_Elementor_Widget_Login_Form',
	);

	public function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ), 10, 1 );
		add_action( 'elementor/frontend/before_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	public function register_widgets( $widgets_manager ) {
		if ( ! empty( self::WIDGETS ) ) {

			// Abstract class for widgets.
			require_once LP_PLUGIN_PATH . 'inc/elementor/widgets/widget-base.php';

			foreach ( self::WIDGETS as $widget => $class ) {
				$class = sprintf( '\Elementor\%s', $class );

				if ( ! class_exists( $class ) ) {
					$widget_path = LP_PLUGIN_PATH . 'inc/elementor/widgets/' . $widget . '.php';

					if ( file_exists( $widget_path ) ) {
						require_once $widget_path;
					}
				}

				if ( class_exists( $class ) ) {
					$widgets_manager->register_widget_type( new $class() );
				}
			}
		}
	}

	public function enqueue_frontend_scripts() {
		if ( ! wp_style_is( 'lp-font-awesome-5' ) ) {
			wp_enqueue_style( 'lp-font-awesome-5', LP_PLUGIN_URL . 'src/css/vendor/font-awesome-5.min.css', array(), array() );
		}

		if ( ! wp_style_is( 'learnpress' ) ) {
			wp_enqueue_style( 'learnpress', LP_PLUGIN_URL . 'css/learnpress.css', array(), array() );
		}

		if ( ! wp_script_is( 'lp-utils' ) ) {
			wp_enqueue_script( 'lp-utils', LP_PLUGIN_URL . 'js/dist/utils.js', array( 'jquery' ), LEARNPRESS_VERSION );
		}

		if ( ! wp_script_is( 'lp-become-a-teacher' ) ) {
			wp_enqueue_script( 'lp-become-a-teacher', LP_PLUGIN_URL . 'src/js/frontend/become-teacher.js', array( 'jquery' ), LEARNPRESS_VERSION, true );
		}
	}

	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'learnpress',
			array(
				'title' => esc_html__( 'LearnPress', 'learnpress' ),
				'icon'  => 'eicon-navigator',
			)
		);
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

LP_Elementor_Widgets::instance();
