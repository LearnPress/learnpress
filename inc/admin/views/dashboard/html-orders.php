<?php
/**
 * Template for displaying statistic orders dashboard in wp-admin
 *
 * @author  ThimPress
 * @package LearnPress/Inc/Admin/Views/
 * @version 4.0.1
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $order_statuses ) || ! isset( $lp_order_icons ) ) {
	return;
}
?>

<li class="count-number total-raised">
	<span>
		<i class="dashicons dashicons-chart-bar"></i>
		<?php echo learn_press_get_total_price_order_complete(); ?></span>
	<p><?php esc_html_e( 'Total Raised', 'learnpress' ); ?></p>
</li>

<?php
$counts = learn_press_count_orders();
foreach ( $order_statuses as $key => $status ) :
	$count = $counts[ $key ];
	$url   = $count ? admin_url( 'edit.php?post_type=' . LP_ORDER_CPT . '&post_status=' . $key ) : '#';
	?>

	<li class="counter-number order-<?php echo $status; ?>">
		<div class="counter-inner">
			<a href="<?php echo esc_url_raw( $url ); ?>">
				<span>
					<?php
					if ( $count ) {
						printf( translate_nooped_plural( _n_noop( '%d order', '%d orders' ), $count, 'learnpress' ), $count );
					} else {
						printf( __( '%d order', 'learnpress' ), 0 );
					}
					//echo $lp_order_icons[ $status ];
					?>
				</span>
				<p><?php printf( '%s', LP_Order::get_status_label( $status ) ); ?></p>
			</a>
		</div>
	</li>
<?php endforeach; ?>
