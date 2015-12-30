/*global jQuery, Backbone, _ */
( function ($, Backbone, _) {
	'use strict';

	LearnPress.ModalSearchItems = function (options) {
		return new LearnPress.ModalSearchItems.View(options);
	};

	LearnPress.ModalSearchItems.View = Backbone.View.extend({
		tagName                  : 'div',
		id                       : 'learn-press-modal-search-items',
		options                  : {
			template: 'tmpl-learn-press-search-items',
			type    : ''
		},
		events                   : {
			'click .close-modal'          : '_closeModal',
			'click .lp-add-item'          : '_addItems',
			'keydown'                     : 'keyboardActions',
			'click input[type="checkbox"]': '_toggleAddItemButtonState'
		},
		searchTimer: null,
		searchTerm: null,
		initialize               : function (options) {
			var that = this;
			this.options = options;
			_.bindAll(this, 'render');
			this.render();
			LearnPress.Hook
				.addAction('learn_press_message_box_before_resize', function () {
					that.$('article').css('height', '');
				})
				.addAction('learn_press_message_box_resize', function (height, app) {
					that.$('article').css({
						height: height - 135
					});
				});
		},
		render                   : function () {
			this.$el.attr({
				tabindex: 0,
				'data-tmpl': this.options.template
			}).append(LearnPress.template(this.options.template));

			$(document.body).css({
				'overflow': 'hidden'
			}).append(this.$el);
			this.search({
				exclude: this.options.exclude
			});

			$(document.body).trigger('learn_press_modal_search_items_loaded', this);
		},
		_toggleAddItemButtonState: function (e) {
			var
				$selected = this.$('li input:checked'),
				$button = this.$('.lp-add-item');
			if ($selected.length) {
				$button.removeAttr('disabled').html($button.attr('data-text') + ' (+' + $selected.length + ')');
			} else {
				$button.attr('disabled', true).html($button.attr('data-text'));
			}
		},
		_search                  : function () {

		},
		_fetchItems              : function (response) {
			this.$('article .lp-list-items').removeClass('lp-ajaxload').html(response.html);
			LearnPress.log(response.html);
			this.refreshModal();
			$(document.body).trigger('learn_press_modal_search_items_fetch', this);
		},
		search                   : function (args) {
			var that = this;
			this.$('article ul').addClass('lp-ajaxload');
			this.refreshModal();
			args = $.extend({
				term   : '',
				exclude: ''
			}, args || {});
			$.ajax({
				url     : LearnPress_Settings.ajax,
				data    : {
					action : 'learnpress_modal_search_items',
					type   : this.options.type,
					term   : args.term,
					exclude: args.exclude,
					context: this.options.context,
					context_id: this.options.context_id
				},
				type    : 'get',
				dataType: 'text',
				success : function (response) {
					response = LearnPress.parseJSON(response);
					that._fetchItems(response);
				}
			})
		},
		_closeModal              : function (e) {
			e.preventDefault();
			$(document.body).trigger('learn_press_modal_search_items_before_remove', this);
			this.undelegateEvents();
			$(document).off('focusin');
			$(document.body).css({
				'overflow': 'auto'
			});
			this.remove();
			LearnPress.MessageBox.hide();
			$(document.body).trigger('learn_press_modal_search_items_removed', this);
		},
		_addItems                : function (e) {
			$(document.body).trigger('learn_press_modal_search_items_response', [this, this.getItems()]);
			this.refreshModal(e);
		},
		refreshModal: function(e){
			this._toggleAddItemButtonState(e);
			$(window).trigger('resize.message-box');
		},
		getItems             : function () {
			return this.$('li input:checked').map(function(){return $(this).closest('li')});
		},
		keyboardActions          : function (e) {
			var that = this,
				button = e.keyCode || e.which;
			// Enter key
			if (e.target.tagName && ( e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea' ) && e.target.value != this.searchTerm ) {
				this.searchTimer && clearTimeout(this.searchTimer);
				this.searchTimer = setTimeout(function(){
					that.search({
						term: e.target.value,
						exclude: LearnPress.Hook.applyFilters( 'learn_press_modal_search_items_exclude', that.options.exclude, that)
					});
				}, 300);
				this.searchTerm = e.target.value;
			}

			// ESC key
			if (27 === button) {
				this._closeModal(e);
			}
		}
	});

}(jQuery, Backbone, _));
