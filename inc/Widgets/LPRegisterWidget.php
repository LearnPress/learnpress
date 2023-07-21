<?php

namespace LearnPress\Widgets;

use LearnPress\Helpers\Singleton;

/**
 * Class AbstractWidget
 *
 * @package LearnPress\Widgets
 * @since 4.2.3.2
 * @version 1.0.0
 */
class LPRegisterWidget {
	use Singleton;

	protected function init() {
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register widgets of LearnPress.
	 *
	 * @return void
	 */
	public function register_widgets() {
		$widgets = apply_filters(
			'learn-press/widgets/register',
			[
				//FilterCourseWidget::class,
			]
		);

		foreach ( $widgets as $widget ) {
			register_widget( $widget );
		}

	}
}

