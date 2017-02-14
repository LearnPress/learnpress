<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Classes
 * @version 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_Admin' ) ) {
	/**
	 * Class LP_Admin
	 */
	class LP_Admin {
		/**
		 *  Constructor
		 */
		public function __construct() {
			$this->includes();
			add_action( 'admin_print_scripts', array( $this, 'plugin_js_settings' ) );
			add_action( 'template_redirect', array( $this, '_redirect' ) );
			add_action( 'delete_user', array( $this, 'delete_user_data' ) );
			add_action( 'delete_user_form', array( $this, 'delete_user_form' ) );
			add_action( 'wp_ajax_learn_press_rated', array( $this, 'rated' ) );
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

			add_action( 'admin_notices', array( $this, 'notice_outdated_templates' ) );
			add_action( 'admin_notices', array( $this, 'notice_setup_page' ) );
			add_action( 'admin_notices', array( $this, 'notice_required_permalink' ) );

		}

		public function notice_required_permalink() {

			if ( current_user_can( 'manage_options' ) ) {

				if ( !get_option( 'permalink_structure' ) ) {
					learn_press_add_notice( sprintf( __( 'LearnPress requires permalink option <strong>Post name</strong> is enabled. Please enable it <a href="%s">here</a> to ensure that all functions work properly.', 'learnpress' ), admin_url( 'options-permalink.php' ) ), 'error' );
				}
			}
		}

		public function notice_setup_page() {

			$args = array(
				array(
					'name_option' => 'learn_press_profile_page_id',
					'id'          => 'lp-admin-warning-profile',
					'title'       => __( 'Profile Page', 'learnpress' ),
					'url'         => admin_url( 'admin.php?page=learn-press-settings&tab=pages' )
				),
				array(
					'name_option' => 'learn_press_checkout_page_id',
					'id'          => 'lp-admin-warning-checkout',
					'title'       => __( 'Checkout Page', 'learnpress' ),
					'url'         => admin_url( 'admin.php?page=learn-press-settings&tab=checkout' )
				),
			);

			if ( current_user_can( 'manage_options' ) ) {

				$notice = esc_html__( 'The following required page(s) are currently missing: ', 'learnpress' );
				$count  = 0;
				$pages  = array();

				foreach ( $args as $key => $arg ) {
					$item_page_id   = get_option( $arg['name_option'] );
					$item_transient = get_transient( $arg['id'] );
					$item_page      = get_post( $item_page_id );

					if ( empty( $item_transient ) && ( empty( $item_page_id ) || empty( $item_page ) ) ) {
						$count ++;
						$pages[] = array(
							'url'   => $arg['url'],
							'title' => $arg['title']
						);

					}
				}

				foreach ( $pages as $key => $page ) {
					if ( $key == ( $count - 1 ) && $count != 1 ) {
						$notice .= esc_html__( ' and ', 'learnpress' );
					}
					$notice .= __( wp_kses( '<a href="' . $page['url'] . '">' . $page['title'] . '</a>', array( 'a' => array( 'href' => array() ) ) ), 'learnpress' );
				}


				$notice .= '.' . esc_html__( ' Please click to the link to set it up, ensure all functions work properly.', 'learnpress' );

				return $count ? learn_press_add_notice( $notice, 'error' ) : '';
			}
			return '';

		}

		public function notice_outdated_templates() {
			if ( current_user_can( 'manage_options' ) ) {
				$page = '';
				$tab  = '';
				if ( !empty( $_REQUEST['page'] ) ) {
					$page = $_REQUEST['page'];
				}

				if ( !empty( $_REQUEST['tab'] ) ) {
					$tab = $_REQUEST['tab'];
				}

				if ( $page == 'learn-press-tools' && $tab == 'templates' ) {
					return;
				}

				if ( learn_press_detect_outdated_template() ) {
					learn_press_admin_view( 'html-admin-notice-templates' );
				}
			}
		}

		public function rated() {
			update_option( 'learn_press_message_user_rated', 'yes' );
			die();
		}

		public function admin_footer_text( $footer_text ) {
			$current_screen = get_current_screen();
			$pages          = learn_press_get_screens();
			if ( isset( $current_screen->id ) && apply_filters( 'learn_press_display_admin_footer_text', in_array( $current_screen->id, $pages ) ) ) {
				if ( !get_option( 'learn_press_message_user_rated' ) ) {
					$footer_text = sprintf( __( 'If you like <strong>LearnPress</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating. A huge thanks in advance!', 'learnpress' ), '<a href="https://wordpress.org/support/plugin/learnpress/reviews/?filter=5#postform" target="_blank" class="lp-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'learnpress' ) . '">', '</a>' );
					ob_start(); ?>
                    <script type="text/javascript">
						var $ratingLink = $('a.lp-rating-link').click(function (e) {
							$.ajax({
								url    : '<?php echo admin_url( 'admin-ajax.php' );?>',
								data   : {
									action: 'learn_press_rated'
								},
								success: function () {
									$ratingLink.parent().html($ratingLink.data('rated'));
								}
							});
						});
                    </script>
					<?php
					$code = ob_get_clean();
					LP_Assets::add_script_tag( $code, '__all' );
				} else {
				}
			}
			return $footer_text;
		}

		function delete_user_form() {
			// What should be displayed here?
		}

		/**
		 * Delete records related user being deleted in other tables
		 *
		 * @param int $user_id
		 */
		function delete_user_data( $user_id ) {
			learn_press_delete_user_data( $user_id );
		}

		/**
		 * Output common js settings in admin
		 *
		 * @since 0.9.4
		 */
		public function plugin_js_settings() {
			static $did = false;
			if ( $did || !is_admin() ) return;
			$js = array(
				'ajax'       => admin_url( 'admin-ajax.php' ),
				'plugin_url' => learn_press_plugin_url(),
				'siteurl'    => home_url(),
				'localize'   => array(
					'button_ok'     => __( 'OK', 'learnpress' ),
					'button_cancel' => __( 'Cancel', 'learnpress' ),
					'button_yes'    => __( 'Yes', 'learnpress' ),
					'button_no'     => __( 'No', 'learnpress' )
				)
			);
			LP_Assets::add_param( $js, false, 'learn-press-global', 'LP_Settings' );
			if ( LP_Settings::instance()->get( 'debug' ) == 'yes' ) {
				LP_Assets::add_var( 'LEARN_PRESS_DEBUG', 'true', '__all' );
			}
			$did = true;
		}

		/**
		 * Redirect to admin settings page
		 */
		public function _redirect() {
			$page = isset( $_GET['page'] ) ? $_GET['page'] : '';
			if ( 'learn_press_settings' == $page ) {
				$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
				$tabs        = learn_press_settings_tabs_array();

				if ( !$current_tab || ( $tabs && empty( $tabs[$current_tab] ) ) ) {
					if ( $tabs ) {
						$tab_keys    = array_keys( $tabs );
						$current_tab = reset( $tab_keys );
						wp_redirect( admin_url( 'options-general.php?page=learn_press_settings&tab=' . $current_tab ) );
						exit();
					}
				}
			}
		}

		/**
		 * Include all classes and functions used for admin
		 */
		public function includes() {
			//crazy tu
			// Common function used in admin
			include_once( 'lp-admin-functions.php' );
			// Admin actions
			include_once( 'lp-admin-actions.php' );
			//Ajax Class
			include_once( 'class-lp-admin-ajax.php' );
			// Admin menu
			include_once( 'class-lp-admin-menu.php' );

			include_once( 'class-lp-meta-box-tabs.php' );

		}
	}

	new LP_Admin();
}