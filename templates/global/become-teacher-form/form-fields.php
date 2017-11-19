<?php
/**
 * Template for displaying become teacher form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/become-teacher-form/form-fields.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php if ( ! isset( $fields ) ) {
	return;
} ?>

<ul class="become-teacher-fields form-fields">

	<?php foreach ( $fields as $field ) { ?>

        <li class="form-field">
			<?php LP_Meta_Box_Helper::show_field( $field ); ?>
        </li>

	<?php } ?>

</ul>

<input type="hidden" name="request-become-a-teacher-nonce"
       value="<?php echo wp_create_nonce( 'request-become-a-teacher' ); ?>">
