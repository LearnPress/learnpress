<?php
/**
 * Template for displaying submit button of become-teacher form.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

defined( 'ABSPATH' ) or exit;
?>
<button type="submit"
        data-text="<?php esc_attr_e( 'Submitting', 'learnpress' ); ?>"><?php _e( 'Submit', 'learnpress' ); ?></button>