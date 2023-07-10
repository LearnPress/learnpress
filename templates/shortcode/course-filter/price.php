<?php
if ( ! isset( $data ) ) {
	return;
}
$value = LP_Helper::sanitize_params_submitted( $_GET['c_price'] ?? [] );
if ( ! empty( $value ) ) {
	$value = explode( ',', $value );
}

do_action( 'learn-press/shortcode/course-filter/price/before', $data );
?>
	<h4><?php esc_html_e( 'Price', 'learnpress' ); ?></h4>
	<ul class="price">
		<li class="price__item">
			<div class="price-name">
				<input id="lp-price-free" name="price" type="checkbox"
					value="on_free" <?php checked( true, in_array( 'free', $value ) ); ?>>
				<label for="lp-price-free"><?php esc_html_e( 'Free', 'learnpress' ); ?></label>
			</div>
			<div class="price-course-num">
				<?php echo esc_html( $data['free_course_number'] ); ?>
			</div>
		</li>
		<li class="price__item">
			<div class="price-name">
				<input id="lp-price-paid" name="price" type="checkbox"
					value="on_paid" <?php checked( true, in_array( 'paid', $value ) ); ?>>
				<label for="lp-price-paid"><?php esc_html_e( 'Paid', 'learnpress' ); ?></label>
			</div>
			<div class="price-course-num">
				<?php echo esc_html( $data['paid_course_number'] ); ?>
			</div>
		</li>
	</ul>

<?php
do_action( 'learn-press/shortcode/course-filter/price/after', $data );


