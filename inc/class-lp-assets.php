<?php
/**
 * Class LP_Assets
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

class LP_Assets extends LP_Abstract_Assets {
	protected static $_instance;

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct();

		add_action( 'wp_print_footer_scripts', array( $this, 'show_overlay' ) );
	}

	/**
	 * Get default styles in admin.
	 *
	 * @return mixed
	 */
	protected function _get_styles() {
		return apply_filters(
			'learn-press/frontend-default-styles',
			array(
				'lp-font-awesome-5' => array(
					'url'     => self::url( 'src/css/vendor/font-awesome-5.min.css' ),
					'screens' => array( 'learnpress' ),
				),
				'lp-bundle'         => array(
					'url'     => self::url( 'css/bundle.min.css' ),
					'screens' => array( 'learnpress' ),
				),
				'learnpress'        => array(
					'url'     => self::url( 'css/learnpress.css' ),
					'screens' => 'learnpress',
				),
			)
		);
	}

	/**
	 * Set localize script data
	 *
	 * @return array
	 */
	public function _get_script_data(): array {
		return array(
			'lp-global'       => array(
				'url'                                => learn_press_get_current_url(),
				'siteurl'                            => site_url(),
				'ajax'                               => admin_url( 'admin-ajax.php' ),
				'courses_url'                        => learn_press_get_page_link( 'courses' ),
				'post_id'                            => get_the_ID(),
				'user_id'                            => get_current_user_id(), // use: course-progress.
				'theme'                              => get_stylesheet(),
				'localize'                           => array(
					'button_ok'     => esc_html__( 'OK', 'learnpress' ),
					'button_cancel' => esc_html__( 'Cancel', 'learnpress' ),
					'button_yes'    => esc_html__( 'Yes', 'learnpress' ),
					'button_no'     => esc_html__( 'No', 'learnpress' ),
				),
				'lp_rest_url'                        => get_rest_url(),
				'nonce'                              => wp_create_nonce( 'wp_rest' ),
				'option_enable_popup_confirm_finish' => LP_Settings::get_option( 'enable_popup_confirm_finish', 'yes' ),
			),
			'lp-checkout'     => array(
				'ajaxurl'            => home_url(),
				'user_checkout'      => LP()->checkout()->get_checkout_email(),
				'i18n_processing'    => esc_html__( 'Processing', 'learnpress' ),
				'i18n_redirecting'   => esc_html__( 'Redirecting', 'learnpress' ),
				'i18n_invalid_field' => esc_html__( 'Invalid field', 'learnpress' ),
				'i18n_unknown_error' => esc_html__( 'Unknown error', 'learnpress' ),
				'i18n_place_order'   => esc_html__( 'Place order', 'learnpress' ),
			),
			'lp-profile-user' => array(
				'processing'  => esc_html__( 'Processing', 'learnpress' ),
				'redirecting' => esc_html__( 'Redirecting', 'learnpress' ),
				'avatar_size' => learn_press_get_avatar_thumb_size(),
			),
			'lp-course'       => learn_press_single_course_args(),
			'lp-quiz'         => learn_press_single_quiz_args(),
		);

	}

	/**
	 * Config load scripts
	 *
	 * @return array
	 */
	public function _get_scripts(): array {
		$wp_js = array(
			'jquery',
			'wp-element',
			'wp-compose',
			'wp-data',
			'wp-hooks',
			'wp-api-fetch',
			'lodash',
		);

		$scripts = apply_filters(
			'learn-press/frontend-default-scripts',
			array(
				'vue-libs'             => new LP_Asset_Key(
					self::url( 'src/js/vendor/vue/vue_libs_special.min.js' )
				),
				'lp-modal'             => new LP_Asset_Key(
					self::url( 'js/dist/frontend/modal' . self::$_min_assets . '.js' ),
					array( 'jquery' )
				),
				'lp-plugins-all'       => new LP_Asset_Key( self::url( 'js/vendor/plugins.all.min.js' ) ),
				'lp-global'            => new LP_Asset_Key(
					self::url( self::$_folder_source . 'js/global' . self::$_min_assets . '.js' ),
					array( 'jquery', 'underscore', 'utils' )
				),
				'lp-utils'             => new LP_Asset_Key(
					self::url( 'js/dist/utils' . self::$_min_assets . '.js' ),
					array( 'jquery' )
				),
				'lp-checkout'          => new LP_Asset_Key(
					self::url( self::$_folder_source . 'js/frontend/checkout' . self::$_min_assets . '.js' ),
					array( 'lp-global', 'lp-utils' ),
					array( LP_PAGE_CHECKOUT ),
					0,
					1
				),
				'lp-data-controls'     => new LP_Asset_Key(
					self::url( 'js/dist/js/data-controls' . self::$_min_assets . '.js' ),
					array_merge( $wp_js, array( 'lp-global' ) )
				),
				'lp-config'            => new LP_Asset_Key(
					self::url( 'js/dist/frontend/lp-configs' . self::$_min_assets . '.js' ),
					array_merge( $wp_js, array( 'lp-global' ) )
				),
				// 'lp-lesson'           => new LP_Asset_Key( self::url( self::$_folder_source .'js/frontend/lesson' . self::$_min_assets . '.js' ) ),
				'lp-question-types'    => new LP_Asset_Key(
					self::url( 'js/dist/frontend/question-types' . self::$_min_assets . '.js' ),
					array_merge( $wp_js, array( 'lp-global' ) )
				),
				'lp-single-curriculum' => new LP_Asset_Key(
					self::url( 'js/dist/frontend/single-curriculum' . self::$_min_assets . '.js' ),
					array_merge(
						$wp_js,
						array(
							'lp-global',
							'lp-utils',
						)
					),
					array( LP_PAGE_SINGLE_COURSE_CURRICULUM ),
					0,
					1
				),
				'lp-quiz'              => new LP_Asset_Key(
					self::url( 'js/dist/frontend/quiz' . self::$_min_assets . '.js' ),
					array_merge(
						$wp_js,
						array(
							'wp-i18n',
							'lp-global',
							'lp-utils',
							'lp-data-controls',
							'lp-question-types',
							'lp-modal',
							'lp-config',
							'lp-single-curriculum',
							'jquery-ui-sortable', // Use in question type: sorting choice - Nhamdv

						)
					),
					array( LP_PAGE_QUIZ ),
					0,
					1
				),
				'lp-single-course'     => new LP_Asset_Key(
					self::url( 'js/dist/frontend/single-course' . self::$_min_assets . '.js' ),
					array_merge(
						$wp_js,
						array(
							'lp-global',
							'lp-utils',
						)
					),
					array( LP_PAGE_SINGLE_COURSE ),
					0,
					1
				),
				'lp-courses'           => new LP_Asset_Key(
					self::url( self::$_folder_source . 'js/frontend/courses' . self::$_min_assets . '.js' ),
					array( 'lp-global', 'lodash', 'lp-utils' ),
					array( LP_PAGE_COURSES ),
					0,
					1
				),
				'lp-profile-user'      => new LP_Asset_Key(
					self::url( self::$_folder_source . 'js/frontend/profile' . self::$_min_assets . '.js' ),
					array(
						'lp-global',
						'lp-utils',
						'plupload',
						'backbone',
						'jquery-ui-slider',
						'jquery-ui-draggable',
						'jquery-touch-punch',
					),
					array( LP_PAGE_PROFILE ),
					0,
					1
				),
				'lp-profile-v2'        => new LP_Asset_Key(
					self::url( 'js/dist/frontend/profile' . self::$_min_assets . '.js' ),
					array_merge(
						$wp_js,
						array( 'wp-i18n' )
					),
					array( LP_PAGE_PROFILE ),
					0,
					1
				),
				'lp-become-a-teacher'  => new LP_Asset_Key(
					self::url( self::$_folder_source . 'js/frontend/become-teacher' . self::$_min_assets . '.js' ),
					array( 'jquery', 'lp-utils' ),
					array( LP_PAGE_BECOME_A_TEACHER ),
					0,
					1
				),
				/*
				'lp-custom'           => new LP_Asset_Key( self::url( 'js/dist/frontend/custom' . self::$_min_assets . '.js' ),
					array( 'jquery', )
				),*/
			)
		);

		wp_set_script_translations( 'lp-quiz', 'learnpress' );

		return $scripts;
	}

	/**
	 * Load assets
	 */
	public function load_scripts() {
		// Register script.
		// $this->_register_scripts();
		// $scripts = $this->_get_scripts();

		// if ( $scripts ) {
		// foreach ( $scripts as $handle => $data ) {
		// $enqueue = false;
		//
		// do_action( 'learn-press/enqueue-script/' . $handle );
		//
		// if ( ! empty( $data['screens'] ) ) {
		// $enqueue = $this->is_screen( $data['screens'] );
		// }
		//
		// if ( $enqueue ) {
		// wp_enqueue_script( $handle );
		// }
		// }
		// }

		$page_current = lp_page_controller()::page_current();
		$this->handle_js( $page_current );

		$styles = $this->_get_styles();
		if ( $styles ) {
			foreach ( $styles as $handle => $data ) {
				$enqueue = false;

				do_action( 'learn-press/enqueue-style/' . $handle );

				wp_register_style( $handle, $data['url'], array(), self::$_version_assets );

				if ( ! empty( $data['screens'] ) ) {
					$enqueue = $this->is_screen( $data['screens'] );
				}

				if ( $enqueue ) {
					wp_enqueue_style( $handle );
				}
			}
		}

		do_action( 'learn-press/after-enqueue-scripts' );
	}

	protected function handle_js( $page_current ) {
		$scripts = $this->_get_scripts();
		/**
		 * @var LP_Asset_Key[] $scripts
		 */
		foreach ( $scripts as $handle => $script ) {
			if ( ! $script instanceof LP_Asset_Key ) {
				continue;
			}

			wp_register_script( $handle, $script->_url, $script->_deps, self::$_version_assets, $script->_in_footer );

			if ( ! $script->_only_register ) {
				$can_load_js = true;

				if ( ! empty( $script->_screens ) ) {
					$can_load_js = apply_filters(
						'learnpress/frontend/can-load-js/' . $handle,
						in_array( $page_current, $script->_screens ),
						$page_current,
						$script->_screens
					);
				}

				if ( $can_load_js ) {
					wp_enqueue_script( $handle );
				}
			}
		}

		/**
		 * Set translate on file js of folder js/dist
		 * Path translate of a string on file ".pot" if have must map to js/dist
		 */
		wp_set_script_translations( 'lp-quiz', 'learnpress' );
	}

	/**
	 * Check is currently in a screen required.
	 *
	 * @param array $screens
	 *
	 * @return bool
	 * @since 3.3.0
	 */
	public function is_screen( $screens ) {
		$pages = array(
			'profile',
			'become_a_teacher',
			'term_conditions',
			'checkout',
			'courses',
		);

		$single_post_types                  = array();
		$single_post_types[ LP_COURSE_CPT ] = 'course';
		$is_screen                          = false;

		if ( $screens === true || $screens === '*' ) {
			$is_screen = true;
		} else {
			$screens = is_array( $screens ) ? $screens : array( $screens );
			if ( in_array( 'learnpress', $screens ) ) {
				foreach ( $pages as $page ) {
					if ( $page === 'courses' && learn_press_is_courses() ) {
						$is_screen = true;
						break;
					}

					if ( is_post_type_archive( 'lp_collection' ) || is_singular( 'lp_collection' ) ) {
						$is_screen = true;
						break;
					}

					if ( learn_press_is_page( $page ) ) {
						$is_screen = true;
						break;
					}

					foreach ( $single_post_types as $post_type => $alias ) {
						if ( is_singular( $post_type ) ) {
							$is_screen = true;
							break 2;
						}
					}
				}
			} else {
				foreach ( $pages as $page ) {
					if ( in_array( $page, $screens ) ) {
						if ( $page === 'courses' && learn_press_is_courses() ) {
							$is_screen = true;
							break;
						}

						if ( learn_press_is_page( $page ) ) {
							$is_screen = true;
							break;
						}
					}
				}
			}

			if ( ! $is_screen ) {
				foreach ( $single_post_types as $post_type => $alias ) {
					if ( is_singular( $post_type ) && in_array( $alias, $screens ) ) {
						$is_screen = true;
						break;
					}
				}
			}
		}

		return $is_screen;
	}

	/**
	 * Add lp overlay
	 *
	 * @since 3.2.8
	 * @version 1.0.1
	 * @author tungnx
	 */
	public function show_overlay() {
		$page_current = LP_Page_Controller::page_current();
		if ( ! in_array(
			$page_current,
			array( LP_PAGE_SINGLE_COURSE_CURRICULUM, LP_PAGE_SINGLE_COURSE, LP_PAGE_QUIZ )
		) ) {
			return;
		}

		if ( 'yes' !== LP_Settings::get_option( 'enable_popup_confirm_finish', 'yes' ) ) {
			return;
		}

		echo '<div class="lp-overlay">';
		apply_filters( 'learnpress/modal-dialog', learn_press_get_template( 'global/lp-modal-overlay' ) );
		echo '</div>';
	}

	public static function instance() {
		if ( is_admin() ) {
			return null;
		}

		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

/**
 * Shortcut function to get instance of LP_Assets
 *
 * @return LP_Assets|null
 */
function learn_press_assets() {
	return LP_Assets::instance();
}

learn_press_assets();

