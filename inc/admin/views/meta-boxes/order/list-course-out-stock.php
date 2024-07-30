<?php
if ( empty( $items_out_stock ) ) {
	return;
}
use LearnPress\Models\CourseModel;

$course_ids = explode( ',', $items_out_stock );
?>
<div class="lp-course-sold-out">
	<p class="lp-course-sold-out__title" style="font-style: italic; font-weight:bolder; color: darkred; font-size: 1rem;">
		<?php esc_html_e( 'List course out stock in order', 'learnpress' ); ?>
	</p>
	<ul class="lp-course-sold-out__list">
		<?php foreach ( $course_ids as $course_id ) : ?>
			<?php
			$course = CourseModel::find( $course_id );
			if ( empty( $course ) ) {
				continue;
			}
			?>

			<li class="lp-course-sold-out__item">
				<a href="<?php echo esc_url_raw( $course->get_permalink() ); ?>">
					<?php echo wp_kses_post( $course->post_title ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
