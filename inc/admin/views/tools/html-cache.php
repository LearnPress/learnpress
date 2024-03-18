<?php
/**
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || die();
?>

<div class="card">
	<h2><?php _e( 'LearnPress option cache', 'learnpress' ); ?></h2>
	<form method="post"
		  action="<?php echo esc_attr( admin_url( 'admin.php?page=learn-press-tools&tab=cache&clear_all=true' ) ) ?>">
		<button class="button button-primary" type="submit"><?php echo __( 'Clear all cache', 'learnpress' ) ?></button>
	</form>

	<form action="">
		<div>
			<?php
			$settings = [
				[
					'title'   => __( 'No cache HTML item coure (Quiz, Lesson...)', 'learnpress' ),
					'id'      => 'learn_press_skip_cache_quiz',
					'type'    => 'checkbox',
					'default' => 'no',
					'desc'    => __( 'Enable', 'learnpress' ),
				],
			];
			LP_Meta_Box_Helper::output_fields( $settings );
			?>
		</div>
	</form>
</div>
