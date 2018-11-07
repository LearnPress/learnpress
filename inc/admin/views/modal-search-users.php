<script type="text/x-template" id="learn-press-modal-search-users">
	<div id="modal-search-users" class="learn-press-modal">
		<div class="modal-overlay">

		</div>
		<div class="modal-wrapper">
			<div class="modal-container">
				<header><?php echo $this->_options['title']; ?></header>
				<article>
					<input type="text" name="search" @keyup="doSearch" ref="term" placeholder="<?php _e('Search users', 'learnpress');?>" autocomplete="off"/>
					<ul class="search-results" @click="selectItem"></ul>
				</article>
				<footer>
					<div class="search-nav" @click="loadPage" v-if="hasUsers">
					</div>
					<button class="button"
					        @click="close"><?php echo $this->_options['close_button']; ?></button>
                    <button class="button button-primary"
                            @click="addUsers"
                            v-if="selected.length && multiple"><?php echo $this->_options['add_button']; ?></button>
				</footer>
			</div>
		</div>
	</div>
</script>
<div id="vue-modal-search-users" style="position: relative;z-index: 10000;">
	<learn-press-modal-search-users v-if="show" :multiple="multiple" :term="term" :contex="context"
	                                :context-id="contextId" :show="show" :callbacks="callbacks"
                                    :text-format="textFormat"
                                    :exclude="exclude"
	                                v-on:close="close">
	</learn-press-modal-search-users>
</div>