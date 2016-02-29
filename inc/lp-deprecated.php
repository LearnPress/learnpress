<?php
/**
 * Handle renamed/removed hooks
 *
 */
global $lp_map_deprecated_filters;

$lp_map_deprecated_filters = array(
	'learn_press_register_add_ons' => 'learn_press_loaded'
);

foreach ( $lp_map_deprecated_filters as $old => $new ) {
	add_filter( $old, 'lp_deprecated_filter_mapping', 9999999 );
}

function lp_deprecated_filter_mapping( $data, $arg_1 = '', $arg_2 = '', $arg_3 = '' ) {
	global $lp_map_deprecated_filters, $wp_filter;
	$filter = current_filter();
	if ( !empty( $wp_filter[$filter] ) && count( $wp_filter[$filter] ) > 1 ) {
		_deprecated_function( 'The ' . $filter . ' hook', '1.0', $lp_map_deprecated_filters[$filter] );
	}

	return $data;
}

function learn_press_text_image( $text = null, $args = array() ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$width      = 200;
	$height     = 150;
	$font_size  = 1;
	$background = 'FFFFFF';
	$color      = '000000';
	$padding    = 20;
	extract( $args );

	// Output to browser
	if ( empty( $_REQUEST['debug'] ) ) {
		header( 'Content-Type: image/png' );
	}
	/*
    $uniqid = md5( serialize( array( 'width' => $width, 'height' => $height, 'text' => $text, 'background' => $background, 'color' => $color ) ) );
    @mkdir( LP_PLUGIN_PATH . '/cache' );
    $cache = LP_PLUGIN_PATH . '/cache/' . $uniqid . '.cache';
    if( file_exists( $cache ) ){
        readfile( $cache );
        die();
    }*/

	$im = imagecreatetruecolor( $width, $height );

	list( $r, $g, $b ) = sscanf( "#{$background}", "#%02x%02x%02x" );
	$background = imagecolorallocate( $im, $r, $g, $b );

	list( $r, $g, $b ) = sscanf( "#{$color}", "#%02x%02x%02x" );
	$color = imagecolorallocate( $im, $r, $g, $b );

	// Set the background to be white
	imagefilledrectangle( $im, 0, 0, $width, $height, $background );

	// Path to our font file
	$font = LP_PLUGIN_PATH . '/assets/fonts/Sumana-Regular.ttf';
	$x    = $width;
	$loop = 0;
	do {
		// First we create our bounding box for the first text
		$bbox = imagettfbbox( $font_size, 0, $font, $text );
		// This is our cordinates for X and Y
		$x = $bbox[0] + ( imagesx( $im ) / 2 ) - ( $bbox[4] / 2 );
		$y = $bbox[1] + ( imagesy( $im ) / 2 ) - ( $bbox[5] / 2 );
		$font_size ++;
		if ( $loop ++ > 100 ) {
			break;
		}
	} while ( $x > $padding );
	// Write it
	imagettftext( $im, $font_size, 0, $x - 5, $y, $color, $font, $text );
	imagepng( $im );
	//readfile( $cache );
	imagedestroy( $im );
}
/**
 * Get all lessons in a course
 *
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_lessons( $course_id ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$lessons    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) {
		foreach ( $curriculum as $lesson_quiz_s ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
				foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
					if ( get_post_type( $lesson_quiz ) == LP()->lesson_post_type ) {
						$lessons[] = $lesson_quiz;
					}
				}
			}
		}
	}

	return $lessons;
}

/**
 * @param $course_id
 *
 * @return array
 */
function learn_press_get_course_quizzes( $course_id ) {
	_deprecated_function( __FUNCTION__, '1.0' );
	$quizzes    = array();
	$curriculum = get_post_meta( $course_id, '_lpr_course_lesson_quiz', true );
	if ( $curriculum ) {
		foreach ( $curriculum as $lesson_quiz_s ) {
			if ( array_key_exists( 'lesson_quiz', $lesson_quiz_s ) ) {
				foreach ( $lesson_quiz_s['lesson_quiz'] as $lesson_quiz ) {
					if ( get_post_type( $lesson_quiz ) == LP()->quiz_post_type ) {
						$quizzes[] = $lesson_quiz;
					}
				}
			}
		}
	}

	return $quizzes;
}