<?php

namespace LearnPress\Helpers;

/**
 * Class Template
 *
 * @package LearnPress\Helpers
 * @since 1.0.0
 * @version 1.0.0
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
	 * @return void
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_frontend_template( string $file_name = '', array $args = array() ) {
		$default_path        = LP_PLUGIN_PATH . "templates/{$file_name}";
		$folder_name_rewrite = apply_filters( 'learn-press/folder-name-override', LP_PLUGIN_FOLDER_NAME );

		$from_theme_path = get_template_directory() . DIRECTORY_SEPARATOR . $folder_name_rewrite . DIRECTORY_SEPARATOR . $file_name;
		$path_load       = file_exists( $from_theme_path ) ? $from_theme_path : $default_path;

		$template = $this->get_template( $path_load, $args );

		if ( ! $this->include ) {
			return $template;
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
	 * Get frontend template file
	 *
	 * @param string $file_name
	 * @param array $args
	 *
	 * @return string|void
	 */
	public function get_frontend_template_type_classic( string $file_name = '', array $args = array() ) {
		$file_name = "classic/{$file_name}";
		$template  = $this->get_frontend_template( $file_name, $args );

		if ( ! $this->include ) {
			return $template;
		}
	}

	/**
	 * Get frontend group template files
	 *
	 * @param array $file_names
	 * @param array $args
	 *
	 * @return void
	 * @version 1.0.0
	 * @since 1.0.1
	 */
	public function get_frontend_templates_type_classic( array $file_names = array(), array $args = array() ) {
		foreach ( $file_names as $file_name ) {
			$search_extension = strrpos( $file_name, '.php' );
			if ( ! $search_extension ) {
				$file_name .= '.php';
			}

			$this->get_frontend_template_type_classic( $file_name, $args );
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
	protected function get_template( string $path_file, array $args = array() ) {
		extract( $args );
		if ( file_exists( $path_file ) ) {
			if ( $this->include ) {
				include $path_file;
			} else {
				return $path_file;
			}
		} else {
			printf( esc_html__( 'Path %s not exists.', 'realpress' ), $path_file );
			?>
			<br>
			<?php
		}
	}
}

