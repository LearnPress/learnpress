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

$current_step    = $wizard->get_current_step();
$is_welcome_step = 'welcome' === $current_step;
$main_class      = $is_welcome_step ? 'lp-setup-main--welcome' : 'lp-setup-main--wizard';
$wizard_steps    = array();

foreach ( $steps as $step_key => $step_data ) {
	if ( 'welcome' !== $step_key ) {
		$wizard_steps[ $step_key ] = $step_data;
	}
}

$wizard_step_keys = array_keys( $wizard_steps );
?>

<div id="main" class="<?php echo esc_attr( $main_class ); ?>">
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
			<?php if ( $is_welcome_step ) { ?>
				<div class="buttons">
					<a class="button button-next button-primary" href="<?php echo esc_url_raw( $wizard->get_next_url() ); ?>">
						<?php echo wp_kses_post( $step['next_button'] ); ?>
					</a>
					<a class="button-dismiss-setup" href="<?php echo esc_url( admin_url( 'index.php' ) ); ?>">
						<?php esc_html_e( 'Dismiss Setup Wizard', 'learnpress' ); ?>
					</a>
				</div>
			<?php } else { ?>
				<div class="lp-setup-footer-bar">
					<div class="lp-setup-footer-bar__back">
						<?php if ( ! ( array_key_exists( 'back_button', $step ) && false === $step['back_button'] ) ) { ?>
							<a class="button button-prev" href="<?php echo esc_url_raw( $wizard->get_prev_url() ); ?>">
								<?php echo ! empty( $step['back_button'] ) ? wp_kses_post( $step['back_button'] ) : esc_html__( 'Back', 'learnpress' ); ?>
							</a>
						<?php } ?>
					</div>

					<ol class="lp-setup-stepper" aria-label="<?php esc_attr_e( 'Setup progress', 'learnpress' ); ?>">
						<?php
						$current_position = array_search( $current_step, $wizard_step_keys, true );
						foreach ( $wizard_steps as $step_key => $step_data ) {
							$step_position = array_search( $step_key, $wizard_step_keys, true );
							$item_class    = 'lp-setup-stepper__item';
							if ( $step_key === $current_step ) {
								$item_class .= ' is-active';
							} elseif ( false !== $current_position && $step_position < $current_position ) {
								$item_class .= ' is-complete';
							}
							?>
							<li class="<?php echo esc_attr( $item_class ); ?>">
								<span class="lp-setup-stepper__number">
									<?php if ( false !== strpos( $item_class, 'is-complete' ) ) { ?>
										<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="m4 8 2.5 2.5L12 5"/></svg>
									<?php } else { ?>
										<?php echo esc_html( $step_position + 1 ); ?>
									<?php } ?>
								</span>
								<span class="lp-setup-stepper__label"><?php echo esc_html( $step_data['title'] ); ?></span>
							</li>
						<?php } ?>
					</ol>

					<div class="lp-setup-footer-bar__controls">
						<a class="button-skip-next" href="<?php echo esc_url_raw( $wizard->get_next_url() ); ?>">
							<?php esc_html_e( 'Skip this step', 'learnpress' ); ?>
						</a>
						<a class="button button-next button-primary" href="<?php echo esc_url_raw( $wizard->get_next_url() ); ?>">
							<?php echo ! empty( $step['next_button'] ) ? wp_kses_post( $step['next_button'] ) : esc_html__( 'Next', 'learnpress' ); ?>
						</a>
					</div>
				</div>
			<?php } ?>
		<?php } ?>
	</form>
	<span class="icon-loading"></span>
</div>
