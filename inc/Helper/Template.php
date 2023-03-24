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
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_frontend_template( string $file_name = '', array $args = array() ) {
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

		$path_load = $default_path;
		if ( file_exists( $from_child_theme_path ) ) {
			$path_load = $from_child_theme_path;
		} elseif ( file_exists( $from_theme_path ) ) {
			$path_load = $from_theme_path;
		}
		$template = $this->get_template( $path_load, $args );

		if ( ! $this->include ) {
			return $template;
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

	//
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
}

