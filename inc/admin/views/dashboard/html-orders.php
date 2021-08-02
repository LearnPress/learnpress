<?php
/**
 * Template for displaying orders dashboard in wp-admin
 *
 * @author  ThimPress
 * @package LearnPress/Inc/Admin/Views/
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $specific_statuses ) ) {
	return;
}
?>

<li class="count-number total-raised">
	<strong><?php echo learn_press_get_total_price_order_complete(); ?></strong>
	<p><?php esc_html_e( 'Total Raised', 'learnpress' ); ?></p>
</li>

<?php

$counts = learn_press_count_orders( array( 'status' => $specific_statuses ) );

foreach ( $specific_statuses as $status ) :

	$status_object = get_post_status_object( $status );

	if ( ! $status_object ) {
		continue;
	}

	$count = $counts[ $status ];
	$url   = $count ? admin_url( 'edit.php?post_type=' . LP_ORDER_CPT . '&post_status=' . $status ) : '#';
	?>

	<li class="counter-number order-<?php echo str_replace( 'lp-', '', $status ); ?>">
		<div class="counter-inner">
			<a href="<?php echo esc_url( $url ); ?>">
				<strong>
					<?php
					if ( $count ) {
						printf( translate_nooped_plural( _n_noop( '%d order', '%d orders' ), $count, 'learnpress' ), $count );
					} else {
						printf( __( '%d order', 'learnpress' ), 0 );
					}
					?>
				</strong>
				<p><?php printf( '%s', $status_object->label ); ?></p>
			</a>
		</div>
	</li>
<?php endforeach; ?>
