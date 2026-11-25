<?php

namespace LearnPress\Gutenberg\Blocks\SingleInstructorElements;

use LearnPress\Gutenberg\Blocks\SingleInstructorElements\AbstractSingleInstructorBlockType;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;
use LP_Debug;
use Throwable;

/**
 * Class InstructorBackgroundBlockType
 *
 * Handle register, render block template
 */
class InstructorBackgroundBlockType extends AbstractSingleInstructorBlockType {
	public $block_name = 'instructor-background';

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [
				'fontSize'                    => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
			],
			'spacing'    => [
				'padding' => true,
				'margin'  => true,
			],
		];
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();
			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return $html;
			}

			$this->enqueue_assets();
			$this->get_class_hash();
			$this->inline_styles( $attributes );

			ob_start();
			echo ProfileTemplate::instance()->html_cover_image( $instructor );
			$html_background = ob_get_clean();

			$html = $this->get_output_with_class_hash( $attributes, $html_background, );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	public function get_inline_style( $attributes ) {
		$size     = $this->sanitize_css_value( $attributes['size'] ?? '', 'cover' );
		$position = $this->sanitize_css_value( $attributes['position'] ?? '', 'center' );
		$repeat   = ! empty( $attributes['repeat'] ) ? 'repeat' : 'no-repeat';

		return sprintf(
			'.%s .lp-user-cover-image_background {background-size: %s;background-position: %s;background-repeat: %s;}',
			sanitize_html_class( $this->class_hash ),
			$size,
			$position,
			$repeat
		);
	}

	/**
	 * Only allow simple CSS values (keywords, numbers, units, percent) to prevent CSS injection.
	 *
	 * @param mixed  $value
	 * @param string $default_value
	 *
	 * @return string
	 * @since 4.5.1
	 */
	protected function sanitize_css_value( $value, string $default_value ): string {
		if ( ! is_string( $value ) ) {
			return $default_value;
		}

		$value = trim( $value );
		if ( '' === $value || ! preg_match( '/^[a-zA-Z0-9.%\s-]+$/', $value ) ) {
			return $default_value;
		}

		return $value;
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}
