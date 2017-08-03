<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'LP_Profile' ) ) {
	/**
	 * Class LP_Profile
	 *
	 * Main class to controls the profile of a user
	 */
	class LP_Profile {
		/**
		 * The instances of all users has initialed a profile
		 *
		 * @var array
		 */
		protected static $_instances = array();

		/**
		 * @var LP_User
		 */
		protected $_user = false;

		protected $_role = '';

		/**
		 *  Constructor
		 */
		public function __construct( $user, $role = '' ) {

			$this->_user = $user;
			$this->get_user();

			if ( ! $role ) {
				$this->_role = $this->get_role();
			}

			add_action( 'learn-press/profile-tab-callback', array( $this, 'output' ), 10, 3 );
		}

		public function output( $tab, $args, $user ) {
			if ( ( $location = learn_press_locate_template( 'profile/tabs/' . $tab . '.php' ) ) && file_exists( $location ) ) {
				include $location;
			}
		}

		protected function get_role() {
			if ( $user = learn_press_get_current_user( false ) ) {
				if ( ! $user->is_guest() ) {
					if ( $this->_user->is_admin() ) {
						return 'admin';
					}

					if ( $this->_user->is_instructor() ) {
						return 'instructor';
					}

					return 'user';
				}
			}

			return '';
		}

		public function get_user() {
			if ( is_numeric( $this->_user ) ) {
				$this->_user = learn_press_get_user( $this->_user );
			} elseif ( empty( $this->_user ) ) {
				$this->_user = learn_press_get_current_user();
			}

			return $this->_user;
		}

		public function tab_dashboard() {
			learn_press_get_template( 'profile/dashboard.php', array( 'user' => $this->_user ) );
		}

		public function get_tabs() {

			$defaults = array(
				''              => array(
					'title'    => __( 'Dashboard', 'learnpress' ),
					'callback' => array( $this, 'tab_dashboard' )
				),
				'courses'       => array(
					'title'    => __( 'Courses', 'learnpress' ),
					'callback' => array( $this, 'tab_courses' )
				),
				'quizzes'       => array(
					'title'    => __( 'Quizzes', 'learnpress' ),
					'callback' => array( $this, 'tab_quizzes' )
				),
				'orders'        => array(
					'title'    => __( 'Orders', 'learnpress' ),
					'callback' => array( $this, 'tab_orders' )
				),
				'order-details' => array(
					'title'    => __( 'Order details', 'learnpress' ),
					'hidden'   => true,
					'callback' => array( $this, 'tab_order_details' )
				),
				'settings'      => array(
					'title'    => __( 'Settings', 'learnpress' ),
					'callback' => array( $this, 'tab_settings' )
				)
			);

			return apply_filters( 'learn-press/profile-tabs', $defaults );
			$course_endpoint = LP()->settings->get( 'profile_endpoints.profile-courses' );
			if ( ! $course_endpoint ) {
				$course_endpoint = 'profile-courses';
			}

			$quiz_endpoint = LP()->settings->get( 'profile_endpoints.profile-quizzes' );
			if ( ! $quiz_endpoint ) {
				$quiz_endpoint = 'profile-quizzes';
			}

			$order_endpoint = LP()->settings->get( 'profile_endpoints.profile-orders' );
			if ( ! $order_endpoint ) {
				$order_endpoint = 'profile-orders';
			}

			$view_order_endpoint = LP()->settings->get( 'profile_endpoints' );
			if ( ! $view_order_endpoint ) {
				$view_order_endpoint = 'order';
			}

			$defaults = array(

				$course_endpoint => array(
					'title'    => __( 'Courses', 'learnpress' ),
					'base'     => 'courses',
					'callback' => 'learn_press_profile_tab_courses_content'
				)
			);

			if ( $this->_user->get_id() == get_current_user_id() ) {
				$defaults[ $order_endpoint ] = array(
					'title'    => __( 'Orders', 'learnpress' ),
					'base'     => 'orders',
					'callback' => 'learn_press_profile_tab_orders_content'
				);
			}

			$tabs = apply_filters( 'learn_press_get_user_profile_tabs', $defaults, $this->_user );
			if ( $this->_user->get_id() == get_current_user_id() ) {
				$tabs['settings'] = array(
					'title'    => apply_filters( 'learn_press_user_profile_tab_edit_title', __( 'Settings', 'learnpress' ) ),
					'base'     => 'settings',
					'callback' => 'learn_press_profile_tab_edit_content'
				);
			}

			foreach ( $tabs as $slug => $opt ) {
				if ( ! empty( $defaults[ $slug ] ) ) {
					continue;
				}
				LP()->query_vars[ $slug ] = $slug;
				add_rewrite_endpoint( $slug, EP_PAGES );
			}

			return $tabs;
		}

		/**
		 * Get an instance of LP_Profile for a user id
		 *
		 * @param $user_id
		 *
		 * @return LP_Profile mixed
		 */
		public static function instance( $user_id = 0 ) {
			if ( ! $user_id ) {
				$user_id = get_current_user_id();
			}
			if ( empty( self::$_instances[ $user_id ] ) ) {
				self::$_instances[ $user_id ] = new self( $user_id );
			}

			return self::$_instances[ $user_id ];
		}
	}
}