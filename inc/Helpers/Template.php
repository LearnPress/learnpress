<?php

namespace LearnPress\Helpers;

/**
 * Class Template
 *
 * @package LearnPress\Helpers
 * @since 1.0.0
 * @version 1.0.1
 */
class Template {
	/**
	 * @var bool
	 */
	protected $include;

	protected function __construct() {

	}

	/**
	 * Set 1 for include file, 0 for not
	 * Set 1 for separate template is block, 0 for not | use "wp_is_block_theme" function
	 *
	 * @param bool $include
	 *
	 * @return self
	 */
	public static function instance( bool $include = true ): Template {
		$self          = new self();
		$self->include = $include;

		return $self;
	}

	/**
	 * Get template admin file
	 *
	 * @param string $file_name
	 * @param array $args
	 *
	 * @return void|string
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_admin_template( string $file_name = '', array $args = array() ) {
		$file_name = str_replace( '.php', '', $file_name );
		$path_file = LP_PLUGIN_PATH . "inc/admin/views/{$file_name}.php";

		$template = $this->get_template( $path_file, $args );

		if ( ! $this->include ) {
			return $template;
		}
	}

	/**
	 * Get template frontend file
	 *
	 * @param string $file_name
	 * @param array $args
	 *
	 * @return void|string
	 * @since 4.2.0
	 * @version 1.0.0
	 */
	public function get_frontend_template( string $file_name = '', array $args = array() ) {
		$default_path          = LP_PLUGIN_PATH . "templates/{$file_name}";
		$folder_name_rewrite   = learn_press_template_path();
		$file_name             = sanitize_text_field( $file_name );
		$from_child_theme_path = sprintf(
			'%s/%s/%s',
			get_stylesheet_directory(),
			$folder_name_rewrite,
			$file_name
		);
		$from_theme_path       = sprintf(
			'%s/%s/%s',
			get_template_directory(),
			$folder_name_rewrite,
			$file_name
		);

		$path_load = $default_path;
		if ( file_exists( $from_child_theme_path ) ) {
			$path_load = $from_child_theme_path;
		} elseif ( file_exists( $from_theme_path ) ) {
			$path_load = $from_theme_path;
		}

		$template = '';
		if ( realpath( $path_load ) ) {
			$template = $this->get_template( $path_load, $args );
		}

		if ( ! $this->include ) {
			return $template;
		}
	}

	/**
	 * @param array $file_names
	 * @param array $args
	 *
	 * @return void
	 */
	public function get_frontend_templates( array $file_names = array(), array $args = array() ) {
		foreach ( $file_names as $file_name ) {
			$search_extension = strrpos( $file_name, '.php' );
			if ( ! $search_extension ) {
				$file_name .= '.php';
			}

			$this->get_frontend_template( $file_name, $args );
		}
	}

	/**
	 * Include path file
	 *
	 * @param string $path_file
	 * @param array $args
	 *
	 * @return string|void
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_template( string $path_file, array $args = array() ) {
		try {
			extract( $args );

			if ( file_exists( $path_file ) ) {
				if ( $this->include ) {
					include $path_file;
				} else {
					return $path_file;
				}
			} else {
				printf( esc_html__( 'Path file %s not exists', 'learnpress' ), $path_file );
				echo '<br>';
			}
		} catch ( \Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Get frontend template block file
	 *
	 * @param string $file_name
	 * @param array $args
	 *
	 * @return string|void
	 */
	public function get_frontend_template_type_block( string $file_name = '', array $args = array() ) {
		$file_name = "block/{$file_name}";
		$template  = $this->get_frontend_template( $file_name, $args );

		if ( ! $this->include ) {
			return $template;
		}
	}

	/**
	 * Check file template is overwritten
	 *
	 * @param string $file_name
	 *
	 * @return bool|string return false if not, path file if yes
	 * @since 4.2.6
	 * @version 1.0.0
	 */
	public static function check_template_is_override( string $file_name = '' ) {
		$default_path          = LP_PLUGIN_PATH . "templates/{$file_name}";
		$folder_name_rewrite   = learn_press_template_path();
		$from_child_theme_path = sprintf(
			'%s/%s/%s',
			get_stylesheet_directory(),
			$folder_name_rewrite,
			$file_name
		);
		$from_theme_path       = sprintf(
			'%s/%s/%s',
			get_template_directory(),
			$folder_name_rewrite,
			$file_name
		);

		if ( file_exists( $from_child_theme_path ) ) {
			return $from_child_theme_path;
		} elseif ( file_exists( $from_theme_path ) ) {
			return $from_theme_path;
		}

		return false;
	}

	/**
	 * Nest elements by tags
	 *
	 * @param array $els [ 'html_tag_open' => 'html_tag_close' ]
	 * @param string $main_content
	 *
	 * @return string
	 */
	public function nest_elements( array $els = [], string $main_content = '' ): string {
		$html = '';
		foreach ( $els as $tag_open => $tag_close ) {
			$html .= $tag_open;
		}

		$html .= $main_content;

		foreach ( array_reverse( $els, true ) as $tag_close ) {
			$html .= $tag_close;
		}

		return $html;
	}

	/**
	 * Display sections
	 *
	 * @param array $sections
	 * ['name_section' => [ 'text_html' => '<span>example</span>' ]]
	 * ['name_section' => [ 'link_templates' => 'path_template' ]]
	 *
	 * @return void
	 */
	public function print_sections( array $sections = [], array $args = [] ) {
		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			foreach ( $section as $type => $val ) {
				switch ( $type ) {
					case 'link_templates':
						$this->get_frontend_template( $val, $args );
						break;
					default:
						if ( is_string( $val ) ) {
							//$allow_tag = wp_kses_allowed_html( 'post' );
							//echo wp_kses( $section, $allow_tag );
							echo $val;
						}
						break;
				}
			}
		}
	}

	/**
	 * Combine html elements
	 *
	 * @param array $elms
	 *
	 * @return string
	 * @since 4.2.6.9
	 */
	public static function combine_components( array $elms = [] ): string {
		$html = '';
		foreach ( $elms as $tag => $val ) {
			$html .= $val;
		}

		return $html;
	}

	/**
	 * Print message
	 *
	 * @param string $message
	 * @param string $status
	 *
	 * @return void
	 * @since 4.2.6.9.3
	 * @version 1.0.0
	 */
	public static function print_message( string $message, string $status = 'success' ) {
		$customer_message = [
			'content' => $message,
			'status'  => $status
		];

		Template::instance()->get_frontend_template( 'global/lp-message.php', compact( 'customer_message' ) );
	}
}

