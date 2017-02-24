<?php
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_Profile' ) ) {
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
		 *  Constructor
		 */
		public function __construct() {

		}

		/**
		 * Get an instance of LP_Profile for a user id
		 *
		 * @param $user_id
		 *
		 * @return mixed
		 */
		public static function instance( $user_id ) {
			if ( empty( self::$_instances[$user_id] ) ) {
				self::$_instances[$user_id] = new self( $user_id );
			}
			return self::$_instances[$user_id];
		}
	}
}