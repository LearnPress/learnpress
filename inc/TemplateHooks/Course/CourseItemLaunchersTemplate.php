<?php
/**
 * Template hook: shared wrapper for the launcher buttons of learning tools
 * (AI Assistant, Student Notes...) on the course item page.
 *
 * Tools print their button on the HOOK action; the wrapper is only output when at least one did.
 *
 * @since 4.4.9.2
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;

defined( 'ABSPATH' ) || exit;

class CourseItemLaunchersTemplate {
	use Singleton;

	/**
	 * Action where learning tools print their launcher button.
	 */
	const HOOK = 'learn-press/course-item-footer-launchers';

	public function init() {
		add_action( 'wp_footer', array( $this, 'render' ), 5 );
	}

	/**
	 * Render the launchers wrapper.
	 */
	public function render() {
		ob_start();
		do_action( self::HOOK );
		$launchers_html = trim( ob_get_clean() );

		if ( '' === $launchers_html ) {
			return;
		}

		printf(
			'<div class="lp-footer-launchers" aria-label="%1$s">%2$s</div>',
			esc_attr__( 'Learning tools', 'learnpress' ),
			$launchers_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}
