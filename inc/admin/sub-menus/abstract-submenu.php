<?php

use LearnPress\Helpers\Template;

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
	 * Tab's sections
	 *
	 * @var array
	 */
	protected $sections = array();

	/**
	 * Current page
	 *
	 * @var bool
	 */
	protected $page = false;

	/**
	 * LP_Abstract_Submenu constructor.
	 */
	public function __construct() {
		add_action( 'learn-press/admin/page-content-sections', array( $this, 'output_section_nav' ) );
		add_filter( 'admin_body_class', array( $this, 'body_class' ) );

		if ( $this->is_displaying() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}
	}

	public function enqueue_assets() {
	}

	public function is_displaying() {
		return $this->get_id() === LP_Request::get_string( 'page' );
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
		return apply_filters( 'learn-press/submenu-' . $this->get_id() . '-heading-tabs', $this->sanitize_tabs( $this->tabs ) );
	}

	/**
	 * Sanitize the tabs.
	 *
	 * @param $tabs
	 *
	 * @return array
	 */
	public function sanitize_tabs( $tabs ) {
		$sanitized_tabs = array();
		if ( $tabs ) {
			foreach ( $tabs as $tab => $name ) {
				// Maybe a tab is name of a class? Try to locate it.
				if ( is_string( $name ) && class_exists( $name ) ) {
					$objSettings                        = new $name();
					$sanitized_tabs[ $objSettings->id ] = $objSettings;
				} else {
					$sanitized_tabs[ $tab ] = $name;
				}
			}
		}

		return $sanitized_tabs;
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
		$tab = LP_Helper::sanitize_params_submitted( $_REQUEST['tab'] ?? '' );
		if ( empty( $tab ) || empty( $tabs[ $tab ] ) ) {
			$tab_keys = array_keys( $tabs );
			$tab      = reset( $tab_keys );
		}

		return $tab;
	}

	/**
	 * @return array|mixed
	 */
	public function has_tabs() {
		return $this->get_tabs();
	}

	/**
	 * @return mixed
	 */
	public function get_sections() {
		$active_tab = $this->get_active_tab();

		if ( ! empty( $this->tabs[ $active_tab ] ) ) {
			if ( is_callable( array( $this->tabs[ $active_tab ], 'get_sections' ) ) ) {
				$this->sections = call_user_func( array( $this->tabs[ $active_tab ], 'get_sections' ) );
			}
		}

		return apply_filters( 'learn-press/submenu-sections', $this->sections );
	}

	/**
	 * Get current page is displaying.
	 *
	 * @param bool $prefix
	 *
	 * @return bool|mixed|null
	 */
	public function get_active_page( $prefix = true ) {
		if ( false === $this->page ) {
			$this->page = LP_Request::get_param( 'page' );
		}

		return $prefix ? $this->page : str_replace( 'learn-press-', '', $this->page );
	}

	/**
	 * Get active section by checking ?section=tab-name
	 *
	 * @return bool|mixed
	 */
	public function get_active_section() {
		$sections = $this->get_sections();

		if ( ! $sections ) {
			return false;
		}

		$section = LP_Helper::sanitize_params_submitted( $_REQUEST['section'] ?? '' );
		if ( empty( $section ) || empty( $sections[ $section ] ) ) {
			$section_keys = array_keys( $sections );
			$section      = reset( $section_keys );
		}

		return $section;
	}

	/**
	 * Callback return template.
	 *
	 * @return bool|array|mixed
	 */
	public function get_callback() {
		return $this->callback;
	}

	/**
	 * Display menu content
	 */
	public function display() {
		$tabs       = $this->get_tabs();
		$active_tab = $this->get_active_tab();
		$classes    = array( 'wrap', 'lp-submenu-page', $this->get_id() );
		?>

		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<?php do_action( 'learn-press/admin/heading-icon', $active_tab ); ?>

			<h1 class="wp-heading-inline">
				<?php echo wp_kses_post( $this->get_menu_title() ); ?>
				<?php do_action( 'learn-press/admin/heading-title', $active_tab ); ?>
			</h1>

			<?php if ( $tabs ) { ?>
				<h2 class="nav-tab-wrapper">
					<?php foreach ( $tabs as $tab => $name ) { ?>
						<?php
						$obj_tab = false;

						if ( is_object( $name ) ) {
							$obj_tab = $name;
							$name    = $obj_tab->text;
							$tab     = $obj_tab->id;
						}

						$active_class = ( $tab == $active_tab ) ? ' nav-tab-active' : '';
						$tab_title    = apply_filters( 'learn-press/admin/submenu-heading-tab-title', $name, $tab );
						?>

						<?php if ( $active_class ) { ?>
							<span class="nav-tab<?php echo esc_attr( $active_class ); ?>"><?php echo esc_html( $tab_title ); ?></span>
						<?php } else { ?>
							<a class="nav-tab" href="?page=<?php echo esc_attr( $this->id ); ?>&tab=<?php echo esc_attr( $tab ); ?>"><?php echo esc_html( $tab_title ); ?></a>
						<?php } ?>
					<?php } ?>
				</h2>
			<?php } ?>

			<?php
			$classes  = array( 'lp-admin-tabs' );
			$sections = $this->get_sections();

			if ( $sections && sizeof( $sections ) > 1 ) {
				$classes[] = 'has-sections';
			}

			ob_start();
			$this->page_content();
			$content = ob_get_clean();
			if ( 'learn-press-settings' === LP_Request::get_param( 'page' ) ) {
				$wrapper = [ sprintf( '<form class="%s" method="post" enctype="multipart/form-data">', esc_attr( implode( ' ', $classes ) ) ) => '</form>' ];
			} else {
				$wrapper = [ sprintf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) ) => '</div>' ];
			}

			echo Template::instance()->nest_elements( $wrapper, $content );
			?>
		</div>
		<?php
	}

	/**
	 * This function for displaying content of active tab only.
	 * For displaying content of main page without tab,
	 * overwrite this function in subclass.
	 */
	public function page_content() {
		do_action( 'learn-press/admin/page-content-sections', $this );

		echo '<div class="lp-admin-tab-content">';

		if ( $this->has_tabs() ) {
			$page = $this->_get_page();
			$tab  = $this->get_active_tab();

			do_action( 'learn-press/admin/before-page-content-sections', $page, $tab, $this );

			// If I have a function named 'page_content_TAB_SLUG' then call it.
			$callback = array( $this, sprintf( 'page_content_%s', $tab ) );

			if ( is_callable( $callback ) ) {
				call_user_func_array( $callback, array() );
			} else {
				do_action( 'learn-press/admin/page-content-' . $page, $tab );
				do_action( 'learn-press/admin/page-content-' . $page . '/' . $tab );
			}

			do_action( 'learn-press/admin/after-page-content-sections', $page, $tab, $this );
		}

		echo '</div>';
	}

	/**
	 * Output section navigation.
	 */
	public function output_section_nav() {
		if ( $this->id !== $this->get_active_page() ) {
			return;
		}

		$active_section = $this->get_active_section();
		$sections       = $this->get_sections();

		if ( ! $sections ) {
			return;
		}
		?>

		<ul class="lp-admin-tab-navs">
			<?php foreach ( $sections as $slug => $section ) { ?>
				<?php
				$active_class  = ( $slug == $active_section ) ? ' nav-section-active' : '';
				$section_title = apply_filters( 'learn-press/admin/submenu-section-title', $section, $slug );
				?>

				<li class="nav-section<?php echo esc_attr( $active_class ); ?>">
					<?php if ( $active_class ) { ?>
						<span><?php echo wp_kses_post( $section_title ); ?></span>
					<?php } else { ?>
						<a href="<?php echo esc_url_raw( remove_query_arg( 'sub-section', add_query_arg( 'section', $slug ) ) ); ?>"><?php echo wp_kses_post( $section_title ); ?></a>
					<?php } ?>
				</li>
			<?php } ?>
		</ul>

		<?php
	}

	/**
	 * Display section content
	 */
	public function display_section() {
		$section = $this->get_active_section();

		if ( ! $section ) {
			return false;
		}

		$section_class = $this->sections[ $section ];

		if ( ! $section_class ) {
			return false;
		}

		do_action( 'learn-press/admin/page-' . $this->_get_page() . '/section-content', $section );

		if ( is_callable( array( $this, 'section_content_' . $section ) ) ) {
			call_user_func( array( $this, 'section_content_' . $section ) );
		} else {
			do_action( 'learn-press/admin/page-' . $this->_get_page() . '/section-content-' . $section );
		}

		return true;
	}

	/**
	 * Get this page id without prefix.
	 *
	 * @return mixed
	 */
	protected function _get_page() {
		return str_replace( 'learn-press-', '', $this->get_id() );
	}

	/**
	 * Append new class to body tag to control our page.
	 *
	 * @param $classes
	 *
	 * @return array|string
	 */
	public function body_class( $classes ) {
		$page = $this->get_active_page( false );

		if ( $page ) {
			if ( $classes ) {
				$classes = explode( ' ', $classes );
			} else {
				$classes = array();
			}

			$classes[] = 'learnpress';
			$classes[] = 'lp-submenu-' . $page;
			$classes   = array_filter( $classes );
			$classes   = array_unique( $classes );
			$classes   = join( ' ', $classes );
		}

		return $classes;
	}
}
