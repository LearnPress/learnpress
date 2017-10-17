<?php
/**
 * Template for displaying fields of become-teacher form.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! isset( $fields ) ) {
	return;
}

?>

<ul class="become-teacher-fields form-fields">

	<?php foreach ( $fields as $field ) { ?>

        <li class="form-field">
			<?php LP_Meta_Box_Helper::show_field( $field ); ?>
        </li>

	<?php } ?>

</ul>

<input type="hidden" name="request-become-a-teacher-nonce"
       value="<?php echo wp_create_nonce( 'request-become-a-teacher' ); ?>">
