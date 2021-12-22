<?php
/**
 * Template for displaying user profile tabs.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/profile/tabs.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.1
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $user ) || ! isset( $profile ) ) {
	return;
}
?>

<div id="profile-nav">

	<?php do_action( 'learn-press/before-profile-nav', $profile ); ?>

	<ul class="lp-profile-nav-tabs">

		<?php
		/**
		 * @var LP_Profile_Tab $profile_tab
		 */
		foreach ( $profile->get_tabs()->tabs() as $tab_key => $profile_tab ) {
			if ( ! is_object( $profile_tab ) || ! $profile_tab || $profile_tab->is_hidden() || ! $profile_tab->user_can_view() ) {
				continue;
			}

			// Admin view another user profile
			if ( $profile->get_user()->get_id() !== get_current_user_id() && current_user_can( ADMIN_ROLE ) ) {
				$tab_key_hidden_admin_view_user = [ 'settings', 'logout', 'orders', 'gradebook' ];
				if ( in_array( $tab_key, $tab_key_hidden_admin_view_user ) ) {
					continue;
				}
			}

			$slug        = $profile->get_slug( $profile_tab, $tab_key );
			$link        = $profile->get_tab_link( $tab_key, true );
			$tab_classes = array( esc_attr( $tab_key ) );

			$sections = $profile_tab->sections();

			if ( $sections && sizeof( $sections ) > 1 ) {
				$tab_classes[] = 'has-child';
			}

			if ( $profile->is_current_tab( $tab_key ) ) {
				$tab_classes[] = 'active';
			}
			?>

			<li class="<?php echo implode( ' ', $tab_classes ); ?>">
				<a href="<?php echo esc_url( $link ); ?>" data-slug="<?php echo esc_attr( $link ); ?>">
					<?php
					if ( ! empty( $profile_tab['icon'] ) ) {
						echo $profile_tab['icon'];
					}
					?>
					<?php echo apply_filters( 'learn_press_profile_' . $tab_key . '_tab_title', $profile_tab['title'], $tab_key ); ?>
				</a>

				<?php if ( $sections && sizeof( $sections ) > 1 ) { ?>

					<ul class="profile-tab-sections">
						<?php
						foreach ( $sections as $section_key => $section_data ) {

							$classes = array( esc_attr( $section_key ) );
							if ( $profile->is_current_section( $section_key, $section_key ) ) {
								$classes[] = 'active';
							}

							$section_slug = $profile->get_slug( $section_data, $section_key );
							$section_link = $profile->get_tab_link( $tab_key, $section_slug );
							?>

							<li class="<?php echo implode( ' ', $classes ); ?>">
								<a href="<?php echo esc_url( $section_link ); ?>">
									<?php
									if ( ! empty( $section_data['icon'] ) ) {
										echo $section_data['icon'];
									}
									?>
									<?php echo apply_filters( 'learn_press_profile_' . $tab_key . '_tab_title', $section_data['title'], $tab_key ); ?>
								</a>
							</li>

						<?php } ?>

					</ul>

				<?php } ?>

			</li>
		<?php } ?>

	</ul>

	<?php do_action( 'learn-press/after-profile-nav', $profile ); ?>

</div>
