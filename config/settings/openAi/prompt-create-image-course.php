<?php
/**
 * Prompt to create a course
 */
if ( ! isset( $params ) ) {
	return;
}

$model_type = LP_Settings::get_option( 'open_ai_image_model_type', 'gpt-image-1' );

// Course Intent
$title    = wp_trim_words( $params['post-title'] ?? '', 100 );
$language = $params['language'] ?? 'English';
$quality  = $params['quality'] ?? 'auto';
//$size     = $params['size'] ?? '256x256';
//$outputs  = $params['outputs'] ?? 1;
$style = $params['style'] ?? 'Impressionism';
$goal  = wp_trim_words( $params['goal'] ?? '', 850 );

$main_subject = ! empty( $goal ) ? "The main subject of the image must be: $goal." : '';
$title        = ! empty( $title ) ? "The image should be inspired by the course title: $title." : '';

/**
 *A text description of the desired image(s).
 * The maximum length is 32000 characters for gpt-image-1,
 * 1000 characters for dall-e-2
 * and 4000 characters for dall-e-3.
 */

return <<<PROMPT
Create a feature image for an online course. With the following details:
$main_subject
$title

The desired artistic style is: $style.
PROMPT;
