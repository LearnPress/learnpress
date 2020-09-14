<script type="text/x-template" id="learn-press-modal-search-items">
	<div id="modal-search-items" class="learn-press-modal">
		<div class="modal-overlay">

		</div>
		<div class="modal-wrapper">
			<div class="modal-container">
				<header><?php echo $this->_options['title']; ?></header>
				<article>
					<input type="text" name="search" @keyup="doSearch" ref="term" value="" placeholder="<?php esc_html_e( 'Search items', 'learnpress' ); ?>" autocomplete="off"/>
					<ul class="search-results" @click="selectItem"></ul>
				</article>
				<footer>
					<div class="search-nav" @click="loadPage" v-if="hasItems">
					</div>
					<button class="button"
							@click="close"><?php echo $this->_options['close_button']; ?></button>
					<button class="button button-primary"
							@click="addItems"
							v-if="selected.length"><?php echo $this->_options['add_button']; ?></button>
				</footer>
			</div>
		</div>
	</div>
</script>
<div id="vue-modal-search-items" style="position: relative;z-index: 10000;">
	<learn-press-modal-search-items v-if="show" :post-type="postType" :term="term" :context="context"
									:context-id="contextId" :show="show" :callbacks="callbacks"
									:exclude="exclude"
									v-on:close="close">
	</learn-press-modal-search-items>
</div>
