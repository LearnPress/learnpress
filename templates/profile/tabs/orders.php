<?php
/**
 * Template for displaying user's orders
 *
 * @author  ThimPress
 * @package LearnPress/Template
 * @version x.x
 */
defined( 'ABSPATH' ) || exit();

$user_id = learn_press_get_current_user_id();
$page    = get_query_var( 'paged', 1 );
$limit   = 10;

if ( $orders = _learn_press_get_user_profile_orders( $user_id, $page, $limit ) ):
	if ( empty( $orders['rows'] ) ) {
		$orders['rows'] = array();
	}
	if ( $orders['rows'] ) :
		?>
        <table class="table-orders">
            <thead>
            <tr>
                <th><?php _e( 'Order', 'learnpress' ); ?></th>
                <th><?php _e( 'Date', 'learnpress' ); ?></th>
                <th><?php _e( 'Status', 'learnpress' ); ?></th>
                <th><?php _e( 'Total', 'learnpress' ); ?></th>
                <th><?php _e( 'Action', 'learnpress' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $orders['rows'] as $order ): $order = learn_press_get_order( $order ); ?>
                <tr>
                    <td><?php echo $order->get_order_number(); ?></td>
                    <td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></td>
                    <td>
						<?php echo $order->get_order_status_html(); ?>
						<?php
						if ( $order->has_status( 'pending' ) ) :
							printf( '(<small><a href="%s" class="%s">%s</a></small>)', $order->get_cancel_order_url(), 'cancel-order', __( 'Cancel', 'learnpress' ) );
						endif;
						?>
                    </td>
                    <td><?php echo $order->get_formatted_order_total(); ?></td>
                    <td>
						<?php
						$actions['view'] = array(
							'url'  => $order->get_view_order_url(),
							'text' => __( 'View', 'learnpress' )
						);
						$actions         = apply_filters( 'learn_press_user_profile_order_actions', $actions, $order );

						foreach ( $actions as $slug => $option ) {
							printf( '<a href="%s">%s</a>', $option['url'], $option['text'] );
						}
						?>
                    </td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>

		<?php
		learn_press_paging_nav( array(
			'num_pages' => $orders['num_pages'],
			'base'      => learn_press_user_profile_link( $user_id, LP()->settings->get( 'profile_endpoints.profile-orders' ) )
		) );
		?>

	<?php else: ?>
		<?php learn_press_display_message( __( 'You have not got any orders yet!', 'learnpress' ) ); ?>
	<?php endif; ?>

<?php else: ?>
	<?php learn_press_display_message( __( 'You have not got any orders yet!', 'learnpress' ) ); ?>
<?php endif; ?>
