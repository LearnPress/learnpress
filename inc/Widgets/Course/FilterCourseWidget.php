<?php

namespace LearnPress\Widgets\Course;
use LearnPress\Widgets\LPWidgetBase;

/**
 * Class AbstractWidget
 *
 * @package LearnPress\Widgets
 * @since 4.2.3.2
 * @version 1.0.0
 */
class FilterCourseWidget extends LPWidgetBase {
	public $lp_widget_id    = 'course_filter';
	public $lp_widget_class = 'lp-widget-course-filter';

	public function __construct() {
		$this->lp_widget_name        = __( 'LearnPress - Course Filter', 'learnpress' );
		$this->lp_widget_description = __( 'Widget Course Filter', 'learnpress' );
		parent::__construct();
	}

	public function widget( $args, $instance ) {
		$title   = $instance['title'] ?? '';
		$content = $instance['content'] ?? '';

		echo $args['before_widget'];
		echo '<h2 class="widget-title">' . $title . '</h2>';
		echo '<p>' . $content . '</p>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title   = esc_attr( $instance['title'] ?? '' );
		$content = esc_textarea( $instance['content'] ?? '' );

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
			<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id( 'title' ); ?>" value="<?php echo $title; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'content' ); ?>">Content:</label>
			<textarea name="<?php echo $this->get_field_name( 'content' ); ?>" id="<?php echo $this->get_field_id( 'content' ); ?>" class="widefat"><?php echo $content; ?></textarea>
		</p>
		<?php
	}
}

