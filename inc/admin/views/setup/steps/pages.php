<?php
/**
 * Template for displaying system pages while setting up LearnPress.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 4.3.2
 */

defined( 'ABSPATH' ) || exit;

$system_pages = array(
	'courses'           => array(
		'title'       => __( 'All Courses Page', 'learnpress' ),
		'description' => __( 'Main directory displaying all available courses for students.', 'learnpress' ),
	),
	'instructors'       => array(
		'title'       => __( 'All Instructors Page', 'learnpress' ),
		'description' => __( 'Grid listing all registered teachers and instructors.', 'learnpress' ),
	),
	'single_instructor' => array(
		'title'       => __( 'Single Instructor Page', 'learnpress' ),
		'description' => __( 'Public instructor profile and their authored courses.', 'learnpress' ),
	),
	'profile'           => array(
		'title'       => __( 'Profile Page', 'learnpress' ),
		'description' => __( 'Student dashboard for enrolled courses, certificates & progress.', 'learnpress' ),
	),
	'checkout'          => array(
		'title'       => __( 'Checkout Page', 'learnpress' ),
		'description' => __( 'Secure payment checkout and course registration page.', 'learnpress' ),
	),
	'become_a_teacher'  => array(
		'title'       => __( 'Become an Instructor Page', 'learnpress' ),
		'description' => __( 'Registration form for prospective instructors to apply.', 'learnpress' ),
	),
	'term_conditions'   => array(
		'title'       => __( 'Terms and Conditions Page', 'learnpress' ),
		'description' => __( 'Terms of service agreement required prior to course purchase.', 'learnpress' ),
	),
);
?>
<section class="lp-setup-system-pages">
	<header class="lp-setup-section-header">
		<h2><?php esc_html_e( 'Step 2: Setup System Pages', 'learnpress' ); ?></h2>
		<p>
			<?php esc_html_e( 'When LearnPress is installed, essential system pages are automatically created and assigned out-of-the-box so your online academy is ready to function right away. You can easily re-assign or customize any page below anytime.', 'learnpress' ); ?>
		</p>
	</header>

	<div class="lp-setup-system-pages__list">
		<?php foreach ( $system_pages as $page_key => $page_data ) { ?>
			<?php
			$page_id  = learn_press_get_page_id( $page_key );
			$is_ready = $page_id && 'publish' === get_post_status( $page_id );
			?>
			<article class="lp-setup-page-card">
				<div class="lp-setup-page-card__content">
					<h3><?php echo esc_html( $page_data['title'] ); ?></h3>
					<p><?php echo esc_html( $page_data['description'] ); ?></p>
				</div>

				<div class="lp-setup-page-card__actions">
					<?php if ( $is_ready ) { ?>
						<span class="lp-setup-page-status lp-setup-page-status--ready">
							<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="m4 8 2.5 2.5L12 5"/></svg>
							<?php esc_html_e( 'Created & Ready to use', 'learnpress' ); ?>
						</span>

						<a class="lp-setup-page-card__edit" href="<?php echo esc_url( get_edit_post_link( $page_id ) ); ?>" target="_blank" rel="noopener noreferrer">
							<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m13.8 3.5 2.7 2.7L7.2 15.5l-3.4.7.7-3.4zM12.3 5l2.7 2.7"/></svg>
							<?php esc_html_e( 'Edit', 'learnpress' ); ?>
						</a>

						<a class="lp-setup-page-card__view" href="<?php echo esc_url( get_permalink( $page_id ) ); ?>" target="_blank" rel="noopener noreferrer">
							<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M2.5 10s2.7-4 7.5-4 7.5 4 7.5 4-2.7 4-7.5 4-7.5-4-7.5-4z"/><circle cx="10" cy="10" r="1.8"/></svg>
							<?php esc_html_e( 'View', 'learnpress' ); ?>
						</a>
					<?php } else { ?>
						<span class="lp-setup-page-status lp-setup-page-status--missing">
							<?php esc_html_e( 'Needs setup', 'learnpress' ); ?>
						</span>
					<?php } ?>
				</div>
			</article>
		<?php } ?>
	</div>
</section>
