<?php
/**
 * Class LP_Assets
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.1
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
		//Note: hook wp_head load before hook wp_enqueue_scripts
		add_action( 'wp_head', [ $this, 'load_scripts_styles_on_head' ], - 1 );
	}

	/**
	 * Get default styles in frontend.
	 *
	 * @return array
	 */
	protected function _get_styles(): array {
		$is_rtl = is_rtl() ? '-rtl' : '';

		return apply_filters(
			'learn-press/frontend-default-styles',
			array(
				'learnpress'         => new LP_Asset_Key(
					self::url( 'css/learnpress' . $is_rtl . self::$_min_assets . '.css' ),
					array(),
					array(
						LP_PAGE_COURSES,
						LP_PAGE_SINGLE_COURSE,
						LP_PAGE_SINGLE_COURSE_CURRICULUM,
						LP_PAGE_QUIZ,
						LP_PAGE_QUESTION,
						LP_PAGE_CHECKOUT,
						LP_PAGE_BECOME_A_TEACHER,
						LP_PAGE_PROFILE,
					),
					0
				),
				'lp-instructor'      => new LP_Asset_Key(
					self::url( 'css/instructor' . $is_rtl . self::$_min_assets . '.css' ),
					array(),
					array(),
					1
				),
				'lp-instructors'     => new LP_Asset_Key(
					self::url( 'css/instructors' . $is_rtl . self::$_min_assets . '.css' ),
					[],
					[],
					1
				),
				'learnpress-widgets' => new LP_Asset_Key(
					self::url( 'css/widgets' . $is_rtl . self::$_min_assets . '.css' ),
					array(),
					array(),
					0
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
		$localize_script = [
			'lp-global'   => array(
				//'url'                                => learn_press_get_current_url(),
				'siteurl'                            => site_url(),
				'ajax'                               => admin_url( 'admin-ajax.php' ),
				'courses_url'                        => learn_press_get_page_link( 'courses' ),
				'post_id'                            => get_the_ID(),
				'user_id'                            => get_current_user_id(),
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
				'is_course_archive'                  => LP_Page_Controller::is_page_courses(),
				'lpArchiveSkeleton'                  => lp_archive_skeleton_get_args(),
				'lpArchiveLoadAjax'                  => LP_Settings_Courses::is_ajax_load_courses() ? 1 : 0,
				'lpArchiveNoLoadAjaxFirst'           => LP_Settings_Courses::is_ajax_load_courses() && LP_Settings_Courses::is_no_load_ajax_first_courses() ? 1 : 0,
				'lpArchivePaginationType'            => LP_Settings::get_option( 'course_pagination_type' ),
				'noLoadCoursesJs'                    => LP_Settings::theme_no_support_load_courses_ajax() ? 1 : 0,
			),
			'lp-checkout' => array(
				'ajaxurl'            => home_url( '/' ),
				//'user_checkout'      => LP_Checkout::instance()->get_checkout_email(),
				'i18n_processing'    => esc_html__( 'Processing', 'learnpress' ),
				'i18n_redirecting'   => esc_html__( 'Redirecting', 'learnpress' ),
				'i18n_invalid_field' => esc_html__( 'Invalid field', 'learnpress' ),
				'i18n_unknown_error' => esc_html__( 'Unknown error', 'learnpress' ),
				'i18n_place_order'   => esc_html__( 'Place order', 'learnpress' ),
			),
			'lp-profile'  => array(
				'text_upload'  => __( 'Upload', 'learnpress' ),
				'text_replace' => __( 'Replace', 'learnpress' ),
				'text_remove'  => __( 'Remove', 'learnpress' ),
				'text_save'    => __( 'Save', 'learnpress' ),
			),
			'lp-quiz'     => learn_press_single_quiz_args(),
		];

		return apply_filters( 'learnpress/frontend/localize_script', $localize_script );
	}

	/**
	 * Localize data for all page frontend.
	 *
	 * @return array
	 */
	public function localize_data_global(): array {
		$cover_image_dimensions = LP_Settings::get_option(
			'cover_image_dimensions',
			array(
				'width'  => 1290,
				'height' => 250,
			)
		);
		$aspectRatio            = $cover_image_dimensions['width'] / $cover_image_dimensions['height'];

		return apply_filters(
			'learn-press/frontend/localize-data-global',
			[
				'site_url'          => site_url(),
				'user_id'           => get_current_user_id(),
				'theme'             => get_stylesheet(),
				'lp_rest_url'       => get_rest_url(),
				'nonce'             => wp_create_nonce( 'wp_rest' ),
				'is_course_archive' => LP_Page_Controller::is_page_courses(),
				'courses_url'       => learn_press_get_page_link( 'courses' ),
				'urlParams'         => lp_archive_skeleton_get_args(),
				'lp_version'        => LearnPress::instance()->version,
				'lp_rest_load_ajax' => get_rest_url( null, 'lp/v1/load_content_via_ajax/' ),
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'lpAjaxUrl'         => LP_Settings::url_handle_lp_ajax(),
				'coverImageRatio'   => $aspectRatio,
				'toast'             => [
					'gravity'     => 'bottom',
					'position'    => 'center',
					'duration'    => 3000,
					'close'       => 1,
					'stopOnFocus' => 1,
					'classPrefix' => 'lp-toast',
				],
				'i18n'              => [],
			]
		);
	}

	/**
	 * Localize data for all page frontend.
	 *
	 * @return array
	 */
	public function localize_data_courses(): array {
		return apply_filters(
			'learn-press/frontend/localize-script/courses',
			[
				'lpArchiveLoadAjax'        => LP_Settings_Courses::is_ajax_load_courses() ? 1 : 0,
				'lpArchiveNoLoadAjaxFirst' => LP_Settings_Courses::is_ajax_load_courses() && LP_Settings_Courses::is_no_load_ajax_first_courses() ? 1 : 0,
				'lpArchivePaginationType'  => LP_Settings::get_option( 'course_pagination_type' ),
				'noLoadCoursesJs'          => LP_Settings::theme_no_support_load_courses_ajax() ? 1 : 0,
			]
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
				// lp-plugins-all use only for FE, when FE 2 release will remove it.
				'lp-plugins-all'       => new LP_Asset_Key( self::url( 'js/vendor/plugins.all.min.js' ) ),
				'lp-global'            => new LP_Asset_Key(
					self::url( self::$_folder_source . 'js/global' . self::$_min_assets . '.js' ),
					array( 'jquery', 'underscore', 'utils' )
				),
				'lp-utils'             => new LP_Asset_Key(
					self::url( 'js/dist/utils' . self::$_min_assets . '.js' ),
					array( 'jquery' )
				),
				'lp-load-ajax'         => new LP_Asset_Key(
					self::url( 'js/dist/loadAJAX' . self::$_min_assets . '.js' ),
					[],
					[],
					0,
					0,
					'',
					[ 'strategy' => 'async' ]
				),
				'lp-checkout'          => new LP_Asset_Key(
					self::url( 'js/dist/frontend/checkout' . self::$_min_assets . '.js' ),
					[],
					[ LP_PAGE_CHECKOUT ],
					0,
					0,
					'',
					[ 'strategy' => 'async' ]
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
					array_merge( $wp_js, array( 'lp-global' ) ),
					array(),
					1,
					1
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
					1,
					'',
					[ 'strategy' => 'defer' ]
				),
				'lp-curriculum'        => new LP_Asset_Key(
					self::url( 'js/dist/frontend/curriculum' . self::$_min_assets . '.js' ),
					[],
					array( LP_PAGE_SINGLE_COURSE_CURRICULUM, LP_PAGE_SINGLE_COURSE ),
					0,
					0,
					'',
					[ 'strategy' => 'defer' ]
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
						)
					),
					array( LP_PAGE_QUIZ ),
					0,
					1,
					'',
					[ 'strategy' => 'defer' ]
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
					0,
					'',
					[ 'strategy' => 'defer' ]
				),
				'lp-courses'           => new LP_Asset_Key(
					self::url( 'js/dist/frontend/courses' . self::$_min_assets . '.js' ),
					array(
						'lp-global',
						'wp-hooks',
					), // when Eduma v5.3.6 release a long time, will be remove lp-global.
					array( LP_PAGE_COURSES ),
					0,
					0,
					'',
					[ 'strategy' => 'defer' ]
				),
				'lp-courses-v2'        => new LP_Asset_Key(
					self::url( 'js/dist/frontend/courses-v2' . self::$_min_assets . '.js' ),
					[ 'utils' ], // dependency utils of wp, because js is using wpCookies
					[ LP_PAGE_COURSES ],
					0,
					0,
					'',
					[ 'strategy' => 'async' ]
				),
				'lp-instructors'       => new LP_Asset_Key(
					self::url( 'js/dist/frontend/instructors' . self::$_min_assets . '.js' ),
					[ 'lp-global' ],
					[],
					1,
					0,
					'',
					[ 'strategy' => 'defer' ]
				),
				'lp-profile'           => new LP_Asset_Key(
					self::url( 'js/dist/frontend/profile' . self::$_min_assets . '.js' ),
					array_merge(
						$wp_js,
						array( 'wp-i18n', 'lp-utils' )
					),
					array( LP_PAGE_PROFILE ),
					0,
					0,
					'',
					[ 'strategy' => 'defer' ]
				),
				'lp-widgets'           => new LP_Asset_Key(
					self::url( 'js/dist/frontend/widgets' . self::$_min_assets . '.js' ),
					[],
					array(),
					1,
					0,
					'',
					[ 'strategy' => 'async' ]
				),
				'lp-become-a-teacher'  => new LP_Asset_Key(
					self::url( 'js/dist/frontend/become-teacher' . self::$_min_assets . '.js' ),
					array( 'jquery' ),
					array( LP_PAGE_BECOME_A_TEACHER ),
					0,
					1,
					'',
					[ 'strategy' => 'defer' ]
				),
				'lp-course-filter'     => new LP_Asset_Key(
					self::url( 'js/dist/frontend/course-filter' . self::$_min_assets . '.js' ),
					array(),
					array(),
					1,
					1,
					'',
					[ 'strategy' => 'defer' ]
				),
			)
		);

		// Dequeue script 'smoothPageScroll' on item details, it makes can't scroll, when rewrite page item detail, can check to remove.
		if ( LP_PAGE_SINGLE_COURSE_CURRICULUM === LP_Page_Controller::page_current() ||
			LP_PAGE_QUIZ === LP_Page_Controller::page_current() ||
			LP_PAGE_QUESTION === LP_Page_Controller::page_current() ) {
			wp_dequeue_script( 'smoothPageScroll' );
		}

		return $scripts;
	}

	/**
	 * Load assets
	 *
	 * @author tungnx
	 * @version 1.0.1
	 * @since 3.2.8
	 */
	public function load_scripts() {
		$page_current = LP_Page_Controller::page_current();
		$this->handle_js( $page_current );
		$this->handle_style( $page_current );

		do_action( 'learn-press/after-enqueue-scripts' );
	}

	/**
	 * Add javascript to head
	 * Add style to head
	 *
	 * @return void
	 */
	public function load_scripts_styles_on_head() {
		$this->load_scripts_on_head();
		$this->load_styles_on_head();
	}

	/**
	 * Load scripts on head
	 * @return void
	 */
	public function load_scripts_on_head() {
		LP_Helper::print_inline_script_tag( 'lpData', $this->localize_data_global(), [ 'id' => 'lpData' ] );

		if ( LP_Page_Controller::is_page_courses() ) {
			LP_Helper::print_inline_script_tag( 'lpSettingCourses', $this->localize_data_courses(), [ 'id' => 'lpSettingCourses' ] );
		}
	}

	/**
	 * Load styles on head
	 * @return void
	 */
	public function load_styles_on_head() {
		$max_width         = esc_html( LP_Settings::get_option( 'width_container', '1290px' ) );
		$padding_container = apply_filters( 'learn-press/container-padding-width', '1rem' );
		$primary_color     = esc_html( LP_Settings::get_option( 'primary_color' ) );
		$secondary_color   = esc_html( LP_Settings::get_option( 'secondary_color' ) );
		?>
		<style id="learn-press-custom-css">
			:root {
				--lp-container-max-width: <?php echo $max_width; ?>;
				--lp-cotainer-padding: <?php echo $padding_container; ?>;
				--lp-primary-color: <?php echo ! empty( $primary_color ) ? $primary_color : '#ffb606'; ?>;
				--lp-secondary-color: <?php echo ! empty( $secondary_color ) ? $secondary_color : '#442e66'; ?>;
			}
		</style>
		<?php
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

		echo '<div class="lp-overlay" style="display: none">';
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

