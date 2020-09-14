<?php
/**
 * Display settings for course
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = LP_Settings::instance();
?>

<h3 class=""><?php echo $this->section['title']; ?></h3>
<table class="form-table">
	<tbody>
	<?php
		do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );
		$this->output_settings();
		do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $settings );
	?>
	</tbody>
</table>

<script type="text/javascript">
	jQuery( function( $ ) {
		$( 'input.learn-press-course-base' ).on( 'change', function() {
			$( '#course_permalink_structure' ).val( $(this).val() );
		});

		$( '#course_permalink_structure' ).on( 'focus', function() {
			$( '#learn_press_custom_permalink' ).trigger( 'click' );
		});

		$( '#learn_press_courses_page_id' ).on( 'change', function() {
			$( 'tr.learn-press-courses-page-id' ).toggleClass( 'hide-if-js',  !parseInt( this.value ) );
		});
	});
</script>
