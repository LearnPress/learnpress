<?php
$instance = $this->instance;
?>
<div class="<?php echo 'archive-course-widget-outer ' . esc_attr($instance["css_class"]);  ?>">
	<div class="widget-body">
		<?php foreach ( $this->courses as $course ): ?>
			<div class="course-entry">
				<?php if ( ! empty( $instance['show_thumbnail'] ) ): ?>
					<div class="course-cover">
						<a href="<?php echo get_the_permalink( $course->id ); ?>">
							<?php echo get_the_post_thumbnail( $course->id, 'medium' ); ?>
						</a>
					</div>
				<?php endif; ?>
				<div class="course-detail">
					<a href="<?php echo get_the_permalink( $course->id ) ?>">
						<h3 class="course-title">
							<?php echo $course->get_course_data()->post_title; ?>
						</h3>
					</a>
					<?php if ( ! empty( $instance['desc_length'] ) && intval( $instance['desc_length'] ) > 0 ): ?>
						<div class="course-description"><?php
							$content_length = intval( $instance['desc_length'] );
							$the_content    = $course->get_course_data()->post_content;
							$the_content    = wp_trim_words( $the_content, $content_length, __( '...', 'learnpress' ) );
							echo $the_content;
							?></div>
					<?php endif; ?>
					<div class="course-meta-data">
						<?php if ( ! empty( $instance['show_price'] ) ): ?>
							<div class="course-meta-field">
								<?php
								echo $course->get_price_html();
								?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $instance['show_enrolled_students'] ) ): ?>
							<div class="course-student-number course-meta-field">
								<?php
								$students = $course->get_users_enrolled();
								echo intval( $students ) > 1 ? sprintf( __( '%d students', 'learnpress' ), $students ) : sprintf( __( '%d student', 'learnpress' ), $students );
								?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $instance['show_lesson'] ) ): ?>
							<div class="course-lesson-number course-meta-field">
								<?php
								$lessons = sizeof( $course->get_lessons() );
								echo intval( $lessons ) > 1 ? sprintf( __( '%d lessons', 'learnpress' ), $lessons ) : sprintf( __( '%d lesson', 'learnpress' ), $lessons );
								?>
							</div>
						<?php endif; ?>
						<?php if ( ! empty( $instance['show_teacher'] ) ): ?>
							<div class="course-meta-field">
								<?php echo $course->get_instructor_html(); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="widget-footer">
		<?php if ( !empty($instance['bottom_link_text'])):
			$page_id = get_option( 'learn_press_courses_page_id' );
			$link = get_page_link( $page_id );
			$title = get_the_title( $page_id );
			?>
			<a class="pull-right" href="<?php echo $link ?>">
				<?php echo wp_kses_post($instance['bottom_link_text']) ; ?>
			</a>
		<?php endif; ?>
	</div>
</div>
<div class="clearfix"></div>