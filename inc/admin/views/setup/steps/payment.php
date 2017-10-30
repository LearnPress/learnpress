<?php
/**
 * Template for displaying payments of setup wizard.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

$wizard   = LP_Setup_Wizard::instance();
$payments = $wizard->get_payments();

?>
<h2><?php _e( 'Payment', 'learnpress' ); ?></h2>

<ul class="browse-payments">
	<?php foreach ( $payments as $slug => $payment ) { ?>
        <li class="payment payment-<?php echo $slug; ?>">
            <h3 class="payment-name">
				<?php if ( ! empty( $payment['icon'] ) ) { ?>
                    <img src="<?php echo $payment['icon']; ?>">
				<?php } else { ?>
					<?php echo $payment['name']; ?>
				<?php } ?>
            </h3>
			<?php if ( ! empty( $payment['desc'] ) ) { ?>
                <p class="payment-desc"><?php echo $payment['desc']; ?></p>
			<?php } ?>
            <div class="payment-settings">
				<?php call_user_func( $payment['callback'] ); ?>
            </div>
        </li>
	<?php } ?>
</ul>