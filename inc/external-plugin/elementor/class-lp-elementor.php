<?php

/**
 * Class LP_Elementor_Widgets
 *
 * @author Nhamdv
 * @since 4.1.6
 * @version 1.0.0
 */
class LP_Elementor_Widgets {
	/**
	 * @var LP_Elementor_Widgets
	 */
	protected static $instance = null;
	/**
	 * @var array
	 */
	public static $widgets = [];

	/**
	 * Construct
	 */
	public function __construct() {
		self::$widgets = include_once 'lp-elementor-widgets-config.php';
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ), 10, 1 );
		add_action( 'elementor/frontend/before_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
	}

	/**
	 * Register category LearnPress
	 *
	 * @param Elementor\Elements_Manager $elements_manager
	 *
	 * @return void
	 */
	public function register_category( Elementor\Elements_Manager $elements_manager ) {
		$elements_manager->add_category(
			'learnpress',
			array(
				'title' => esc_html__( 'LearnPress', 'learnpress' ),
				'icon'  => 'eicon-navigator',
			)
		);
	}

	public function register_widgets( $widgets_manager ) {
		if ( ! empty( self::$widgets ) ) {

			// Abstract class for widgets.
			require_once LP_PLUGIN_PATH . 'inc/external-plugin/elementor/widgets/widget-base.php';

			foreach ( self::$widgets as $widget => $class ) {
				if ( ! class_exists( $class ) ) {
					$widget_path = LP_PLUGIN_PATH . 'inc/external-plugin/elementor/widgets/' . $widget . '.php';

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

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

LP_Elementor_Widgets::instance();
