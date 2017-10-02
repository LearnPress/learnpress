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
				<?php foreach ( $emails as $email ) { ?>
                    <tr>
                        <td class="name">
							<?php if ( $email->group ) { ?>
                                <a href="<?php echo esc_url( add_query_arg( array('section'=>$email->group, 'sub-section' => $email->id), admin_url( 'admin.php?page=learn-press-settings&tab=emails' )) ); ?>"><?php echo $email->title; ?></a>
							<?php } else { ?>
                                <a href="<?php echo esc_url( add_query_arg( array('section' => $email->id), admin_url( 'admin.php?page=learn-press-settings&tab=emails' )) ); ?>"><?php echo $email->title; ?></a>
							<?php } ?>
                        </td>
                        <td class="description"><?php echo $email->description; ?></td>
                        <td class="status<?php echo $email->enable ? ' enabled' : ''; ?>">
                            <span class="dashicons dashicons-yes"></span>
                        </td>
                    </tr>
				<?php } ?>
                </tbody>
            </table>
			<?php
			return ob_get_clean();
		}
	}
}