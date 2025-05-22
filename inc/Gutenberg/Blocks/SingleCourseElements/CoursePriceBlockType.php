<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CoursePriceInfoBlockType
 *
 * Handle register, render block template
 */
class CoursePriceBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-price';

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
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			$userModel = $this->get_user();
			if ( $userModel instanceof UserModel ) {
				$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
				if ( $userCourseModel ) {
					return $html;
				}
			}

			$html_price = SingleCourseTemplate::instance()->html_price( $courseModel );
			if ( empty( $html_price ) ) {
				return $html;
			}

			$html = $this->get_output( $html_price );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}
