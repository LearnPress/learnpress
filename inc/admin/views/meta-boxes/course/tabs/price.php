<?php
/**
 * Course price data panel.
 *
 * @author ThimPress <nhamdv>
 */

defined( 'ABSPATH' ) || exit;

$payment = get_post_meta( $thepostid, '_lp_payment', true );
?>

<div id="price_course_data" class="lp-meta-box-course-panels">

	<?php if ( current_user_can( LP_TEACHER_ROLE ) || current_user_can( 'administrator' ) ) { ?>
		<?php
		$message    = '';
		$price      = get_post_meta( $thepostid, '_lp_price', true );
		$sale_price = '';
		$start_date = '';
		$end_date   = '';

		if ( $payment != 'free' ) {
			$sale_price = get_post_meta( $thepostid, '_lp_sale_price', true );
			$start_date = get_post_meta( $thepostid, '_lp_sale_start', true );
			$end_date   = get_post_meta( $thepostid, '_lp_sale_end', true );
		}

		do_action( 'learnpress/course-settings/before-price' );

		lp_meta_box_text_input_field(
			array(
				'id'                => '_lp_price',
				'label'             => esc_html__( 'Regular price', 'learnpress' ),
				'description'       => sprintf( __( 'Set a regular price (<strong>%s</strong>). Leave it blank for <strong>Free</strong>.', 'learnpress' ), learn_press_get_currency() ),
				'type'              => 'number',
				'default'           => $price,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '0.01',
				),
				'style'             => 'width: 80px;',
				'class'             => 'lp_meta_box_regular_price',
			)
		);

		lp_meta_box_text_input_field(
			array(
				'id'                => '_lp_sale_price',
				'label'             => esc_html__( 'Sale price', 'learnpress' ),
				'description'       => '<a href="#" class="lp_sale_price_schedule">' . esc_html__( 'Schedule', 'learnpress' ) . '</a>',
				'type'              => 'number',
				'default'           => $sale_price,
				'custom_attributes' => array(
					'min'  => '0',
					'step' => '0.01',
				),
				'style'             => 'width: 80px;',
				'class'             => 'lp_meta_box_sale_price',
			)
		);
		?>

		<div class="lp_sale_dates_fields">
			<p class="form-field lp_sale_start_dates_fields">
				<label for="_lp_sale_start"><?php esc_html_e( 'Sale start dates', 'learnpress' ); ?></label>
				<input type="text" class="short" name="_lp_sale_start" id="_lp_sale_start" value="<?php echo esc_attr( $start_date ); ?>" placeholder="<?php echo esc_html( _x( 'From&hellip;', 'placeholder', 'learnpress' ) ); ?>" style="width:320px;" />
			</p>
			<p class="form-field lp_sale_end_dates_fields">
				<label for="_lp_sale_start"><?php esc_html_e( 'Sale end dates', 'learnpress' ); ?></label>
				<input type="text" class="short" name="_lp_sale_end" id="_lp_sale_end" value="<?php echo esc_attr( $end_date ); ?>" placeholder="<?php echo esc_html( _x( 'To&hellip;', 'placeholder', 'learnpress' ) ); ?>" style="width:320px;" />
				<a href="#" class="description lp_cancel_sale_schedule"><?php esc_html_e( 'Cancel', 'learnpress' ); ?></a>
			</p>
		</div>

		<?php do_action( 'learnpress/course-settings/after-price' ); ?>

	<?php } else { ?>
		<p><?php esc_html_e( 'Price set by admin', 'learnpress' ); ?></p>
	<?php } ?>
</div>
