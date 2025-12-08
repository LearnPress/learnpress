<?php
/**
 * Template for displaying order detail.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/order-received.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.4
 */

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;

defined( 'ABSPATH' ) || exit();

$user_id   = get_current_user_id();
$lp_profile = learn_press_get_profile( $user_id );
$userModel = UserModel::find( $user_id, true );
?>
<div class="lp-content-area">
	<?php
	/**
	 * @var LP_Order $order_received
	 */
	if ( ! isset( $order_received ) ) {
		echo wp_sprintf(
			'<p>%s</p>',
			esc_html(
				apply_filters(
					'learn-press/order/received-invalid-order-message',
					__( 'Invalid order.', 'learnpress' )
				)
			)
		);

		return;
	}

	echo wp_sprintf(
		'<p>%s</p><p>%s</p>',
		esc_html(
			apply_filters(
				'learn-press/order/received-order-message',
				__( 'Thank you. Your order has been received.', 'learnpress' )
			)
		),
		sprintf(
			__( 'Go to %1$s to continue browsing or go to %2$s', 'learnpress' ),
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( learn_press_get_page_link( 'courses' ) ),
				esc_html__( 'Courses', 'learnpress' )
			),
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( $lp_profile->get_tab_link( 'orders' ) ),
				esc_html__( 'My Orders', 'learnpress' )
			)
		)
	);
	do_action( 'learn-press/order/after-received-order-message', $order_received->get_id() );

	?>
	<table class="order_details">
		<?php if ( isset( $_GET['key'] ) && ! is_user_logged_in() ) : ?>
			<tr class="order-key">
				<th><?php esc_html_e( 'Order Key', 'learnpress' ); ?></th>
				<td>
					<?php echo esc_html( $_GET['key'] ); ?>
				</td>
			</tr>
		<?php endif; ?>
		<tr class="order">
			<th><?php esc_html_e( 'Order Number', 'learnpress' ); ?></th>
			<td>
				<?php echo esc_html( $order_received->get_order_number() ); ?>
			</td>
		</tr>
		<tr class="status">
			<th><?php esc_html_e( 'Status', 'learnpress' ); ?></th>
			<td>
				<?php
				$status = $order_received->get_status();
				echo ucfirst( $order_received::get_status_label( $status ) );
				?>
			</td>
		</tr>
		<tr class="item">
			<th><?php esc_html_e( 'Item', 'learnpress' ); ?></th>
			<td>
				<?php
				$links = array();
				$items = $order_received->get_items();
				$count = count( $items );

				foreach ( $items as $item ) {
					if ( empty( $item['course_id'] ) || get_post_type( $item['course_id'] ) !== LP_COURSE_CPT ) {
						$links[] = apply_filters(
							'learn-press/order-item-not-course-id',
							__( 'The course does not exist', 'learnpress' ),
							$item
						);
					} else {
						$course_id = $item['course_id'];
						$courseModel = CourseModel::find( $course_id, true );
						$button_course = '';
						$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
						if ( $userCourseModel && $userModel ) {
							// For enrolled or purchased course.
							$userCourseTemplate = UserCourseTemplate::instance();
							$singleCourseTemplate = SingleCourseTemplate::instance();

							// Load js button course.
							wp_enqueue_script( 'lp-single-course' );

							if ( $userCourseModel->has_enrolled() ) {
								$button_course = $userCourseTemplate->html_btn_continue( $userCourseModel );
							} elseif ( $userCourseModel->has_purchased() ) {
								$button_course = $singleCourseTemplate->html_btn_enroll_course( $courseModel, $userModel );
							}
						}

						$link = sprintf(
							'<a href="%s">%s (#%s)</a> %s',
							get_the_permalink( $item['course_id'] ),
							get_the_title( $item['course_id'] ),
							$item['course_id'],
							$button_course
						);

						if ( $count > 1 ) {
							$link = sprintf( '<li>%s</li>', $link );
						}
						$links[] = apply_filters( 'learn-press/order-received-item-link', $link, $item );
					}
				}

				if ( $count > 1 ) {
					echo sprintf( '<ol>%s</ol>', join( '', $links ) );
				} elseif ( 1 == $count ) {
					echo implode( '', $links );
				} else {
					echo esc_html__( '(No item)', 'learnpress' );
				}
				?>
			</td>
		</tr>
		<tr class="date">
			<th><?php esc_html_e( 'Date', 'learnpress' ); ?></th>
			<td>
				<?php
				echo wp_kses_post(
					date_i18n(
						get_option( 'date_format' ),
						strtotime( $order_received->get_order_date() )
					)
				);
				?>
			</td>
		</tr>
		<tr class="total">
			<th><?php esc_html_e( 'Total', 'learnpress' ); ?></th>
			<td>
				<?php echo wp_kses_post( $order_received->get_formatted_order_total() ); ?>
			</td>
		</tr>
		<?php
		$method_title = $order_received->get_payment_method_title();
		if ( $method_title ) :
			?>
			<tr class="method">
				<th><?php esc_html_e( 'Payment Method', 'learnpress' ); ?></th>
				<td>
					<?php echo esc_html( $method_title ); ?>
				</td>
			</tr>
		<?php endif; ?>
		<?php do_action( 'learn-press/order/received/items-table', $order_received ); ?>
	</table>

	<?php do_action( 'learn-press/order/received', $order_received ); ?>
</div>
