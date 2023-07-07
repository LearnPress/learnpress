<?php
if ( ! isset( $data ) ) {
	return;
}
$value = LP_Helper::sanitize_params_submitted( $_GET['term_id'] ?? [] );
if ( ! empty( $value ) ) {
	$value = explode( ',', $value );
}
do_action( 'learn-press/shortcode/course-filter/course-tag/before', $data );
$terms = get_terms(
	'course_tag',
	apply_filters(
		'learn-press/shortcode/course-filter/course-tag/term-args',
		array(
			'hide_empty' => false,
		)
	)
);

if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
	?>
	<h4><?php esc_html_e( 'Course Tag', 'learnpress' ); ?></h4>
	<ul class="term-list">
		<?php
		foreach ( $terms as $term ) {
			$id = 'course-tag-' . $term->term_id;
			?>
			<li class="term-list__item">
				<div class="term-name">
					<input id="<?php echo esc_attr( $id ); ?>" name="course-tag" type="checkbox"
						value="<?php echo esc_attr( $term->term_id ); ?>"
						<?php checked( true, in_array( $term->term_id, $value ) ); ?>
					>
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $term->name ); ?></label>
				</div>
				<div class="term-course-num">
					<?php echo esc_html( $term->count ); ?>
				</div>
			</li>
			<?php
		}
		?>
	</ul>
	<?php
}

do_action( 'learn-press/shortcode/course-filter/course-tag/after', $data );

