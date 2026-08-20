<?php
/**
 * Admin View: Help Center page.
 *
 * @package LearnPress/Admin/Views
 *
 * @var array  $quick_links
 * @var array  $whats_new
 * @var array  $articles
 * @var array  $banner_ad
 * @var string $tick_icon
 */

use LearnPress\TemplateHooks\Admin\AdminHelpCenterDataTemplate;
use LearnPress\TemplateHooks\Admin\AdminTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) || exit;
$quick_links = apply_filters(
	'learn-press/admin/help-center/quick-links',
	array(
		array(
			'icon'        => 'help-center/ico-hc-support-ticket.svg',
			'title'       => __( 'Support Ticket', 'learnpress' ),
			'description' => __( 'Need help with your LMS website? Submit a ticket to the LearnPress support team.', 'learnpress' ),
			'button'      => __( 'Create Ticket', 'learnpress' ),
			'url'         => 'https://help.thimpress.com/',
		),
		array(
			'icon'        => 'help-center/ico-hc-video-tutorials.svg',
			'title'       => __( 'Video Tutorials', 'learnpress' ),
			'description' => __( 'Watch step-by-step videos about LearnPress settings and common workflows.', 'learnpress' ),
			'button'      => __( 'Watch Videos', 'learnpress' ),
			'url'         => 'https://www.youtube.com/@LearnPressLMS/videos',
		),
		array(
			'icon'        => 'help-center/ico-hc-documentation.svg',
			'title'       => __( 'Documentation', 'learnpress' ),
			'description' => __( 'Browse LearnPress setup guides, course management, quizzes, payment, and emails.', 'learnpress' ),
			'button'      => __( 'Read Docs', 'learnpress' ),
			'url'         => 'https://learnpresslms.com/docs/',
		),
		array(
			'icon'        => 'help-center/ico-hc-community.svg',
			'title'       => __( 'Community', 'learnpress' ),
			'description' => __( 'Join the LearnPress community to share ideas, ask questions, and connect with LMS site owners.', 'learnpress' ),
			'button'      => __( 'Join Community', 'learnpress' ),
			'url'         => 'https://www.facebook.com/groups/learnpress/',
		),
		array(
			'icon'        => 'help-center/ico-hc-feedback.svg',
			'title'       => __( 'Feedback', 'learnpress' ),
			'description' => __( 'Share your thoughts, report issues, and help improve the LearnPress experience.', 'learnpress' ),
			'button'      => __( 'Send Feedback', 'learnpress' ),
			'url'         => 'https://learnpresslms.com/feedback/',
		),
		array(
			'icon'        => 'help-center/ico-hc-affiliate.svg',
			'title'       => __( 'Affiliate', 'learnpress' ),
			'description' => __( 'Promote LearnPress products, track referrals, and manage your affiliate resources.', 'learnpress' ),
			'button'      => __( 'Join Affiliate', 'learnpress' ),
			'url'         => 'https://thimpress.com/become-an-affiliate/',
		),
	)
);

foreach ( $quick_links as &$link ) {
	$link['icon_svg'] = LP_WP_Filesystem::get_icon_svg( $link['icon'] ?? '' );
}
unset( $link );
?>

<?php ob_start(); ?>
	<p class="lp-help-center-subtitle">
		<?php esc_html_e( 'Find documentation, tutorials, troubleshooting guides, and support resources for LearnPress.', 'learnpress' ); ?>
	</p>

	<?php if ( $quick_links ) : ?>
		<div class="lp-help-center-grid">
			<?php foreach ( $quick_links as $item ) : ?>
				<div class="lp-help-center-card">
					<span class="lp-help-center-card__icon">
						<?php echo $item['icon_svg'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG file content via LP_WP_Filesystem::get_icon_svg(). ?>
					</span>

					<div class="lp-help-center-card__content">
						<h3><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $item['description'] ?? '' ); ?></p>
					</div>

					<a class="button lp-help-center-card__button" href="<?php echo esc_url( $item['url'] ?? '#' ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $item['button'] ?? '' ); ?>
					</a>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php
	echo TemplateAJAX::load_content_via_ajax(
		[
			'id_url' => 'data-help-center',
		],
		[
			'class'  => AdminHelpCenterDataTemplate::class,
			'method' => 'html_data_online',
		]
	)
	?>
<?php
$content = ob_get_clean();

echo AdminTemplate::html_on_wp_admin_screen(
	array(
		'content' => $content,
		'title'   => __( 'LearnPress Help Center', 'learnpress' ),
		'id'      => 'learn-press-help-center',
	)
);
