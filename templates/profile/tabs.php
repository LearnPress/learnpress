<?php
/**
 * Template for displaying user profile tabs.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/profile/tabs.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();
?>

<div id="learn-press-profile-nav">

	<?php do_action( 'learn-press/before-profile-nav', $profile ); ?>

    <ul class="learn-press-tabs tabs">

		<?php
		foreach ( $profile->get_tabs()->tabs() as $tab_key => $tab_data ) {

            /**
             * @var $tab_data LP_Profile_Tab
             */
			if ( $tab_data->is_hidden() || ! $tab_data->user_can_view() ) {
				continue;
			}

			$slug        = $profile->get_slug( $tab_data, $tab_key );
			$link        = $profile->get_tab_link( $tab_key, true );
			$tab_classes = array( esc_attr( $tab_key ) );
			/**
			 * @var $tab_data LP_Profile_Tab
			 */
			$sections    = $tab_data->sections();

			if ( $sections && sizeof( $sections ) > 1 ) {
				$tab_classes[] = 'has-child';
			}

			if ( $profile->is_current_tab( $tab_key ) ) {
				$tab_classes[] = 'active';
			} ?>

            <li class="<?php echo join( ' ', $tab_classes ) ?>">
                <!--tabs-->
                <a href="<?php echo esc_url( $link ); ?>" data-slug="<?php echo esc_attr( $link ); ?>">
					<?php echo apply_filters( 'learn_press_profile_' . $tab_key . '_tab_title', esc_html( $tab_data['title'] ), $tab_key ); ?>
                </a>
                <!--section-->

				<?php if ( $sections && sizeof( $sections ) > 1 ) { ?>

                    <ul class="profile-tab-sections">
						<?php foreach ( $sections as $section_key => $section_data ) {

							$classes = array( esc_attr( $section_key ) );
							if ( $profile->is_current_section( $section_key, $section_key ) ) {
								$classes[] = 'active';
							}

							$section_slug = $profile->get_slug( $section_data, $section_key );
							$section_link = $profile->get_tab_link( $tab_key, $section_slug );
							?>

                            <li class="<?php echo join( ' ', $classes ); ?>">
                                <a href="<?php echo $section_link; ?>"><?php echo $section_data['title']; ?></a>
                            </li>

						<?php } ?>

                    </ul>

				<?php } ?>

            </li>
		<?php } ?>

    </ul>

	<?php do_action( 'learn-press/after-profile-nav', $profile ); ?>

</div>