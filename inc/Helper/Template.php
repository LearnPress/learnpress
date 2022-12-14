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
	 * @return void
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public function get_frontend_template( string $file_name = '', array $args = array() ) {
		$default_path        = LP_PLUGIN_PATH . "templates/{$file_name}";
		$folder_name_rewrite = apply_filters( 'learn_press_template_path', LP_PLUGIN_FOLDER_NAME );

		$from_theme_path = get_template_directory() . DIRECTORY_SEPARATOR . $folder_name_rewrite . DIRECTORY_SEPARATOR . $file_name;
		$path_load       = file_exists( $from_theme_path ) ? $from_theme_path : $default_path;

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

