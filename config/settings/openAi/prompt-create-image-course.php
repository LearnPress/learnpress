<?php
/**
 * Prompt to create a course
 */
if ( ! isset( $params ) ) {
	return;
}

// Course Intent
$topic    = $params['topic'] ?? '';
$title    = wp_trim_words( $params['post-title'] ?? '', 800 );
$outputs  = $params['outputs'] ?? 1;
$language = $params['language'] ?? 'English';
$quality  = $params['quality'] ?? 'auto';
$size     = $params['size'] ?? '256x256';
$style    = $params['style'] ?? 'Impressionism';


/**
 *A text description of the desired image(s).
 * The maximum length is 32000 characters for gpt-image-1,
 * 1000 characters for dall-e-2
 * and 4000 characters for dall-e-3.
 */

return <<<PROMPT
Create a feature image for an online course. With the following details:
The main subject of the image must be: "$topic".
The image should be inspired by the course title: "$title".
Ensure the final image is $quality quality and fits a $size aspect ratio, suitable for a website feature product.
The desired artistic style is: $style.

Must return exactly $outputs results image(s).
PROMPT;
