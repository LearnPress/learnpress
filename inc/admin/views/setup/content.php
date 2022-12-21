<?php
/**
 * Template for displaying content of setup wizard.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;
$wizard = LP_Setup_Wizard::instance();

if ( ! isset( $steps ) ) {
	return;
}
?>

<div id="main">
	<div class="lp-setup-nav">
		<ul class="lp-setup-steps">
			<?php foreach ( $steps as $key => $step ) { ?>
				<li class="<?php echo esc_attr( $wizard->get_current_step() == $key ? 'active' : '' ); ?>">
					<span><?php echo esc_html( $step['title'] ); ?></span>
				</li>
			<?php } ?>
		</ul>
		<div class="lp-setup-progress">
			<div class="active"
				style="width: <?php echo intval( ( $wizard->get_step_position() + 1 ) / sizeof( $steps ) * 100 ); ?>%;">
			</div>
		</div>
	</div>
	<form id="learn-press-setup-form" class="lp-setup-content" name="lp-setup" method="post">
		<?php
		$step = $wizard->get_current_step( false );
		?>
		<input type="hidden" name="lp-setup-nonce"
			value="<?php echo wp_create_nonce( 'lp-setup-step-' . $step['slug'] ); ?>">
		<input type="hidden" name="lp-setup-step"
			value="<?php echo esc_attr( $step['slug'] ); ?>">
		<?php call_user_func( $step['callback'] ); ?>
		<?php if ( ! $wizard->is_last_step() ) { ?>
			<div class="buttons">
				<?php if ( ! $wizard->is_first_step() && ! ( array_key_exists( 'back_button', $step ) && $step['back_button'] === false ) ) { ?>
					<a class="button button-prev" href="<?php echo esc_url_raw( $wizard->get_prev_url() ); ?>">
						<?php
						if ( ! empty( $step['next_button'] ) ) {
							echo wp_kses_post( $step['back_button'] );
						} else {
							_e( 'Back', 'learnpress' );
						}
						?>
					</a>
				<?php } ?>
				<?php if ( ! $wizard->is_last_step() && ! ( array_key_exists( 'next_button', $step ) && $step['next_button'] === false ) ) { ?>
					<a class="button button-next button-primary" href="<?php echo esc_url_raw( $wizard->get_next_url() ); ?>">
						<?php
						if ( ! empty( $step['next_button'] ) ) {
							echo wp_kses_post( $step['next_button'] );
						} else {
							_e( 'Continue', 'learnpress' );
						}
						?>
					</a>
				<?php } else { ?>
					<a class="button button-finish">
						<?php _e( 'Finish', 'learnpress' ); ?>
					</a>
				<?php } ?>
			</div>
		<?php } ?>
	</form>
	<span class="icon-loading"></span>
</div>
