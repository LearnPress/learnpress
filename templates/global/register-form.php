<?php
/**
 * Template for displaying global login form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/register-form.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$profile = LP_Global::profile();
$fields  = $profile->get_register_fields();
?>

<div class="learn-press-register-form learn-press-form">

    <h3><?php echo _x( 'Register', 'register-heading', 'learnpress' ); ?></h3>

	<?php do_action( 'learn-press/before-register-form' ); ?>

    <form name="learn-press-register" method="post" action="">

		<?php do_action( 'learn-press/before-register-form-fields' ); ?>

        <ul class="form-fields">
			<?php foreach ( $fields as $field ) { ?>
                <li class="form-field">
					<?php LP_Meta_Box_Helper::show_field( $field ); ?>
                </li>
			<?php } ?>
        </ul>

		<?php do_action( 'learn-press/after-register-form-fields' ); ?>

        <p>
            <input type="hidden" name="learn-press-register-nonce"
                   value="<?php echo wp_create_nonce( 'learn-press-register' ); ?>">
            <button type="submit"><?php _e( 'Register', 'learnpress' ); ?></button>
        </p>

    </form>

	<?php do_action( 'learn-press/after-register-form' ); ?>

</div>
