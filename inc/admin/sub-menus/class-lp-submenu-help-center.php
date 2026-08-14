<?php
/**
 * Submenu: LearnPress Help Center.
 *
 * @author  ThimPress
 * @package LearnPress/Admin
 * @since   4.4.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class LP_Submenu_Help_Center
 *
 * @since 4.4.5
 */
class LP_Submenu_Help_Center extends LP_Abstract_Submenu {

	/**
	 * URL of the remote "What's New" + "Latest Articles" JSON.
	 *
	 * Fetched the same way LP_Manager_Addons::$url_list_addons is (plain
	 * wp_remote_get, no auth, no transient caching) — see
	 * LP_Rest_Addon_Controller::list_addons(). Left empty until the team
	 * finalizes the hosting URL and JSON schema; while empty, get_remote_data()
	 * falls back to the local demo file so the page still renders.
	 *
	 * @var string
	 */
	protected $url_help_center_data = '';

	/**
	 * LP_Submenu_Help_Center constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-help-center';
		$this->menu_title = __( 'Help Center', 'learnpress' );
		$this->page_title = __( 'LearnPress Help Center', 'learnpress' );
		$this->priority   = 25;
		$this->callback   = array( $this, 'display' );

		parent::__construct();
	}

	/**
	 * Enqueue assets for this page only.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// Styles are loaded via LP_Admin_Assets::_get_styles() as 'lp-help-center'.
	}

	/**
	 * Quick-link cards shown at the top of the page.
	 *
	 * Icon files live in assets/images/icons/help-center/ico-hc-*.svg and are
	 * inlined via LP_WP_Filesystem::get_icon_svg(), same convention as the
	 * Course Builder dashboard cards (see BuilderDashboardTemplate::html_statistics_cards()).
	 *
	 * @return array
	 */
	protected function get_quick_links(): array {
		$links = apply_filters(
			'learn-press/admin/help-center/quick-links',
			array(
				array(
					'icon'        => 'help-center/ico-hc-support-ticket.svg',
					'title'       => __( 'Support Ticket', 'learnpress' ),
					'description' => __( 'Need help with your LMS website? Submit a ticket to the LearnPress support team.', 'learnpress' ),
					'button'      => __( 'Create Ticket', 'learnpress' ),
					'url'         => 'https://help.thimpress.com/',
				),
				array(
					'icon'        => 'help-center/ico-hc-video-tutorials.svg',
					'title'       => __( 'Video Tutorials', 'learnpress' ),
					'description' => __( 'Watch step-by-step videos about LearnPress settings and common workflows.', 'learnpress' ),
					'button'      => __( 'Watch Videos', 'learnpress' ),
					'url'         => 'https://www.youtube.com/@LearnPressLMS/videos',
				),
				array(
					'icon'        => 'help-center/ico-hc-documentation.svg',
					'title'       => __( 'Documentation', 'learnpress' ),
					'description' => __( 'Browse LearnPress setup guides, course management, quizzes, payment, and emails.', 'learnpress' ),
					'button'      => __( 'Read Docs', 'learnpress' ),
					'url'         => 'https://learnpresslms.com/docs/',
				),
				array(
					'icon'        => 'help-center/ico-hc-community.svg',
					'title'       => __( 'Community', 'learnpress' ),
					'description' => __( 'Join the LearnPress community to share ideas, ask questions, and connect with LMS site owners.', 'learnpress' ),
					'button'      => __( 'Join Community', 'learnpress' ),
					'url'         => 'https://www.facebook.com/groups/learnpress/',
				),
				array(
					'icon'        => 'help-center/ico-hc-feedback.svg',
					'title'       => __( 'Feedback', 'learnpress' ),
					'description' => __( 'Share your thoughts, report issues, and help improve the LearnPress experience.', 'learnpress' ),
					'button'      => __( 'Send Feedback', 'learnpress' ),
					'url'         => 'https://learnpresslms.com/feedback/',
				),
				array(
					'icon'        => 'help-center/ico-hc-affiliate.svg',
					'title'       => __( 'Affiliate', 'learnpress' ),
					'description' => __( 'Promote LearnPress products, track referrals, and manage your affiliate resources.', 'learnpress' ),
					'button'      => __( 'Join Affiliate', 'learnpress' ),
					'url'         => 'https://thimpress.com/become-an-affiliate/',
				),
			)
		);

		foreach ( $links as &$link ) {
			$link['icon_svg'] = LP_WP_Filesystem::get_icon_svg( $link['icon'] ?? '' );
		}
		unset( $link );

		return $links;
	}

	/**
	 * "What's New" + "Latest Articles" data.
	 *
	 * Fetches $url_help_center_data the same way the Add-ons page does
	 * (wp_remote_get, 30s timeout, no auth — see
	 * LP_Rest_Addon_Controller::list_addons()). Falls back to the local demo
	 * JSON bundled with the plugin when the URL isn't set yet or the request
	 * fails, so the page keeps rendering while the schema is finalized.
	 *
	 * @return array
	 */
	protected function get_remote_data(): array {
		$default = array(
			'whats_new' => array(),
			'articles'  => array(),
		);

		$data = null;

		if ( ! empty( $this->url_help_center_data ) ) {
			$response = wp_remote_get( $this->url_help_center_data, array( 'timeout' => 30 ) );

			if ( ! is_wp_error( $response ) ) {
				try {
					$data = LP_Helper::json_decode( wp_remote_retrieve_body( $response ), true );
				} catch ( Exception $e ) {
					$data = null;
				}
			}
		}

		if ( ! is_array( $data ) ) {
			$file          = LP()->plugin_path( 'inc/admin/data/help-center-demo.json' );
			$lp_filesystem = LP_WP_Filesystem::instance();
			$data          = $lp_filesystem->file_exists( $file )
				? json_decode( $lp_filesystem->file_get_contents( $file ), true )
				: null;
		}

		if ( ! is_array( $data ) ) {
			return $default;
		}

		return apply_filters( 'learn-press/admin/help-center/remote-data', wp_parse_args( $data, $default ) );
	}

	/**
	 * Banner ad image shown in place of the newsletter sign-up box.
	 *
	 * @return array{image: string, url: string}
	 */
	protected function get_banner_ad(): array {
		return apply_filters(
			'learn-press/admin/help-center/banner-ad',
			array(
				'image' => LP_PLUGIN_URL . 'assets/images/image-help-center.jpeg',
				'url'   => '',
			)
		);
	}

	/**
	 * Display page content.
	 *
	 * @return void
	 */
	public function display() {
		$remote_data = $this->get_remote_data();

		learn_press_admin_view(
			'help-center/html-help-center',
			array(
				'quick_links' => $this->get_quick_links(),
				'whats_new'   => $remote_data['whats_new'] ?? array(),
				'articles'    => $remote_data['articles'] ?? array(),
				'banner_ad'   => $this->get_banner_ad(),
				'tick_icon'   => LP_WP_Filesystem::get_icon_svg( 'help-center/ico-hc-tick.svg' ),
			)
		);
	}
}

return new LP_Submenu_Help_Center();
