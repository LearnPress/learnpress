<?php
/**
 * Template for displaying template of login form
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$profile = LP_Global::profile();
$fields  = $profile->get_register_fields();
?>
<div class="learn-press-register-form learn-press-form">

    <h3><?php echo _x( 'Register', 'register-heading', 'learnpress' ); ?></h3>

	<?php
	do_action( 'learn-press/before-register-form' );
	?>

    <form name="learn-press-register" method="post" action="">

		<?php
		do_action( 'learn-press/before-register-form-fields' );
		?>

        <ul class="form-fields">
			<?php foreach ( $fields as $field ) { ?>
                <li class="form-field">
					<?php LP_Meta_Box_Helper::show_field( $field ); ?>
                </li>
			<?php } ?>
        </ul>

		<?php
		do_action( 'learn-press/after-register-form-fields' );
		?>

        <p>
            <input type="hidden" name="learn-press-register-nonce"
                   value="<?php echo wp_create_nonce( 'learn-press-register' ); ?>">
            <button type="submit"><?php _e( 'Register', 'learnpress' ); ?></button>
        </p>

    </form>

	<?php
	do_action( 'learn-press/after-register-form' );
	?>

</div>
