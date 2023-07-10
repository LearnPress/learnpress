<?php
if ( ! isset( $data ) ) {
	return;
}
$value = LP_Helper::sanitize_params_submitted( $_GET['c_level'] ?? [] );
if ( ! empty( $value ) ) {
	$value = explode( ',', $value );
}
do_action( 'learn-press/shortcode/course-filter/level/before', $data );
?>
	<h4><?php esc_html_e( 'Level', 'learnpress' ); ?></h4>
<?php
$levels = lp_course_level();
if ( ! empty( $levels ) ) {
	?>
	<ul class="level">
		<?php
		foreach ( $levels as $name => $label ) {
			if ( $name == '' ) {
				$name = 'all';
			}

			$id = 'lp-level-' . $name;
			?>
			<li class="level__item">
				<div class="level-name">
					<input id="<?php echo esc_attr( $id ); ?>" name="level" type="checkbox"
						value="<?php echo esc_attr( $name ); ?>"
						<?php checked( true, in_array( $name, $value ) ); ?>
					>
					<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
				</div>
				<div class="level-course-num">
					<?php
					if ( $name === 'all' ) {
						$num = $data['all_level_number'];
					} elseif ( $name === 'beginner' ) {
						$num = $data['beginner_number'];
					} elseif ( $name === 'intermediate' ) {
						$num = $data['intermediate_number'];
					} else {
						$num = $data['expert_number'];
					}
					echo esc_html( $num );
					?>
				</div>
			</li>
			<?php
		}
		?>
	</ul>
	<?php
}
do_action( 'learn-press/shortcode/course-filter/level/after', $data );

