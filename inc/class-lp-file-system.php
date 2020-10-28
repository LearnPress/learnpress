<?php
if ( ! class_exists( 'LP_WP_Filesystem' ) ) {
	class LP_WP_Filesystem {

		public static function wp_file_system() {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
		}

		public static function chmod_dir() {
			if ( defined( 'FS_CHMOD_DIR' ) ) {
				$chmod_dir = FS_CHMOD_DIR;
			} else {
				$chmod_dir = ( fileperms( ABSPATH ) & 0777 | 0755 );
			}

			return $chmod_dir;
		}

		public static function chmod_file() {
			if ( defined( 'FS_CHMOD_FILE' ) ) {
				$chmod_file = FS_CHMOD_FILE;
			} else {
				$chmod_file = ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 );
			}

			return $chmod_file;
		}

		public static function chmod( $path, $perms = null ) {
			if ( is_null( $perms ) ) {
				$perms = self::is_file( $path ) ? self::chmod_file() : self::chmod_dir();
			}

			$output = @chmod( $path, $perms ); // phpcs:ignore

			if ( ! $output ) {
				global $wp_filesystem;

				self::wp_file_system();

				$output = $wp_filesystem->chmod( $abs_path, $perms, false );
			}

			return $output;
		}

		public static function is_file( $path ) {
			$output = is_file( $path );

			if ( ! $output ) {

				global $wp_filesystem;

				self::wp_file_system();

				$output = $wp_filesystem->is_file( $abs_path );
			}

			return $output;
		}

		public static function is_dir( $path ) {
			$output = is_dir( $path );

			if ( ! $output ) {
				global $wp_filesystem;
				self::wp_file_system();

				$output = $wp_filesystem->is_dir( $path );
			}

			return $output;
		}


		public static function is_readable( $path ) {
			$output = is_readable( $path );

			if ( ! $output ) {
				global $wp_filesystem;
				self::wp_file_system();

				$output = $wp_filesystem->is_readable( $path );
			}

			return $output;
		}

		public static function is_writable( $path ) {
			$output = is_writable( $path );

			if ( ! $output ) {
				global $wp_filesystem;
				self::wp_file_system();

				$output = $wp_filesystem->is_writable( $path );
			}

			return $output;
		}

		public static function unlink( $path ) {
			$output = @unlink( $path );

			if ( ! $output ) {
				global $wp_filesystem;
				self::wp_file_system();

				$output = $wp_filesystem->delete( $path, false, false );
			}

			return $output;
		}

		public static function get_contents( $path ) {
			$output = @file_get_contents( $path );

			if ( ! $output ) {
				global $wp_filesystem;
				self::wp_file_system();

				if ( self::file_exists( $path ) ) {
					$output = $wp_filesystem->get_contents( $path );
				}
			}

			return $output;
		}

		public static function put_contents( $path, $content ) {
			$output = @file_put_contents( $path, $content ); // phpcs:ignore
			self::chmod( $path );

			if ( ! $output ) {
				global $wp_filesystem;
				self::wp_file_system();

				$output = $wp_filesystem->put_contents( $path, $content, self::chmod_file() );
			}

			return $output;
		}

		public static function file_exists( $path ) {
			$output = file_exists( $path );

			if ( ! $output ) {
				global $wp_filesystem;

				self::wp_file_system();

				$output = $wp_filesystem->exist( $path );
			}

			return (bool) $output;
		}

		public static function put_content_upload( $file_name, $content, $folder = 'learnpress' ) {
			$wp_upload_dir = wp_upload_dir( null, false );
			$upload_dir    = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
			$file_path     = $upload_dir . $file_name;

			$output = @file_put_contents( $file_path, $content ); // phpcs:ignore

			if ( ! $output ) {
				global $wp_filesystem;

				self::wp_file_system();

				if ( ! self::is_writable( $wp_upload_dir['basedir'] ) ) {
					return;
				}

				if ( ! self::is_dir( $upload_dir ) ) {
					wp_mkdir_p( $upload_dir );
				}

				$output = $wp_filesystem->put_contents( $file_path, $content, self::chmod_file() );
			}

			return $output;
		}

		public static function copy( $source_path, $des_path, $overwrite = true, $perms = false ) {
			if ( ! self::file_exists( $source_path ) ) {
				return false;
			}

			if ( ! $overwrite && self::file_exists( $des_path ) ) {
				return false;
			}

			$output = @copy( $source_path, $des_path ); // phpcs:ignore

			if ( $perms && $output ) {
				self::chmod( $des_path, $perms );
			}

			if ( ! $output ) {
				global $wp_filesystem;

				self::wp_file_system();

				$output = $wp_filesystem->copy( $source_path, $des_path, $overwrite, $perms );
			}

			return $output;
		}

		public static function move( $source_path, $des_path, $overwrite = true ) {
			if ( ! self::file_exists( $source_path ) ) {
				return false;
			}

			if ( ! $overwrite && self::file_exists( $des_path ) ) {
				return false;
			} elseif ( @rename( $source_path, $des_path ) ) {
				return true;
			} else {
				if ( self::copy( $source_path, $des_path, $overwrite ) && self::file_exists( $des_path ) ) {
					self::unlink( $source_path );

					return true;
				} else {
					$output = false;
				}
			}

			if ( ! $output ) {
				global $wp_filesystem;
				self::wp_file_system();

				$output = $wp_filesystem->move( $source_path, $des_path, $overwrite );
			}

			return $output;
		}
	}

	new LP_WP_Filesystem();
}
