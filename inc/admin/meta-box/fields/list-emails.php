<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_List_Emails_Field' ) ) {
	class RWMB_List_Emails_Field extends RWMB_Field {
		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param mixed $field
		 *
		 * @return string
		 */
		public static function html( $meta, $field = '' ) {
			$emails = LP_Emails::instance()->emails;
			ob_start();
			?>
            <table class="learn-press-emails">
                <thead>
                <tr>
                    <th><?php _e( 'Email', 'learnpress' ); ?></th>
                    <th><?php _e( 'Description', 'learnpress' ); ?></th>
                    <th class="status"><?php _e( 'Status', 'learnpress' ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ( $emails as $email ) {
					$group = '';
					if ( $email->group ) {
						$url = esc_url( add_query_arg( array(
							'section'     => $email->group->group_id,
							'sub-section' => $email->id
						), admin_url( 'admin.php?page=learn-press-settings&tab=emails' ) ) );

						$group = $email->group;
					} else {
						$url = esc_url( add_query_arg( array( 'section' => $email->id ), admin_url( 'admin.php?page=learn-press-settings&tab=emails' ) ) );
					} ?>
                    <tr id="email-<?php echo $email->id; ?>">
                        <td class="name">
                            <a href="<?php echo $url; ?>">
								<?php
								if ( $group ) {
									echo join( ' &rarr; ', array(
										$group,
										$email->title
									) );
								} else {
									echo $email->title;
								} ?></a>
                        </td>
                        <td class="description"><?php echo $email->description; ?></td>
                        <td class="status<?php echo $email->enable ? ' enabled' : ''; ?>">
                            <span class="change-email-status dashicons dashicons-yes"
                                  data-status="<?php echo $email->enable ? 'on' : 'off'; ?>"
                                  data-id="<?php echo $email->id; ?>"></span>
                        </td>
                    </tr>
				<?php } ?>
                </tbody>
            </table>
            <p class="email-actions">
				<?php learn_press_quick_tip( __( 'You can enable/disable each email by clicking on the status icon or apply status for all emails by clicking these buttons', 'learnpress' ) ); ?>
                <button class="button"
                        id="learn-press-enable-emails"
                        data-status="yes"><?php _e( 'Enable all', 'learnpress' ); ?></button>
                <button class="button"
                        id="learn-press-disable-emails"
                        data-status="no"><?php _e( 'Disable all', 'learnpress' ); ?></button>
            </p>
			<?php
			return ob_get_clean();
		}
	}
}