<?php
/**
 * Template for displaying message when LP updating to latest version
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="lp-update-db-modal" class="lp-update-db-modal lp-update-db-modal__hidden">
	<div class="lp-update-db-modal__overlay">
		<div class="lp-update-db-modal__overlay-bg"></div>
	</div>
	<div class="lp-update-db-modal__content">
		<div class="lp-update-db-modal__content-header">
			<div class="lp-update-db-modal__content-start">
				<div class="lp-update-db-modal__content-icon">
					<svg class="lp-update-db-modal__content-icon__error" x-description="Heroicon name: exclamation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
					</svg>

					<svg class="lp-update-db-modal__content-icon__success" x-description="Heroicon name: exclamation" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
					</svg>

				</div>
				<div class="lp-update-db-modal__content-text" data-text="<?php esc_attr_e( 'LearnPress database update successfuly', 'learnpress' ); ?>">
					<h3><?php esc_html_e( 'LearnPress update database', 'learnpress' ); ?></h3>
					<p><?php esc_html_e( 'Are you sure you want to update database? All of your website database will replace. Please back up your site and database before update.', 'learnpress' ); ?></p>
				</div>
			</div>
		</div>
		<div class="lp-update-db-modal__content-footer">
			<a class="lp-update-db-modal__cancel" href="#"><?php esc_html_e( 'Cancel', 'learnpress' ); ?></a>
			<a class="lp-update-db-modal__button" href="#" data-loading="<?php esc_attr_e( 'Updating...', 'learnpress' ); ?>">
				<?php esc_html_e( 'Update', 'learnpress' ); ?>
			</a>
		</div>
	</div>
</div>
