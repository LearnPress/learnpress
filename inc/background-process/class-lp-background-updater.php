<?php
/**
 * Send emails in background
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LP_Background_Updater' ) ) {
	/**
	 * Class LP_Background_Updater
	 *
	 * @since 3.0.0
	 */
	class LP_Background_Updater extends LP_Abstract_Background_Process {

		/**
		 * @var string
		 */
		protected $action = 'lp_updater';

		/**
		 * @var int
		 */
		protected $queue_lock_time = 3600;

		/**
		 * LP_Background_Emailer constructor.
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * @param mixed $callback
		 *
		 * @return bool
		 */
		protected function task( $callback ) {
			$updater = LP_Updater::instance();
			if ( $updated_versions = get_option( 'learn-press-update-versions' ) ) {
				$updated_versions = array();
			}

			foreach ( $updater->get_update_files() as $version => $file ) {
				$file = LP_PLUGIN_PATH . '/inc/updates/' . $file;

				if ( version_compare( $version, LEARNPRESS_VERSION, '<' ) ) {
					if ( ! in_array( $version, $updated_versions ) ) {
						include_once $file;
						$updated_versions[] = $version;
					}
					continue;
				}

				include_once $file;
			}
			update_option( 'learn-press-update-versions', $updated_versions, false );

			return false;
		}
	}
}