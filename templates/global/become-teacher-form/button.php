<?php
/**
 * Template for displaying submit button of become teacher form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/become-teacher-form/button.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();
?>

<button type="submit" data-text="<?php esc_attr_e( 'Submitting', 'learnpress' ); ?>">
	<?php esc_html_e( 'Submit', 'learnpress' ); ?>
</button>
