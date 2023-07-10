<?php
if ( ! isset( $data ) ) {
	return;
}
$value = LP_Helper::sanitize_params_submitted( $_GET['c_authors'] ?? [] );
if ( ! empty( $value ) ) {
	$value = explode( ',', $value );
}

do_action( 'learn-press/shortcode/course-filter/instructor/before', $data );
?>
	<h4><?php esc_html_e( 'Instructor', 'learnpress' ); ?></h4>
<?php
$instructors = get_users(
	array(
		'number'   => - 1,
		'role__in' => [ 'lp_teacher', 'administrator' ],
	)
);

if ( ! empty( $instructors ) ) {
	?>
	<ul class="instructor">
		<?php
		foreach ( $instructors as $instructor ) {
			$data          = $instructor->data;
			$id            = 'lp-instructor-' . $data->ID;
			$course_number = count_user_posts( $data->ID, LP_COURSE_CPT, true );
			?>
			<li class="instructor__item">
				<div class="instructor-name">
					<input id="<?php echo esc_attr( $id ); ?>" name="instructor" type="checkbox"
						value="<?php echo esc_attr( $data->ID ); ?>"
						<?php checked( true, in_array( $data->ID, $value ) ); ?>
					>
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $data->display_name ); ?></label>
				</div>
				<div class="instructor-course-num">
					<?php echo esc_html( $course_number ); ?>
				</div>
			</li>
			<?php
		}
		?>
	</ul>
	<?php
}
do_action( 'learn-press/shortcode/course-filter/instructor/after', $data );
