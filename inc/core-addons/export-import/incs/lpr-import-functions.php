<?php
function lpr_upload_dir( $time = null ) {
	$siteurl = get_option( 'siteurl' );
	$upload_path = 'uploads';//trim( get_option( 'upload_path' ) );

	if ( empty( $upload_path ) || 'uploads' == $upload_path ) {
		$dir = LPR_EXPORT_IMPORT_PATH . '/uploads';
	} elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
		// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
		$dir = path_join( ABSPATH, $upload_path );
	} else {
		$dir = $upload_path;
	}

	$url = plugins_url() . $upload_path;
	
	$uploads = apply_filters( 'upload_dir',
		array(
			'path'    => $dir,
			'url'     => $url,		
			'error'   => false,
		) );

	// Make sure we have an uploads directory.
	if ( ! wp_mkdir_p( $uploads['path'] ) ) {
		$message = sprintf( __( 'Unable to create directory. Is its parent directory writable by the server?' ) );
		$uploads['error'] = $message;
	}

	return $uploads;
}
function lpr_import_handle_upload( $file, $overrides = array() ) {
	// The default error handler.
	if ( ! function_exists( 'lpr_handle_upload_error' ) ) {
		function lpr_handle_upload_error( $file, $message ) {
			return array( 'error' => $message );
		}
	}
	$action = 'lpr_import_handle_upload';
	/**
	 * The dynamic portion of the hook name, $action, refers to the post action.
	 *
	 * @since 2.9.0 as 'wp_handle_upload_prefilter'
	 * @since 4.0.0 Converted to a dynamic hook with $action
	 *
	 * @param array $file An array of data for a single file.
	 */
	$file = apply_filters( "lpr_import_handle_upload_prefilter", $file );

	// You may define your own function and pass the name in $overrides['upload_error_handler']
	$upload_error_handler = 'lpr_handle_upload_error';
	if ( isset( $overrides['upload_error_handler'] ) ) {
		$upload_error_handler = $overrides['upload_error_handler'];
	}

	// You may have had one or more 'wp_handle_upload_prefilter' functions error out the file. Handle that gracefully.
	if ( isset( $file['error'] ) && ! is_numeric( $file['error'] ) && $file['error'] ) {
		return $upload_error_handler( $file, $file['error'] );
	}

	// Install user overrides. Did we mention that this voids your warranty?

	// You may define your own function and pass the name in $overrides['unique_filename_callback']
	$unique_filename_callback = null;
	if ( isset( $overrides['unique_filename_callback'] ) ) {
		$unique_filename_callback = $overrides['unique_filename_callback'];
	}

	/*
	 * This may not have orignially been intended to be overrideable,
	 * but historically has been.
	 */
	if ( isset( $overrides['upload_error_strings'] ) ) {
		$upload_error_strings = $overrides['upload_error_strings'];
	} else {
		// Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
		$upload_error_strings = array(
			false,
			__( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.' ),
			__( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.' ),
			__( 'The uploaded file was only partially uploaded.' ),
			__( 'No file was uploaded.' ),
			'',
			__( 'Missing a temporary folder.' ),
			__( 'Failed to write file to disk.' ),
			__( 'File upload stopped by extension.' )
		);
	}

	// All tests are on by default. Most can be turned off by $overrides[{test_name}] = false;
	$test_form = isset( $overrides['test_form'] ) ? $overrides['test_form'] : true;
	$test_size = isset( $overrides['test_size'] ) ? $overrides['test_size'] : true;

	// If you override this, you must provide $ext and $type!!
	$test_type = isset( $overrides['test_type'] ) ? $overrides['test_type'] : true;
	$mimes = isset( $overrides['mimes'] ) ? $overrides['mimes'] : false;

	$test_upload = isset( $overrides['test_upload'] ) ? $overrides['test_upload'] : true;

	// A correct form post will pass this test.
	/*if ( $test_form && ( ! isset( $_POST['action'] ) || ( $_POST['action'] != $action ) ) ) {
		return call_user_func( $upload_error_handler, $file, __( 'Invalid form submission.' ) );
	}*/
	// A successful upload will pass this test. It makes no sense to override this one.
	if ( isset( $file['error'] ) && $file['error'] > 0 ) {
		return call_user_func( $upload_error_handler, $file, $upload_error_strings[ $file['error'] ] );
	}

	$test_file_size = 'wp_handle_upload' === $action ? $file['size'] : filesize( $file['tmp_name'] );
	// A non-empty file will pass this test.
	if ( $test_size && ! ( $test_file_size > 0 ) ) {
		if ( is_multisite() ) {
			$error_msg = __( 'File is empty. Please upload something more substantial.' );
		} else {
			$error_msg = __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.' );
		}
		return call_user_func( $upload_error_handler, $file, $error_msg );
	}

	// A properly uploaded file will pass this test. There should be no reason to override this one.
	$test_uploaded_file = 'wp_handle_upload' === $action ? @ is_uploaded_file( $file['tmp_name'] ) : @ is_file( $file['tmp_name'] );
	if ( $test_upload && ! $test_uploaded_file ) {
		return call_user_func( $upload_error_handler, $file, __( 'Specified file failed upload test.' ) );
	}

	// A correct MIME type will pass this test. Override $mimes or use the upload_mimes filter.
	if ( $test_type ) {
		//print_r($file);
		$wp_filetype = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
		$ext = empty( $wp_filetype['ext'] ) ? '' : $wp_filetype['ext'];
		$type = empty( $wp_filetype['type'] ) ? '' : $wp_filetype['type'];
		$proper_filename = empty( $wp_filetype['proper_filename'] ) ? '' : $wp_filetype['proper_filename'];
		// Check to see if wp_check_filetype_and_ext() determined the filename was incorrect
		if ( $proper_filename ) {
			$file['name'] = $proper_filename;
		}
		if ( ( ! $type || !$ext ) && ! current_user_can( 'unfiltered_upload' ) ) {
			return call_user_func( $upload_error_handler, $file, __( 'Sorry, this file type is not permitted for security reasons.' ) );
		}
		if ( ! $type ) {
			$type = $file['type'];
		}
	} else {
		$type = '';
	}

	/*
	 * A writable uploads dir will pass this test. Again, there's no point
	 * overriding this one.
	 */
	if ( ! ( ( $uploads = wp_upload_dir() ) && false === $uploads['error'] ) ) {
		return call_user_func( $upload_error_handler, $file, $uploads['error'] );
	}

	$filename = wp_unique_filename( $uploads['path'], $file['name'], $unique_filename_callback );

	// Move the file to the uploads dir.
	$new_file = $uploads['path'] . "/$filename";
	if ( 'lpr_handle_upload' === $action ) {
		$move_new_file = @ move_uploaded_file( $file['tmp_name'], $new_file );
	} else {
		$move_new_file = @ rename( $file['tmp_name'], $new_file );
	}

	if ( false === $move_new_file ) {
		return $upload_error_handler( $file, sprintf( __('The uploaded file could not be moved' ) ) );
	}

	// Set correct file permissions.
	$stat = stat( dirname( $new_file ));
	$perms = $stat['mode'] & 0000666;
	@ chmod( $new_file, $perms );

	// Compute the URL.
	$url = $uploads['url'] . "/$filename";

	if ( is_multisite() ) {
		delete_transient( 'dirsize_cache' );
	}

	/**
	 * Filter the data array for the uploaded file.
	 *
	 * @since 2.1.0
	 *
	 * @param array  $upload {
	 *     Array of upload data.
	 *
	 *     @type string $file Filename of the newly-uploaded file.
	 *     @type string $url  URL of the uploaded file.
	 *     @type string $type File type.
	 * }
	 * @param string $context The type of upload action. Values include 'upload' or 'sideload'.
	 */
	return apply_filters( 'lpr_handle_upload', array(
		'file' => $new_file,
		'url'  => $url,
		'type' => $type
	), 'upload' );
}