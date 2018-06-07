<?php
/**
 * Template for displaying template of login form.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/global/form-login.php.
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
$fields  = $profile->get_login_fields();
?>

<div class="learn-press-form-login learn-press-form">

    <h3><?php echo _x( 'Login', 'login-heading', 'learnpress' ); ?></h3>

	<?php do_action( 'learn-press/before-form-login' ); ?>

    <form name="learn-press-login" method="post" action="">

		<?php do_action( 'learn-press/before-form-login-fields' ); ?>

        <ul class="form-fields">
			<?php foreach ( $fields as $field ) { ?>
                <li class="form-field">
					<?php LP_Meta_Box_Helper::show_field( $field ); ?>
                </li>
			<?php } ?>
        </ul>

		<?php do_action( 'learn-press/after-form-login-fields' ); ?>
        <p>
            <label>
                <input type="checkbox" name="rememberme"/>
				<?php _e( 'Remember me', 'learnpress' ); ?>
            </label>
        </p>
        <p>
            <input type="hidden" name="learn-press-login-nonce"
                   value="<?php echo wp_create_nonce( 'learn-press-login' ); ?>">
            <button type="submit"><?php _e( 'Login', 'learnpress' ); ?></button>
        </p>
        <p>
            <a href="<?php echo wp_lostpassword_url(); ?>"><?php _e( 'Lost your password?', 'learnpress' ); ?></a>
        </p>
    </form>

	<?php do_action( 'learn-press/after-form-login' ); ?>

</div>
