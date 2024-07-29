<script type="text/template" id="learn-press-modal-search-items">
	<div id="modal-search-items" class="learn-press-modal">
		<div class="modal-overlay">

		</div>
		<div class="modal-wrapper">
			<div class="modal-container">
				<header><?php echo wp_kses_post( $this->_options['title'] ); ?></header>
				<article>
					<input type="text" name="search" value="" placeholder="<?php esc_html_e( 'Search items', 'learnpress' ); ?>" autocomplete="off"/>
					<ul class="search-results"></ul>
				</article>
				<footer>
					<div class="search-nav">
					</div>
					<button class="button close"><?php echo wp_kses_post( $this->_options['close_button'] ); ?></button>
					<button class="button button-primary add" style="display:none"><?php echo wp_kses_post( $this->_options['add_button'] ); ?></button>
				</footer>
			</div>
		</div>
	</div>
</script>
<?php

?>
<div id="container-modal-search-items" style="position: relative;z-index: 10000;">
</div>



