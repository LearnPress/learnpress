<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Abstract_Submenu
 *
 * @since 3.0.0
 */
abstract class LP_Abstract_Submenu {
	/**
	 * Menu slug
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Menu title
	 *
	 * @var string
	 */
	protected $menu_title = '';

	/**
	 * Page title
	 *
	 * @var string
	 */
	protected $page_title = '';

	/**
	 * Priority
	 *
	 * @var int
	 */
	protected $priority = 10;

	/**
	 * Capability
	 *
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * Menu icon
	 *
	 * @var string
	 */
	protected $icon = '';

	/**
	 * Callback (may not be used)
	 *
	 * @var bool
	 */
	protected $callback = false;

	/**
	 * Heading tabs
	 *
	 * @var array
	 */
	protected $tabs = array();

	/**
	 * LP_Abstract_Submenu constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function get_menu_title() {
		return $this->menu_title;
	}

	/**
	 * @param string $menu_title
	 */
	public function set_menu_title( $menu_title ) {
		$this->menu_title = $menu_title;
	}

	/**
	 * @return string
	 */
	public function get_page_title() {
		return $this->page_title;
	}

	/**
	 * @param string $page_title
	 */
	public function set_page_title( $page_title ) {
		$this->page_title = $page_title;
	}

	/**
	 * @return int|string
	 */
	public function get_priority() {
		return $this->priority;
	}

	/**
	 * @param string|int $priority
	 */
	public function set_priority( $priority ) {
		$this->priority = $priority;
	}

	/**
	 * @return string
	 */
	public function get_capability() {
		return $this->capability;
	}

	/**
	 * @param string $capability
	 */
	public function set_capability( $capability ) {
		$this->capability = $capability;
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return $this->icon;
	}

	/**
	 * @param string $icon
	 */
	public function set_icon( $icon ) {
		$this->icon = $icon;
	}

	/**
	 * Get heading tabs.
	 *
	 * @return mixed|array
	 */
	public function get_tabs() {
		return apply_filters( 'learn-press/submenu-' . $this->get_id() . '-heading-tabs', $this->tabs );
	}

	/**
	 * Get active tab by checking ?tab=tab-name
	 *
	 * @return bool|mixed
	 */
	public function get_active_tab() {
		$tabs = $this->get_tabs();
		if ( ! $tabs ) {
			return false;
		}
		$tab = ! empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : false;
		if ( ! $tab || empty( $tabs[ $tab ] ) ) {
			$tab_keys = array_keys( $tabs );
			$tab      = reset( $tab_keys );
		}

		return $tab;
	}

	public function has_tabs() {
		return $this->get_tabs();
	}

	/**
	 * Display menu content
	 */
	public function display() {
		$tabs       = $this->get_tabs();
		$active_tab = $this->get_active_tab();
		?>
        <div class="wrap <?php echo $this->get_id(); ?>">
            <div id="icon-themes" class="icon32"><br></div>
			<?php if ( $tabs ) { ?>
                <h2 class="nav-tab-wrapper">
					<?php foreach ( $tabs as $tab => $name ) { ?>
						<?php
						$active_class = ( $tab == $active_tab ) ? ' nav-tab-active' : '';
						$tab_title    = apply_filters( 'learn-press/admin/submenu-heading-tab-title', $name, $tab );
						?>
						<?php if ( $active_class ) { ?>
                            <span class="nav-tab<?php echo esc_attr( $active_class ); ?>"><?php echo $tab_title; ?></span>
						<?php } else { ?>
                            <a class="nav-tab"
                               href="?page=learn-press-settings&tab=<?php echo $tab; ?>"><?php echo $tab_title; ?></a>
						<?php } ?>
					<?php } ?>
                </h2>
			<?php } else { ?>
                <h1 class="wp-heading-inline"><?php echo $this->get_menu_title(); ?></h1>
			<?php } ?>
			<?php $this->page_content(); ?>
        </div>
		<?php
	}

	/**
	 * This function for displaying content of active tab only.
	 * For displaying content of main page without tab,
	 * overwrite this function in sub class.
	 */
	public function page_content() {
		if ( $this->has_tabs() ) {
		    // If I have a function named 'page_content_TAB_SLUG' then call it.
			$callback = array( $this, sprintf( 'page_content_%s', $this->get_active_tab() ) );
			if ( is_callable( $callback ) ) {
				call_user_func_array( $callback, array() );
			} else {
			    // Otherwise, do a action.
				do_action( 'learn-press/admin/page-content-' . $this->get_active_tab() );
			}
		}
	}
}