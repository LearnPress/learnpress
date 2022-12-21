<?php
/**
 * Class LP_Outdated_Template_Helper
 *
 * @author ThimPress
 * @since  3.0.0
 */
class LP_Outdated_Template_Helper {
	/**
	 * Template counter
	 *
	 * @var array
	 */
	public static $counts = array(
		'all'         => 0,
		'outdated'    => 0,
		'unversioned' => 0,
		'up-to-date'  => 0,
	);

	/**
	 * Get list of template files are overwritten in the theme
	 *
	 * @param bool $check
	 *
	 * @return array|bool
	 */
	public static function get_theme_templates( $check = false ) {
		$template_folder = learn_press_template_path();
		$template_path   = LP_PLUGIN_PATH . '/templates/';
		$template_dir    = get_template_directory();
		$stylesheet_dir  = get_stylesheet_directory();
		$t_folder        = basename( $template_dir );
		$s_folder        = basename( $stylesheet_dir );

		$found_files        = array(
			$t_folder => array(),
			$s_folder => array(),
		);
		$outdated_templates = false;

		$scanned_files = self::scan_template_files( $template_path );
		foreach ( $scanned_files as $file ) {
			$theme_folder = '';

			if ( file_exists( $stylesheet_dir . '/' . $file ) ) {
				$theme_file   = $stylesheet_dir . '/' . $file;
				$theme_folder = $s_folder;
			} elseif ( file_exists( $stylesheet_dir . '/' . $template_folder . '/' . $file ) ) {
				$theme_file   = $stylesheet_dir . '/' . $template_folder . '/' . $file;
				$theme_folder = $s_folder;
			} elseif ( file_exists( $template_dir . '/' . $file ) ) {
				$theme_file   = $template_dir . '/' . $file;
				$theme_folder = $t_folder;
			} elseif ( file_exists( $template_dir . '/' . $template_folder . '/' . $file ) ) {
				$theme_file   = $template_dir . '/' . $template_folder . '/' . $file;
				$theme_folder = $t_folder;
			} else {
				$theme_file = false;
			}

			if ( ! empty( $theme_file ) ) {
				self::$counts['all'] ++;
				$core_version  = self::get_file_version( $template_path . $file );
				$theme_version = self::get_file_version( $theme_file );

				if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
					if ( ! $outdated_templates ) {
						$outdated_templates = true;
					}
					$found_files[ $theme_folder ][] = array(
						str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ),
						$theme_version ? $theme_version : '-',
						$core_version,
						true,
					);
					if ( empty( $theme_version ) ) {
						self::$counts['unversioned'] ++;
					}
					self::$counts['outdated'] ++;
				} else {
					$found_files[ $theme_folder ][] = array(
						str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ),
						$theme_version ? $theme_version : '?',
						$core_version ? $core_version : '?',
						null,
					);
				}
			}
			if ( $check && $outdated_templates ) {
				return $outdated_templates;
			}
		}
		if ( sizeof( $found_files ) > 1 ) {
			$found_files = array_merge( $found_files[ $t_folder ], $found_files[ $s_folder ] );
		} else {
			$found_files = reset( $found_files );
		}

		usort( $found_files, array( __CLASS__, '_sort_templates' ) );

		return $check ? $outdated_templates : $found_files;
	}

	/**
	 * Check if there is any outdated template files in current theme.
	 *
	 * @return array|bool
	 */
	public static function detect_outdated_template() {
		$template_folder = learn_press_template_path();
		$template_path   = LP_PLUGIN_PATH . '/templates/';
		$template_dir    = get_template_directory();
		$stylesheet_dir  = get_stylesheet_directory();
		$scanned_files   = self::scan_template_files( $template_path );
		$parent_item     = 0;
		$child_item      = 0;

		foreach ( $scanned_files as $file ) {
			$theme_file = false;
			$cradle     = '';

			if ( $stylesheet_dir == $template_dir ) { // Parent theme
				if ( file_exists( $template_dir . '/' . $file ) ) {
					$theme_file = $template_dir . '/' . $file;
					$cradle     = 'parent';
				} elseif ( file_exists( $template_dir . '/' . $template_folder . '/' . $file ) ) {
					$theme_file = $template_dir . '/' . $template_folder . '/' . $file;
					$cradle     = 'parent';
				}
			} else { // Child Theme
				if ( file_exists( $stylesheet_dir . '/' . $file ) ) {
					$theme_file = $stylesheet_dir . '/' . $file;
					$cradle     = 'child';
				} elseif ( file_exists( $stylesheet_dir . '/' . $template_folder . '/' . $file ) ) {
					$theme_file = $stylesheet_dir . '/' . $template_folder . '/' . $file;
					$cradle     = 'child';
				} elseif ( file_exists( $template_dir . '/' . $file ) ) {
					$theme_file = $template_dir . '/' . $file;
					$cradle     = 'parent';
				} elseif ( file_exists( $template_dir . '/' . $template_folder . '/' . $file ) ) {
					$theme_file = $template_dir . '/' . $template_folder . '/' . $file;
					$cradle     = 'parent';
				}
			}

			if ( ! empty( $theme_file ) ) {
				$core_version  = self::get_file_version( $template_path . $file );
				$theme_version = self::get_file_version( $theme_file );

				if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
					if ( $cradle == 'parent' ) {
						$parent_item ++;
					} else {
						$child_item ++;
					}
				}
			}
		}

		if ( ! empty( $child_item ) || ! empty( $parent_item ) ) {
			return array(
				'parent_item' => $parent_item,
				'child_item'  => $child_item,
			);
		}

		return false;
	}

	/**
	 * Scan all template files in a folder (and sub-folders).
	 *
	 * @param string $template_path
	 *
	 * @return array
	 */
	public static function scan_template_files( $template_path ) {
		$files  = @scandir( $template_path );
		$result = array();

		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $value ) {
				if ( ! in_array( $value, array( '.', '..', 'index.php', 'index.html' ) ) ) {
					if ( is_dir( $template_path . '/' . $value ) ) {
						$sub_files = self::scan_template_files( $template_path . '/' . $value );

						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . '/' . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get number version of a template file.
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	public static function get_file_version( $file ) {
		if ( ! file_exists( $file ) ) {
			return '';
		}

		$fp        = fopen( $file, 'r' );
		$file_data = fread( $fp, 8192 );
		fclose( $fp );
		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$version = _cleanup_header_comment( $match[1] );
		}

		return $version;
	}

	/**
	 * Sort overrides templates are outdated first
	 *
	 * @param array $a
	 * @param array $b
	 *
	 * @return int
	 */
	public static function _sort_templates( $a, $b ) {
		if ( $a[3] && $b[3] ) {
			return 0;
		}

		if ( $a[3] ) {
			return - 1;
		}

		if ( $b[3] ) {
			return 1;
		}

		return 0;
	}
}
