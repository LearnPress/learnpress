<?php
/**
 * Offline payment gateway class.
 *
 * @author   ThimPress
 * @package  LearnPress/Offline-Payment/Classes
 * @version  4.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'LP_Gateway_Offline_Payment' ) ) {
	/**
	 * Class LP_Gateway_Offline_Payment.
	 */
	class LP_Gateway_Offline_Payment extends LP_Gateway_Abstract {

		/**
		 * @var LP_Settings
		 */
		public $settings;

		/**
		 * Instructions for making a payment.
		 *
		 * @var mixed|string
		 */
		public $instructions = '';

		/**
		 * @var string
		 */
		public $id = 'offline-payment';

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			parent::__construct();

			$this->icon               = $this->settings->get( 'icon', LP_PLUGIN_URL . 'inc/gateways/offline-payment/images/cod.png' );
			$this->method_title       = __( 'Offline Payment', 'learnpress' );
			$this->method_description = __( 'Make a payment with cash.', 'learnpress' );

			// Get settings
			$this->title        = $this->settings->get( 'title', $this->method_title );
			$this->description  = $this->settings->get( 'description', $this->method_description );
			$this->instructions = $this->settings->get( 'instructions' );

			if ( did_action( 'learn_press/offline-payment-add-on/loaded' ) ) {
				return;
			}

			add_action( 'learn-press/order/received', array( $this, 'instructions' ), 99 );
			add_filter(
				'learn-press/payment-gateway/' . $this->id . '/available',
				array(
					$this,
					'offline_payment_available',
				)
			);

			do_action( 'learn_press/offline-payment-add-on/loaded' );
		}

		/**
		 * Check gateway available.
		 *
		 * @return bool
		 */
		public function offline_payment_available(): bool {
			return LP_Settings::instance()->get( "{$this->id}.enable", 'no' ) === 'yes';
		}

		/**
		 * Output for the order received page.
		 *
		 * @param $order
		 */
		public function instructions( $order ) {
			if ( $order && ( $this->id == $order->payment_method ) && $this->instructions ) {
				echo stripcslashes( wpautop( wptexturize( $this->instructions ) ) );
			}
		}

		protected function _get( $name ) {
			return LP_Settings::instance()->get( $this->id . '.' . $name );
		}

		/**
		 * Admin payment settings.
		 *
		 * @return array
		 */
		public function get_settings() {
			return apply_filters(
				'learn-press/gateway-payment/offline-payment/settings',
				array(
					array(
						'type' => 'title',
					),
					array(
						'title'   => __( 'Enable', 'learnpress' ),
						'id'      => '[enable]',
						'default' => 'no',
						'type'    => 'checkbox',
					),
					array(
						'title'   => __( 'Testing Mode', 'learnpress' ),
						'id'      => '[sandbox]',
						'default' => 'no',
						'type'    => 'checkbox',
						'desc'    => __( 'Auto complete the order for testing purpose.' ),
					),
					array(
						'title'   => __( 'Title', 'learnpress' ),
						'id'      => '[title]',
						'default' => $this->title,
						'type'    => 'text',
					),
					array(
						'title'   => __( 'Instruction', 'learnpress' ),
						'id'      => '[description]',
						'default' => $this->description,
						'type'    => 'textarea',
					),
					array(
						'type' => 'sectionend',
					),
				)
			);
		}
		/**
		 * Payment form.
		 */
		public function get_payment_form() {
			return LP_Settings::instance()->get( $this->id . '.description' );
		}

		/**
		 * Process the payment and return the result
		 *
		 * @param $order_id
		 *
		 * @return array
		 * @throws Exception
		 */
		public function process_payment( $order_id ) {

			$order = learn_press_get_order( $order_id );

			// Mark as processing (payment won't be taken until delivery)
			$default_status = 'processing';

			/**
			 * If sandbox mode is turn on then the order
			 * should be completed immediately.
			 *
			 * @since 3.x.x
			 */
			if ( LP_Settings::instance()->get( $this->id . '.sandbox', 'no' ) === 'yes' ) {
				$default_status = 'completed';
			}

			$order->update_status( $default_status, __( 'Payment can be made upon delivery.', 'learnpress' ) );

			// Remove cart
			LearnPress::instance()->cart->empty_cart();

			// Return thank you redirect
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		}
	}
}
