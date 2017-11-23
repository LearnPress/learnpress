<?php
/**
 * Template for displaying content of Recent Courses widget.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/widgets/recent-courses/default.php.
 *
 * @author  ThimPress
 * @category Widgets
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

//widget instance
$instance = $this->instance;
?>

<div class="archive-course-widget-outer <?php esc_attr_e( $instance["css_class"] ); ?>">

    <div class="widget-body">
		<?php foreach ( $this->courses as $course ) { ?>

			<?php $data = array(
				'id'         => $course->get_id(),
				'title'      => $course->get_title(),
				'content'    => $course->get_data( 'post_content' ),
				'price'      => $course->get_price_html(),
				'students'   => $course->get_users_enrolled(),
				'lessons'    => sizeof( $course->get_items( LP_LESSON_CPT ) ),
				'instructor' => $course->get_instructor_html(),
			); ?>

            <div class="course-entry">
                <!-- course thumbnail -->
				<?php if ( ! empty( $instance['show_thumbnail'] ) ) { ?>
                    <div class="course-cover">
                        <a href="<?php echo get_the_permalink( $data['id'] ); ?>">
							<?php echo get_the_post_thumbnail( $data['id'], 'medium' ); ?>
                        </a>
                    </div>
				<?php } ?>
                <div class="course-detail">
                    <!-- course title -->
                    <a href="<?php echo get_the_permalink( $data['id'] ) ?>">
                        <h3 class="course-title"><?php echo $data['title']; ?></h3>
                    </a>
                    <!-- course content -->
					<?php if ( ! empty( $instance['desc_length'] ) && intval( $instance['desc_length'] ) > 0 ) { ?>
                        <div class="course-description">
							<?php $content_length = intval( $instance['desc_length'] ); ?>
							<?php echo wp_trim_words( $data['content'], $content_length, __( '...', 'learnpress' ) ); ?></div>
					<?php } ?>
                    <div class="course-meta-data">
                        <!-- price -->
						<?php if ( ! empty( $instance['show_price'] ) ) { ?>
                            <div class="course-meta-field"><?php echo $data['price']; ?></div>
						<?php } ?>
                        <!-- number students -->
						<?php if ( ! empty( $instance['show_enrolled_students'] ) ) { ?>
                            <div class="course-student-number course-meta-field">
								<?php echo intval( $data['students'] ) > 1 ? sprintf( __( '%d students', 'learnpress' ), $data['students'] ) : sprintf( __( '%d student', 'learnpress' ), $data['students'] ); ?>
                            </div>
						<?php } ?>
                        <!-- number lessons -->
						<?php if ( ! empty( $instance['show_lesson'] ) ) { ?>
                            <div class="course-lesson-number course-meta-field">
								<?php echo intval( $data['lessons'] ) > 1 ? sprintf( __( '%d lessons', 'learnpress' ), $data['lessons'] ) : sprintf( __( '%d lesson', 'learnpress' ), $data['lessons'] ); ?>
                            </div>
						<?php } ?>
                        <!-- instructor -->
						<?php if ( ! empty( $instance['show_teacher'] ) ) { ?>
                            <div class="course-meta-field"><?php echo $data['instructor']; ?></div>
						<?php } ?>
                    </div>
                </div>
            </div>
		<?php } ?>
    </div>
    <div class="widget-footer">
		<?php if ( ! empty( $instance['bottom_link_text'] ) ) {
			$page_id = get_option( 'learn_press_courses_page_id' );
			$link    = get_the_permalink( $page_id );
			$title   = get_the_title( $page_id );
			?>
            <a class="pull-right" href="<?php echo esc_url( $link ); ?>">
				<?php echo wp_kses_post( $instance['bottom_link_text'] ); ?>
            </a>
		<?php } ?>
    </div>
</div>