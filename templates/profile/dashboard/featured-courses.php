<div class="profile-courses featured-courses">
    <h3><?php esc_html_e( 'Featured courses', 'learnpress' ); ?></h3>
    <div class="lp-archive-courses">
        <ul class="learn-press-courses" data-size="3" data-layout="grid">
			<?php
			global $post;
			global $wpdb;
			$courses = get_posts( array( 'post_type' => 'lp_course', 'fields' => 'ids', 'posts_per_page' => 3 ) );
			foreach ( $courses as $item ) {
				$course = learn_press_get_course( $item );
				$post   = get_post( $item );
				setup_postdata( $post );
				learn_press_get_template( 'content-course.php' );
			}
			wp_reset_postdata();
			?>
        </ul>
    </div>
</div>

<div class="profile-courses newest-courses">
    <h3><?php esc_html_e( 'Latest courses', 'learnpress' ); ?></h3>
    <div class="lp-archive-courses">
        <ul class="learn-press-courses" data-size="3" data-layout="grid">
			<?php
			global $post;
			global $wpdb;
			$courses = get_posts( array( 'post_type' => 'lp_course', 'fields' => 'ids', 'posts_per_page' => 3 ) );
			foreach ( $courses as $item ) {
				$course = learn_press_get_course( $item );
				$post   = get_post( $item );
				setup_postdata( $post );
				learn_press_get_template( 'content-course.php' );
			}
			wp_reset_postdata();
			?>
        </ul>
    </div>
</div>