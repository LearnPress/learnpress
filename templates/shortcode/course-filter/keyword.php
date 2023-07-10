<?php
if ( ! isset( $data ) ) {
	return;
}

$value          = LP_Helper::sanitize_params_submitted( $_GET['c_search'] ?? '' );
$has_suggestion = ! empty( $data['search_suggestion'] );
do_action( 'learn-press/shortcode/course-filter/keyword/before', $data );
?>

<h4><?php esc_html_e( 'Keyword', 'learnpress' ); ?></h4>
<div class="keyword" data-suggest= "<?php echo esc_attr( $has_suggestion ); ?>">
	<div class="lp-search-keyword">
		<input autocomplete="off" name="keyword" type="text" value="<?php echo esc_attr( $value ); ?>"
			   placeholder="<?php esc_attr_e( 'Search courses...', 'learnpress' ); ?>">
	</div>
	<?php
	if ( $has_suggestion ) {
		?>
		<ul class="lp-suggest-result"></ul>
		<?php
	}
	?>
</div>
<?php
do_action( 'learn-press/shortcode/course-filter/keyword/before', $data );
