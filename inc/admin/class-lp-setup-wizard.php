<?php

/**
 * Class LP_Setup_Wizard
 *
 * Class helper for displaying the Setup Wizard page.
 *
 * @since   3.0.0
 * @author  ThimPress
 * @package LearnPress/Classes
 */
class LP_Setup_Wizard {
	/**
	 * @var string
	 */
	protected $_base_url = 'index.php?page=lp-setup';

	/**
	 * LP_Setup_Wizard constructor.
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'setup_wizard' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

		$actions = array(
			'setup-create-pages' => 'create_pages',
			'get-price-format'   => 'get_price_format'
		);
		foreach ( $actions as $action => $callback ) {
			LP_Request::register_ajax( $action, array( $this, $callback ) );
		}
	}

	/**
	 * Create static pages action
	 */
	public function create_pages() {
		if ( ! wp_verify_nonce( sanitize_key( LP_Request::get_string( '_wpnonce', 'setup-create-pages' ) ) ) ) {
			die();
		}

		$settings = LP_Request::get( 'settings' );

		if ( ! empty( $settings ) ) {
			foreach ( $settings['pages'] as $page => $page_id ) {
				if ( empty( $page_id ) ) {
					$_REQUEST['settings']['pages'][ $page ] = $this->create_page( $page );
				}
			}
		}

		LP_Request::$ajax_shutdown = false;
	}

	/**
	 * Get sample format price
	 */
	public static function get_price_format() {
		LP_Setup_Wizard::instance()->save();
		LP()->settings()->refresh();
		echo learn_press_format_price( 1234.56, true );
		die();
	}

	/**
	 * Create page by type.
	 *
	 * @param string $page
	 *
	 * @return int|WP_Error
	 */
	public function create_page( $page ) {
		$page_titles = array(
			'courses_page_id'          => _x( 'LP Courses', 'static-page', 'learnpress' ),
			'profile_page_id'          => _x( 'LP Profile', 'static-page', 'learnpress' ),
			'checkout_page_id'         => _x( 'LP Checkout', 'static-page', 'learnpress' ),
			'become_a_teacher_page_id' => _x( 'LP Become Teacher', 'static-page', 'learnpress' ),
		);

		return wp_insert_post(
			array(
				'post_title'  => $page_titles[ $page ],
				'post_status' => 'publish',
				'post_type'   => 'page'
			)
		);
	}

	/**
	 * Add an empty menu item for validating page.
	 */
	public function admin_menu() {
		if ( 'lp-setup' !== LP_Request::get_string( 'page' ) || ! current_user_can( 'install_plugins' ) ) {
			return;
		}
		add_dashboard_page( '', '', 'manage_options', 'lp-setup', '' );
	}

	/**
	 * Display setup page a ignore anything else in the rest
	 */
	public function setup_wizard() {
		if ( 'lp-setup' !== LP_Request::get_string( 'page' ) || ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( 'finish' === LP_Request::get_string( 'step' ) ) {
			delete_option( 'learn_press_install' );

			LP_Install::create_options();
		}

		LP_Install::create_tables();

		$this->save();

		// Refresh new changes
		LP()->settings()->refresh();

		$assets = learn_press_admin_assets();

		@do_action( 'admin_enqueue_scripts' );

		// TODO: move handle enqueue style on class-lp-admin-assets.php
		wp_enqueue_style( 'buttons' );
		wp_enqueue_style( 'common' );
		wp_enqueue_style( 'forms' );
		wp_enqueue_style( 'themes' );
		wp_enqueue_style( 'dashboard' );
		wp_enqueue_style( 'widgets' );
		wp_enqueue_style( 'lp-admin', $assets->url( 'css/admin/admin.css' ) );
		wp_enqueue_style( 'lp-setup', $assets->url( 'css/admin/setup.css' ) );
		wp_enqueue_style( 'lp-select2', $assets->url( '../inc/libraries/meta-box/css/select2/select2.css' ) );
		// TODO: move handle enqueue style on class-lp-admin-assets.php

		learn_press_admin_view( 'setup/header' );
		learn_press_admin_view( 'setup/content', array( 'steps' => $this->get_steps() ) );
		learn_press_admin_view( 'setup/footer' );
		die();
	}

	public function save() {
		$step = LP_Request::get_string( 'lp-setup-step' );

//		if ( ! wp_verify_nonce( sanitize_key( LP_Request::get_string( 'lp-setup-nonce' ) ), 'lp-setup-step-' . $step ) ) {
//			return;
//		}

		$postdata = LP_Request::get_array( 'settings' );
		$steps    = array( 'payment', 'pages', 'currency', 'emails' );

		if ( ( 'yes' !== LP_Request::get( 'skip' ) ) && in_array( $step, $steps ) ) {

			if ( array_key_exists( 'paypal', $postdata ) ) {
				update_option( 'learn_press_paypal', $postdata['paypal'] );
			}

			if ( array_key_exists( 'currency', $postdata ) ) {
				foreach ( $postdata['currency'] as $k => $v ) {
					update_option( 'learn_press_' . $k, $v );
				}
			}

			if ( array_key_exists( 'pages', $postdata ) ) {
				foreach ( $postdata['pages'] as $k => $v ) {
					update_option( 'learn_press_' . $k, $v );
				}
			}

			if ( array_key_exists( 'emails', $postdata ) ) {

				if ( ! empty( $postdata['emails']['enable'] ) && ( $postdata['emails']['enable'] === 'yes' ) ) {

					if ( $emails = LP_Emails::instance()->emails ) {
						foreach ( $emails as $email ) {
							$response[ $email->id ] = $email->enable( true );
						}
					}

				}

			}
		}

		do_action( 'learn-press/setup-wizard/update-settings', $postdata, $step );
	}

	/**
	 * Return array of all steps are available when running setup wizard.
	 *
	 * @return array
	 */
	public function get_steps() {
		static $steps = false;
		if ( ! $steps ) {
			$steps = apply_filters(
				'learn-press/setup-wizard/steps',
				array(
					'welcome'  => array(
						'title'       => __( 'Welcome', 'learnpress' ),
						'callback'    => array( $this, 'step_welcome' ),
						'next_button' => __( 'Run setup wizard', 'learnpress' )
					),
					'currency' => array(
						'title'            => __( 'Currency', 'learnpress' ),
						'callback'         => array( $this, 'step_currency' ),
						'back_button'      => false,
						'skip_prev_button' => false
					),
					'pages'    => array(
						'title'    => __( 'Static Pages', 'learnpress' ),
						'callback' => array( $this, 'step_pages' ),
					),
					'payment'  => array(
						'title'    => __( 'Payment', 'learnpress' ),
						'callback' => array( $this, 'step_payment' )
					),
					'emails'   => array(
						'title'    => __( 'Emails', 'learnpress' ),
						'callback' => array( $this, 'step_emails' )
					),
					'finish'   => array(
						'title'    => __( 'Finish', 'learnpress' ),
						'callback' => array( $this, 'step_finish' )
					)
				)
			);
		}

		return $steps;
	}

	/**
	 * Get all keys of available steps.
	 *
	 * @return array
	 */
	public function get_step_keys() {
		return array_keys( $this->get_steps() );
	}

	/**
	 * Get key of current step.
	 *
	 * @param bool $key
	 *
	 * @return mixed|string
	 */
	public function get_current_step( $key = true ) {
		$current = LP_Request::get_string( 'step' );
		$steps   = $this->get_steps();

		if ( empty( $steps[ $current ] ) ) {
			$key_steps = array_keys( $steps );
			$current   = reset( $key_steps );
		}

		$step = $steps[ $current ];
		if ( empty( $step['slug'] ) ) {
			$step['slug'] = $current;
		}

		return $key ? $current : $step;
	}

	/**
	 * Return true if the first step is viewing.
	 *
	 * @return bool
	 */
	public function is_first_step() {
		$steps = $this->get_step_keys();

		return array_search( $this->get_current_step(), $steps ) === 0;
	}

	/**
	 * Return true if the last steo is viewing.
	 *
	 * @return bool
	 */
	public function is_last_step() {
		$steps = $this->get_step_keys();

		return array_search( $this->get_current_step(), $steps ) === sizeof( $steps ) - 1;
	}

	/**
	 * Get url of next step.
	 *
	 * @return string
	 */
	public function get_next_url() {
		$current = $this->get_current_step();
		$steps   = $this->get_step_keys();
		$at      = array_search( $current, $steps );
		if ( $at < sizeof( $steps ) - 1 ) {
			$at ++;
		}

		return add_query_arg( 'step', $steps[ $at ], admin_url( $this->_base_url ) );
	}

	/**
	 * Get url of prev step.
	 *
	 * @return string
	 */
	public function get_prev_url() {
		$current = $this->get_current_step();
		$steps   = $this->get_step_keys();
		$at      = array_search( $current, $steps );
		if ( $at > 0 ) {
			$at --;
		}

		return add_query_arg( 'step', $steps[ $at ], admin_url( $this->_base_url ) );
	}

	/**
	 * Get position number of a step.
	 *
	 * @param string $step - Optional.
	 *
	 * @return mixed
	 */
	public function get_step_position( $step = '' ) {
		if ( ! $step ) {
			$step = $this->get_current_step();
		}

		$steps = $this->get_step_keys();

		return array_search( $step, $steps );
	}

	public function get_payments() {
		return array(
			'paypal' => array(
				'name'     => __( 'Paypal', 'learnpress' ),
				'desc'     => __( 'Enter your Paypal email address to accept payment via Paypal.', 'learnpress' ),
				'icon'     => LP()->plugin_url( '/assets/images/paypal-2.png' ),
				'callback' => array( $this, 'setup_paypal' )
			)
		);
	}

	public function setup_paypal() {
		learn_press_admin_view( 'setup/setup-paypal' );
	}

	public function setup_stripe() {
		learn_press_admin_view( 'setup/setup-stripe' );
	}

	/**
	 * Welcome step content.
	 */
	public function step_welcome() {
		learn_press_admin_view( 'setup/steps/welcome' );
	}

	/**
	 * Currency step content.
	 */
	public function step_currency() {
		learn_press_admin_view( 'setup/steps/currency' );
	}

	public function step_pages() {
		learn_press_admin_view( 'setup/steps/pages' );
	}

	public function step_payment() {
		learn_press_admin_view( 'setup/steps/payment' );
	}

	public function step_emails() {
		learn_press_admin_view( 'setup/steps/emails' );
	}

	public function step_finish() {
		learn_press_admin_view( 'setup/steps/finish' );
	}

	public function scripts() {
	}

	/**
	 * Get singleton instance
	 *
	 * @return bool|LP_Setup_Wizard
	 */
	public static function instance() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}

// Init
return LP_Setup_Wizard::instance();
