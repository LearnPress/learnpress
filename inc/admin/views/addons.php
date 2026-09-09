<?php
/**
 * Template show list addons of LearnPress
 *
 * @version 1.0.1
 * @since 4.2.1
 */

use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit();

if ( ! isset( $addons ) ) {
	return;
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$total_addon_free          = 0;
$total_addon_paid          = 0;
$total_addon_installed     = 0;
$total_addon_not_installed = 0;
$total_addon_activated     = 0;
$total_addon_update        = 0;
$total_addon_purchased     = 0;
$plugins_installed         = get_plugins();
$plugins_activated         = get_option( 'active_plugins', '' );
$active_tab                = ! empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'all';
$keys_purchase             = LP_Settings::get_option( LP_Manager_Addons::instance()->key_purchase_addons, [] );
$addon_categories          = array(
	'create-course'          => __( 'Create Course', 'learnpress' ),
	'monetize-course'        => __( 'Monetize Course', 'learnpress' ),
	'manage-course'          => __( 'Manage Course', 'learnpress' ),
	'marketing-optimization' => __( 'Marketing Optimization', 'learnpress' ),
);
?>
<div class="lp-addons-wrapper">
	<div id="lp-addons">
		<?php
		foreach ( $addons as $slug => $addon ) :
			$addon->slug     = $slug;
			$is_installed    = false;
			$is_activated    = false;
			$is_updated      = false;
			$is_free         = $addon->is_free ?? 0;
			$addon_base      = $addon->basename ?? '';
			$version_latest  = $addon->version ?? 0;
			$version_current = $addon->version ?? 0;
			$classes_status  = [];
			$addon_purchased = $addon->purchase_info ?? false;
			$addon_category  = sanitize_title( $addon->category ?? '' );
			// Addon is free or paid.
			if ( 1 == $addon->is_free ) {
				++$total_addon_free;
			} else {
				++$total_addon_paid;
			}
			// Addon is installed
			if ( isset( $plugins_installed[ $addon_base ] ) ) {
				$is_installed     = true;
				$classes_status[] = 'installed';
				$version_current  = $plugins_installed[ $addon_base ]['Version'];
				++$total_addon_installed;
			} else {
				$classes_status[] = 'not_installed';
				++$total_addon_not_installed;
			}
			// Addon is activated
			if ( in_array( $addon_base, $plugins_activated ) ) {
				$is_activated     = true;
				$classes_status[] = 'activated';
				++$total_addon_activated;
			}
			// Addon is having update
			if ( $is_installed && version_compare( $version_current, $version_latest, '<' ) ) {
				++$total_addon_update;
				$classes_status[] = 'update';
				$is_updated       = true;
			}
			// Addon is purchased
			if ( $addon_purchased ) {
				$classes_status[] = 'license';
				++$total_addon_purchased;
				$date_expired_str = $addon_purchased->date_expire ?? '';
				// Test
				//$date_expired_str = '2024-02-01';
				//$date_expired_str = '2023-01-12';
				// End
				$date_expired          = new DateTime( $date_expired_str );
				$date_now              = new DateTime( gmdate( 'Y-m-d' ) );
				$date_diff             = date_diff( $date_now, $date_expired );
				$number_days_remaining = $date_diff->days;
				if ( $date_diff->invert ) {
					$number_days_remaining = 0;
				}
			}
			// Addon is paid on Thimpress
			if ( ! $is_free ) {
				$classes_status[] = 'purchase';
				$purchase_code    = $keys_purchase[ $addon->slug ] ?? '';
			} else { // Addon is free
				$classes_status[] = 'free';
			}
			// Show addons of tab.
			if ( ! in_array( $active_tab, $classes_status ) && $active_tab != 'all' ) {
				$classes_status[] = 'hide';
			}
			?>
			<div class="lp-addon-item <?php echo esc_attr( implode( ' ', $classes_status ) ); ?>"
				data-slug="<?php echo esc_attr( $slug ); ?>"
				data-category="<?php echo esc_attr( $addon_category ); ?>">
				<div class="lp-addon-item__content">
					<div class="lp-addon-item__header">
						<img class="lp-addon-item__image" src="<?php echo esc_url( $addon->image ); ?>" alt=""/>
						<h3 class="lp-addon-item__title">
							<a href="<?php echo esc_url( $addon->link ); ?>" target="_blank" rel="noopener">
								<?php echo esc_html( $addon->name ); ?>
							</a>
						</h3>
					</div>
					<p class="lp-addon-item__description" title="<?php echo esc_attr( $addon->description ); ?>">
						<?php echo esc_html( $addon->description ); ?>
					</p>
					<div class="lp-addon-item__meta">
						<?php if ( $version_current ) { ?>
							<span><?php esc_html_e( 'Version', 'learnpress' ); ?> <span class="addon-version-current"><?php echo esc_html( $version_current ); ?></span></span>
						<?php } ?>
						<?php if ( ! empty( $addon->link_doc ) ) { ?>
							<a href="<?php echo esc_url( $addon->link_doc ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Docs', 'learnpress' ); ?></a>
						<?php } ?>
						<a href="<?php echo esc_url( $addon->link ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View details', 'learnpress' ); ?></a>
					</div>
					<?php
					// Show version latest.
					if ( $is_updated ) {
						echo sprintf(
							'<span class="new-update">%s: %s</span>',
							esc_html__( 'Latest version', 'learnpress' ),
							esc_html( $version_latest )
						);
					}

					if ( $addon_purchased ) {
						$message        = '';
						$button_extends = sprintf(
							'<a class="need-extend__link" href="%s" target="_blank" rel="noopener">%s</a>',
							esc_url( $addon->link ),
							esc_html__( 'Extend now', 'learnpress' )
						);

						if ( $number_days_remaining === 0 ) {
							$message      = __( 'Your update and support has expired!', 'learnpress' );

						} elseif ( $number_days_remaining < 61 ) {
							$message      = sprintf(
								__( 'You have a license of for this item with %s days of update & support remaining. Please extend the update & support license to keep updating the latest versions & receive customer support from ThimPress before it expires.', 'learnpress' ),
								sprintf( '<strong class="need-extend__days">%d</strong>', $number_days_remaining )
							);
						} else {
							$button_extends = '';
						}

							echo sprintf(
							'<span class="need-extend">%s %s</span>',
							$message,
							$button_extends
						);
					}
					?>
				</div>
				<div class="lp-addon-item__actions">
					<div class="lp-addon-item__price">
						<?php if ( $is_free ) { ?>
							<strong class="lp-addon-item__price-current lp-addon-item__price-current--free"><?php esc_html_e( 'Free', 'learnpress' ); ?></strong>
						<?php } else { ?>
							<strong class="lp-addon-item__price-current">$<?php echo esc_html( number_format_i18n( $addon->price ?? 0 ) ); ?></strong>
							<?php if ( ! empty( $addon->old_price ) ) { ?>
								<del class="lp-addon-item__price-old">$<?php echo esc_html( number_format_i18n( $addon->old_price ) ); ?></del>
							<?php } ?>
						<?php } ?>
					</div>
					<div class="lp-addon-item__actions__left">
						<?php
						if ( isset( $addon->setting ) && ! empty( $addon->setting ) ) {
							?>
							<a class="lp-addon-button" data-action="setting" href="<?php echo esc_url( site_url( $addon->setting ) ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Settings', 'learnpress' ); ?>
							</a>
							<?php
						}
						?>
						<button class="btn-addon-action" data-action="update"
								title="<?php echo sprintf( '%s %s require LP version %s', $addon->name, $version_latest, $addon->require_lp ); ?>">
							<span class="dashicons dashicons-update"></span><span class="text">Update</span>
						</button>
						<button class="btn-addon-action" data-action="update-purchase-code"
								title="<?php _e( 'Change Purchase Code', 'learnpress' ); ?>">
							<span class="dashicons dashicons-ellipsis"></span>
						</button>
						<button class="btn-addon-action" data-action="install"
							<?php echo $is_free ? 'data-link="' . $addon->link . '"' : ''; ?>
						>
							<span class="dashicons dashicons-update"></span><span
								class="text"><?php _e( 'Install', 'learnpress' ); ?></span>
						</button>
						<button class="btn-addon-action"
								data-action="purchase"><?php _e( 'Install', 'learnpress' ); ?></button>
					</div>
					<div class="lp-addon-item__actions__right">
						<button class="btn-addon-action" data-action="deactivate">
							<span class="dashicons dashicons-update"></span><span
								class="text"><?php _e( 'Deactivate', 'learnpress' ); ?></span>
						</button>
						<button class="btn-addon-action" data-action="activate">
							<span class="dashicons dashicons-update"></span><span
								class="text"><?php _e( 'Activate', 'learnpress' ); ?></span>
						</button>
					</div>
				</div>
				<div class="lp-addon-item__purchase">
					<div class="lp-addon-item__purchase__wrapper">
						<div class="purchase-install">
							<div class="lp-addon-purchase__header">
								<strong class="lp-addon-purchase__title"><?php esc_html_e( 'Purchase Code', 'learnpress' ); ?></strong>
								<button class="btn-addon-action lp-addon-purchase__close" data-action="cancel" type="button" aria-label="<?php esc_attr_e( 'Close', 'learnpress' ); ?>">
									<span class="lp-addon-purchase__close-symbol" aria-hidden="true">&times;</span>
								</button>
							</div>
							<label class="lp-addon-purchase__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Purchase Code', 'learnpress' ); ?></span>
								<input type="text" class="enter-purchase-code" placeholder="<?php esc_attr_e( 'Enter Purchase Code', 'learnpress' ); ?>" value="<?php echo esc_attr( $purchase_code ?? '' ); ?>">
							</label>
							<button class="btn-addon-action lp-addon-purchase__submit" data-action="install">
								<span class="dashicons dashicons-update"></span><span
									class="text"><?php esc_html_e( 'Submit', 'learnpress' ); ?></span>
							</button>
							<div class="lp-addon-purchase__divider">
								<span><?php esc_html_e( 'Don’t have a code?', 'learnpress' ); ?></span>
							</div>
							<button class="btn-addon-action lp-addon-purchase__buy" data-action="buy" data-link="<?php echo esc_url( $addon->link ); ?>">
								<?php esc_html_e( 'Buy Now', 'learnpress' ); ?>
							</button>
						</div>
						<div class="purchase-update">
							<div class="lp-addon-purchase__header">
								<strong class="lp-addon-purchase__title"><?php esc_html_e( 'Purchase Code', 'learnpress' ); ?></strong>
								<button class="btn-addon-action lp-addon-purchase__close" data-action="cancel" type="button" aria-label="<?php esc_attr_e( 'Close', 'learnpress' ); ?>">
									<span class="lp-addon-purchase__close-symbol" aria-hidden="true">&times;</span>
								</button>
							</div>
							<label class="lp-addon-purchase__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Purchase Code', 'learnpress' ); ?></span>
								<input type="text" class="enter-purchase-code" placeholder="<?php esc_attr_e( 'Enter Purchase Code', 'learnpress' ); ?>" value="<?php echo esc_attr( $purchase_code ?? '' ); ?>">
							</label>
							<button class="btn-addon-action lp-addon-purchase__submit" data-action="update-purchase">
								<span class="dashicons dashicons-update"></span><span
									class="text"><?php esc_html_e( 'Save', 'learnpress' ); ?></span>
							</button>
						</div>
						<input type="hidden" name="purchase-code"
								value="<?php echo esc_attr( $purchase_code ?? '' ); ?>">
					</div>
				</div>
			</div>
			<?php
		endforeach;
		?>
	</div>
	<div class="lp-addons-controls" hidden>
		<div class="lp-nav-tab-wrapper lp-addons-toolbar">
			<div class="lp-addons-filter" role="group" aria-label="<?php esc_attr_e( 'Filter add-ons', 'learnpress' ); ?>">
			<?php
			$tabs = array(
				'all'           => sprintf( '%s (<span>%d</span>)', __( 'All', 'learnpress' ), count( (array) $addons ) ),
				'installed'     => sprintf( '%s (<span>%d</span>)', __( 'Installed', 'learnpress' ), $total_addon_installed ),
				'purchase'      => sprintf( '%s (<span>%d</span>)', __( 'Paid', 'learnpress' ), $total_addon_paid ),
				'free'          => sprintf( '%s (<span>%d</span>)', __( 'Free', 'learnpress' ), $total_addon_free ),
				'update'        => sprintf( '%s (<span>%d</span>)', __( 'Updated', 'learnpress' ), $total_addon_update ),
				'license'       => sprintf( '%s (<span>%d</span>)', __( 'License', 'learnpress' ), $total_addon_purchased ),
				'not_installed' => sprintf( '%s (<span>%d</span>)', __( 'Not Installed', 'learnpress' ), $total_addon_not_installed ),
			);
			foreach ( $tabs as $tab => $name ) {
				?>
				<?php

				$active_class = ( $tab == $active_tab ) ? ' nav-tab-active' : '';
				$tab_title    = apply_filters( 'learn-press/admin/submenu-heading-tab-title', $name, $tab );
				?>

				<?php if ( $active_class ) { ?>
					<a class="lp-addons-filter__item nav-tab<?php echo esc_attr( $active_class ); ?>"
						data-tab="<?php echo esc_attr( $tab ); ?>" href="#" aria-pressed="true">
						<?php echo wp_kses_post( $tab_title ); ?>
					</a>
				<?php } else { ?>
					<a class="lp-addons-filter__item nav-tab"
						data-tab="<?php echo esc_attr( $tab ); ?>"
						aria-pressed="false"
						href="?page=learn-press-addons&tab=<?php echo esc_attr( $tab ); ?>">
						<?php echo wp_kses_post( $tab_title ); ?>
					</a>
				<?php } ?>
			<?php } ?>
			</div>
			<label class="lp-search-addons lp-addons-search">
				<span class="screen-reader-text"><?php esc_html_e( 'Search add-ons', 'learnpress' ); ?></span>
				<span class="lp-addons-search__icon lp-icon-search" aria-hidden="true"></span>
				<input id="lp-search-addons__input" class="lp-addons-search__input" type="search" placeholder="<?php esc_attr_e( 'Search add-ons by name…', 'learnpress' ); ?>"/>
			</label>
		</div>
		<div class="lp-addons-categories" role="group" aria-label="<?php esc_attr_e( 'Filter add-ons by category', 'learnpress' ); ?>">
			<button type="button" class="lp-addons-category active" data-category="all" aria-pressed="true">
				<?php esc_html_e( 'All', 'learnpress' ); ?> <span class="lp-addons-category__count"></span>
			</button>
			<?php foreach ( $addon_categories as $category_slug => $category_label ) { ?>
				<button type="button" class="lp-addons-category" data-category="<?php echo esc_attr( $category_slug ); ?>" aria-pressed="false">
					<?php echo esc_html( $category_label ); ?> <span class="lp-addons-category__count"></span>
				</button>
			<?php } ?>
		</div>
	</div>
</div>
