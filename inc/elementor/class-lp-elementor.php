<?php

class LP_Elementor_Widgets {

	protected $instance = null;

	public function __construct() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/widgets_registered', array( $this, 'register_widgets' ), 10, 1 );
	}

	public function register_widgets( $widgets_manager ) {
		$widgets = array(
			'become-a-teacher' => 'LP_Elementor_Widget_Become_A_Teacher',
		);

		if ( ! empty( $widgets ) ) {
			foreach ( $widgets as $widget => $class ) {
				if ( ! class_exists( $class ) ) {
					$widget_path = LP_PLUGIN_PATH . '/inc/elementor/widgets/' . $widget . '.php';

					if ( file_exists( $widget_path ) ) {
						require_once $widget_path;
					}
				}

				$widgets_manager->register_widget_type( new $class() );
			}
		}
	}

	public function register_category( \Elementor\Elements_Manager $elements_manager ) {
		$elements_manager->add_category(
			'learnpress',
			array(
				'title' => esc_html__( 'LearnPress', 'learnpress' ),
				'icon'  => 'eicon-navigator',
			)
		);
	}

	public function instance() {
		if ( ! $this->instance ) {
			$this->instance = new self();
		}

		return $this->instance;
	}
}

LP_Elementor_Widgets::instance();
