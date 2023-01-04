<?php
/**
 * Class LP_Submenu_Addons
 *
 * @since 3.0.0
 */
class LP_Submenu_Addons extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Addons constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-addons';
		$this->menu_title = __( 'Add-ons', 'learnpress' );
		$this->page_title = __( 'LearnPress Add-ons', 'learnpress' );
		$this->priority   = 20;
		$this->callback   = [ $this, 'display' ];

		//$this->add_ons_tabs();

		parent::__construct();
	}

	public function display() {
		echo '<h1>' . __( 'LearPress Addons' ) . '</h1>';
		echo '<div class="lp-addons-page">';
		lp_skeleton_animation_html( 20 );
		echo '</div>';
	}

	public function add_ons_tabs() {
		// Check is page addons
		$current_page = LP_Helper::getUrlCurrent();
		$pattern      = '/.*page=learn-press-addons.*/';
		$match        = preg_match( $pattern, $current_page, $matches );
		if ( ! $match ) {
			return;
		}

		/*$tabs = array(
			'all'       => sprintf( __( 'All (%d)', 'learnpress' ), '<span class="count-addons-all"></span>' ),
			'installed' => sprintf( __( 'Installed (%d)', 'learnpress' ), '<span class="count-addons-installed"></span>' ),
			'paid'      => sprintf( __( 'Paid (%d)', 'learnpress' ), '<span class="count-addons-paid"></span>' ),
			'free'      => sprintf( __( 'Free (%d)', 'learnpress' ), '<span class="count-addons-free"></span>' ),
			'update'    => sprintf( __( 'Update (%d)', 'learnpress' ), '<span class="count-addons-update"></span>' ),
			'more'      => __( 'Get more', 'learnpress' ),
			//'themes'    => sprintf( __( 'Themes (%d)', 'learnpress' ), LP_Plugins_Helper::count_themes() ),
		);

		$this->tabs = apply_filters(
			'learn-press/admin/page-addons-tabs',
			$tabs
		);*/
	}

	public function page_content_installed() {
		$this->page_content_search_form();
		learn_press_admin_view( 'addons/html-plugins-installed' );
	}

	public function page_content_more() {
		$this->page_content_search_form();
		learn_press_admin_view( 'addons/html-plugins-more' );
	}

	public function page_content_themes() {
		$this->page_content_search_form();
		learn_press_admin_view( 'addons/html-themes' );
	}

	public function page_content_search_form() {
		?>

		<p class="search-box">
			<input type="text" class="lp-search-addon" value="" placeholder="<?php esc_attr_e( 'Search...', 'learnpress' ); ?>">
		</p>

		<?php
	}
}

return new LP_Submenu_Addons();
