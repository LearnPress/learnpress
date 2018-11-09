<?php
/**
 * Template for display order status in WP Dashboard
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.10
 */

defined( 'ABSPATH' ) or die();

$dashboard         = new LP_Admin_Dashboard();
$order_statuses    = learn_press_get_order_statuses( true, true );
$eduma_data        = $dashboard->get_theme_info( 14058034 );
$specific_statuses = array( 'lp-completed', 'lp-failed', 'lp-on-hold' );

foreach ( $order_statuses as $status ) {
	if ( ! in_array( $status, $specific_statuses ) ) {
		$specific_statuses[] = $status;
	}
}

$counts = learn_press_count_orders( array( 'status' => $specific_statuses ) );
?>
<ul class="lp-order-statuses">
    <li class="count-number total-raised">
        <strong><?php echo $dashboard->get_order_total_raised(); ?></strong>
        <p><?php _e( 'Total Raised', 'learnpress' ); ?></p>
    </li>
	<?php foreach ( $specific_statuses as $status ) : ?>
		<?php
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
						<?php if ( $count ) {
							printf( translate_nooped_plural( _n_noop( '%d order', '%d orders' ), $count, 'learnpress' ), $count );
						} else {
							printf( __( '%d order', 'learnpress' ), 0 );
						} ?>
                    </strong>
                    <p><?php printf( '%s', $status_object->label ); ?></p>
                </a>
            </div>
        </li>
	<?php endforeach; ?>
    <li class="clear"></li>
    <li class="featured-theme">
        <p>
            <a href="<?php echo esc_url( $eduma_data['item']['url'] ) ?>">
				<?php echo esc_html( $eduma_data['item']['item'] ) ?>
            </a> - <?php printf( '%s%s', '$', $eduma_data['item']['cost'] ) ?>
        </p>
        <p>
			<?php _e( 'Created by: ', 'learnpress' ) ?>
            <a href="https://thimpress.com/"
               class="author"><?php echo esc_html( $eduma_data['item']['user'] ); ?></a>
        </p>
    </li>
</ul>