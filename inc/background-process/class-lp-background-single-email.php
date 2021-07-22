<?php
/**
 * Send emails in background
 */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Background_Single_Email' ) ) {
	/**
	 * Class LP_Background_Single_Email
	 *
	 * @since 4.1.1
	 * @author tungnx
	 */
	class LP_Background_Single_Email extends WP_Async_Request {
		protected $prefix = 'lp';
		protected $action = 'background_single_email';
		protected static $instance;

		/**
		 * Method async handle
		 */
		protected function handle() {
			$params     = $_POST['params'] ?? false;
			$class_name = $_POST['class_name'] ?? false;

			if ( ! $class_name || ! $params ) {
				error_log( 'Params send email on background is invalid' );

				return;
			}

			if ( ! class_exists( $class_name ) ) {
				error_log( 'Class not exists: ' . $class_name );

				return;
			}

			if ( ! method_exists( $class_name, 'handle' ) ) {
				error_log( "Method 'handle' not exists on class $class_name" );

				return;
			}

			/**
			 * @var LP_Email_Type_Enrolled_Course $email
			 */
			$email = new $class_name;
			$email->handle( $params );
		}

		/**
		 * @return LP_Background_Single_Email
		 */
		public static function instance(): self {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	// Must run instance to register ajax.
	LP_Background_Single_Email::instance();
}
