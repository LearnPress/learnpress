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
	 * Display page content.
	 *
	 * @return void
	 */
	public function display() {
		learn_press_admin_view(
			'help-center/html-help-center',
			array(
				'quick_links' => $this->get_quick_links(),
				'tick_icon'   => LP_WP_Filesystem::get_icon_svg( 'help-center/ico-hc-tick.svg' ),
			)
		);
	}
}

return new LP_Submenu_Help_Center();
