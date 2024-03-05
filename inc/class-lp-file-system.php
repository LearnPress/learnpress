<?php
/**
 * Class LP_WP_Filesystem
 *
 * @version 1.0.1
 * @editor tungnx
 * @modify 4.1.3.1
 */
if ( ! class_exists( 'LP_WP_Filesystem' ) ) {
	class LP_WP_Filesystem {
		/**
		 * @var WP_Filesystem_Direct
		 */
		public $lp_filesystem;
		/**
		 * @var LP_WP_Filesystem
		 */
		protected static $instance;

		protected function __construct() {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}

			$this->lp_filesystem = $wp_filesystem;
		}

		/**
		 * Instance
		 *
		 * @return LP_WP_Filesystem
		 */
		public static function instance(): LP_WP_Filesystem {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/*public static function wp_file_system() {
			global $wp_filesystem;

			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . '/wp-admin/includes/file.php';
				WP_Filesystem();
			}
		}*/

		public static function chmod_dir(): int {
			if ( defined( 'FS_CHMOD_DIR' ) ) {
				$chmod_dir = FS_CHMOD_DIR;
			} else {
				$chmod_dir = ( fileperms( ABSPATH ) & 0777 | 0755 );
			}

			return $chmod_dir;
		}

		public static function chmod_file(): int {
			if ( defined( 'FS_CHMOD_FILE' ) ) {
				$chmod_file = FS_CHMOD_FILE;
			} else {
				$chmod_file = ( fileperms( ABSPATH . 'index.php' ) & 0777 | 0644 );
			}

			return $chmod_file;
		}

		/**
		 * Set CHMOD
		 *
		 * @param $path
		 * @param null $perms
		 *
		 * @return bool
		 */
		public function chmod( $path, $perms = null ): bool {
			if ( is_null( $perms ) ) {
				$perms = $this->is_file( $path ) ? self::chmod_file() : self::chmod_dir();
			}

			$output = @chmod( $path, $perms ); // phpcs:ignore

			if ( ! $output ) {
				$output = $this->lp_filesystem->chmod( $path, $perms, false );
			}

			return $output;
		}

		/**
		 * Check is file
		 *
		 * @param $path
		 *
		 * @return bool
		 */
		public function is_file( $path ): bool {
			$output = is_file( $path );

			if ( ! $output ) {
				$output = $this->lp_filesystem->is_file( $path );
			}

			return $output;
		}

		/**
		 * Check is dir
		 *
		 * @param $path
		 *
		 * @return bool
		 */
		public function is_dir( $path ): bool {
			$output = is_dir( $path );

			if ( ! $output ) {
				$output = $this->lp_filesystem->is_dir( $path );
			}

			return $output;
		}

		/**
		 * Check can read file
		 *
		 * @param $path
		 *
		 * @return bool
		 */
		public function is_readable( $path ): bool {
			$output = is_readable( $path );

			if ( ! $output ) {
				$output = $this->lp_filesystem->is_readable( $path );
			}

			return $output;
		}

		/**
		 * Check file can write
		 *
		 * @param $path
		 *
		 * @return bool
		 */
		public function is_writable( $path ): bool {
			$output = is_writable( $path );

			if ( ! $output ) {
				$output = $this->lp_filesystem->is_writable( $path );
			}

			return $output;
		}

		/**
		 * Delete file
		 *
		 * @param $path
		 *
		 * @return bool
		 */
		public function unlink( $path ): bool {
			$output = @unlink( $path );

			if ( ! $output ) {
				$output = $this->lp_filesystem->delete( $path, false, false );
			}

			return $output;
		}

		/**
		 * Get content of file
		 *
		 * @param $path
		 *
		 * @return false|string
		 */
		public function file_get_contents( $path ) {
			$output = @file_get_contents( $path );

			if ( ! $output ) {
				$output = $this->lp_filesystem->get_contents( $path );
			}

			return $output;
		}

		/**
		 * Put content to file
		 *
		 * @param $path
		 * @param $content
		 *
		 * @return false|int
		 */
		public function put_contents( $path, $content ) {
			$output = @file_put_contents( $path, $content ); // phpcs:ignore
			$this->chmod( $path );

			if ( ! $output ) {
				$output = $this->lp_filesystem->put_contents( $path, $content, self::chmod_file() );
			}

			return $output;
		}

		/**
		 * Check file exists
		 *
		 * @param $path
		 *
		 * @return bool
		 * @editor tungnx
		 * @modify 4.1.3.1
		 */
		public function file_exists( $path ): bool {
			$output = file_exists( $path );

			if ( ! $output ) {
				$output = $this->lp_filesystem->exists( $path );
			}

			return $output;
		}

		/**
		 * Put content
		 *
		 * @param $file_name
		 * @param $content
		 * @param string $folder
		 *
		 * @return false|int|void
		 */
		public function put_content_upload( $file_name, $content, $folder = 'learnpress' ) {
			$wp_upload_dir = wp_upload_dir( null, false );
			$upload_dir    = $wp_upload_dir['basedir'] . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
			$file_path     = $upload_dir . $file_name;

			$output = @file_put_contents( $file_path, $content ); // phpcs:ignore

			if ( ! $output ) {
				if ( ! $this->is_writable( $wp_upload_dir['basedir'] ) ) {
					return;
				}

				if ( ! $this->is_dir( $upload_dir ) ) {
					wp_mkdir_p( $upload_dir );
				}

				$output = $this->lp_filesystem->put_contents( $file_path, $content, self::chmod_file() );
			}

			return $output;
		}

		/**
		 * Copy file
		 *
		 * @param $source_path
		 * @param $des_path
		 * @param bool $overwrite
		 * @param false $perms
		 *
		 * @return bool
		 */
		public function copy( $source_path, $des_path, $overwrite = true, $perms = false ): bool {
			if ( ! $this->file_exists( $source_path ) ) {
				return false;
			}

			if ( ! $overwrite && $this->file_exists( $des_path ) ) {
				return false;
			}

			$output = @copy( $source_path, $des_path ); // phpcs:ignore

			if ( $perms && $output ) {
				$this->chmod( $des_path, $perms );
			}

			if ( ! $output ) {
				$output = $this->lp_filesystem->copy( $source_path, $des_path, $overwrite, $perms );
			}

			return $output;
		}

		/**
		 * Move file
		 *
		 * @param $source_path
		 * @param $des_path
		 * @param bool $overwrite
		 *
		 * @return bool
		 */
		public function move( $source_path, $des_path, bool $overwrite = true ): bool {
			if ( ! $this->file_exists( $source_path ) ) {
				return false;
			}

			if ( ! $overwrite && $this->file_exists( $des_path ) ) {
				return false;
			} elseif ( @rename( $source_path, $des_path ) ) {
				return true;
			} else {
				if ( $this->copy( $source_path, $des_path, $overwrite ) && $this->file_exists( $des_path ) ) {
					$this->unlink( $source_path );

					$output = true;
				} else {
					$output = false;
				}
			}

			if ( ! $output ) {
				$output = $this->lp_filesystem->move( $source_path, $des_path, $overwrite );
			}

			return $output;
		}

		public function download_url( $url, $timeout = 300, $signature_verification = false ) {
			return download_url( $url, $timeout, $signature_verification );
		}

		public function lp_handle_upload( &$file, $overrides = false, $time = null ) {
			return wp_handle_upload( $file, $overrides, $time );
		}

		/**
		 * Get size of file from url
		 *
		 * @param $url
		 *
		 * @return string
		 * @since 4.2.3
		 * @version 1.0.1
		 */
		public function get_file_size_from_url( $url ) {
			$tmp_file = $this->download_url( $url );
			$size     = '';
			if ( $tmp_file && ! is_wp_error( $tmp_file ) ) {
				$size = ( filesize( $tmp_file ) / 1024 < 1024 ) ? round( filesize( $tmp_file ) / 1024, 2 ) . 'KB' : round( filesize( $tmp_file ) / 1024 / 1024, 2 ) . 'MB';
			}

			return $size;
		}
	}
}
