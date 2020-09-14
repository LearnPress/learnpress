<?php
/**
 * Template for displaying outdated template files in theme/child-themes
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<table class="form-table">
	<tr>
		<th><?php esc_attr_e( 'Cron url', 'learnpress' ); ?></th>
		<td>
			<input type="text" class="widefat" value="<?php echo esc_attr( learn_press_get_cron_url() ); ?>">
			<p class="description"><?php esc_html_e( 'Use this url to setup cronjob on your server.', 'learnpress' ); ?></p>
			<br/>
			<a class="button" href="<?php echo wp_nonce_url( add_query_arg( 'generate-cron-url', '1' ) ); ?>"><?php esc_html_e( 'Generate new url', 'learnpress' ); ?></a>
		</td>
	</tr>

<!--    <tr>-->
<!--        <th>--><?php // esc_attr_e( 'Schedule', 'learnpress' ); ?><!--</th>-->
<!--        <td>-->
<!--            <input type="text" class="widefat" value="--><?php // echo esc_attr( learn_press_get_cron_url() ); ?><!--">-->
<!--            <p class="description">--><?php // esc_html_e( 'Enable .', 'learnpress' ); ?><!--</p>-->
<!--            <br/>-->
<!--            <a class="button"-->
<!--               href="--><?php // echo wp_nonce_url( add_query_arg( 'generate-cron-url', '1' ) ); ?><!--">--><?php // esc_html_e( 'Generate new url', 'learnpress' ); ?><!--</a>-->
<!--        </td>-->
<!--    </tr>-->
</table>
