<?php

namespace LearnPress\TemplateHooks\Admin;

use LearnPress\Helpers\Singleton;
use LP_Admin_Notice;
use LP_Cache;
use LP_Helper;

defined( 'ABSPATH' ) || exit();

/**
 * Admin Tools page renderer.
 *
 * @since 4.2.8
 */
class AdminToolsPage {
	use Singleton;

	/**
	 * Singleton initialization hook.
	 *
	 * @return void
	 */
	public function init(): void {}

	/**
	 * Tools tabs with their labels.
	 *
	 * @return array
	 */
	private static function get_tabs(): array {
		return apply_filters(
			'learn-press/admin/tools-tabs',
			array(
				'course'          => __( 'Course Data', 'learnpress' ),
				'assign_course'   => __( 'Assign/Unassigned Course', 'learnpress' ),
				'database'        => __( 'Database', 'learnpress' ),
				'template'        => __( 'Templates', 'learnpress' ),
				'lp_beta_version' => __( 'LearnPress Beta Version', 'learnpress' ),
				'cache'           => __( 'Cache', 'learnpress' ),
			)
		);
	}

	/**
	 * Map of tool tabs to view paths.
	 *
	 * @return array
	 */
	private static function get_tab_views(): array {
		return apply_filters(
			'learn-press/admin/tools-tab-views',
			array(
				'course'   => 'tools/html-course',
				'database' => 'tools/html-database',
				'template' => 'tools/html-template',
			)
		);
	}

	/**
	 * Render the LearnPress Tools page.
	 *
	 * @return void
	 */
	public function html_page() {
		$tabs      = self::get_tabs();
		$tab_views = self::get_tab_views();
		$tab       = isset( $_REQUEST['tab'] ) ? LP_Helper::sanitize_params_submitted( $_REQUEST['tab'] ) : '';
		$tab       = array_key_exists( $tab, $tabs ) ? $tab : ( array_key_first( $tabs ) ?? '' );
		?>
		<div class="wrap lp-submenu-page learn-press-tools">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'LearnPress Tools', 'learnpress' ); ?></h1>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $tab_slug => $tab_label ) : ?>
					<?php if ( $tab_slug === $tab ) : ?>
						<span class="nav-tab nav-tab-active"><?php echo esc_html( $tab_label ); ?></span>
					<?php else : ?>
						<a class="nav-tab" href="
						<?php
						echo esc_url(
							add_query_arg(
								array(
									'page' => 'learn-press-tools',
									'tab'  => $tab_slug,
								),
								admin_url( 'admin.php' )
							)
						);
						?>
						">
							<?php echo esc_html( $tab_label ); ?>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</h2>

			<div class="lp-admin-tabs">
				<div class="lp-admin-tab-content">
					<?php
					if ( isset( $tab_views[ $tab ] ) ) {
						learn_press_admin_view( $tab_views[ $tab ] );
					} else {
						$this->render_custom_tab( $tab );
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render tabs that do not have a dedicated view file.
	 *
	 * @param string $tab Active tab slug.
	 *
	 * @return void
	 */
	private function render_custom_tab( string $tab ) {
		switch ( $tab ) {
			case 'assign_course':
				learn_press_admin_view( 'tools/course/html-assign-course' );
				learn_press_admin_view( 'tools/course/html-unassign-course' );
				break;
			case 'cache':
				$lp_cache = new LP_Cache( true );
				$lp_cache->clear_all();

				echo sprintf(
					'<form action="" method="post"><button class="button button-primary" type="submit">%s</button></form>',
					esc_html__( 'Clear all cache', 'learnpress' )
				);
				break;
			case 'lp_beta_version':
				$lp_beta_version_info = LP_Admin_Notice::check_lp_beta_version();
				learn_press_admin_view(
					'admin-notices/beta-version',
					array(
						'data' => array(
							'check' => 1,
							'info'  => $lp_beta_version_info,
						),
					)
				);
				break;
			default:
				do_action( 'learn-press/admin/tools-tab-content', $tab );
				break;
		}
	}
}
