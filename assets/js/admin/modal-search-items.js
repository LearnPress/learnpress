/*global jQuery, Backbone, _ */
( function ($, Backbone, _) {
	'use strict';

	LP.ModalSearchItems = function (options) {
		return new LP.ModalSearchItems.View(options);
	};

	LP.ModalSearchItems.View = Backbone.View.extend({
		tagName                  : 'div',
		id                       : 'learn-press-modal-search-items',
		options                  : {
			template: 'tmpl-learn-press-search-items',
			type    : ''
		},
		events                   : {
			'click .close-modal'           : '_closeModal',
			'click .lp-add-item'           : '_addItems',
			'keydown'                      : 'keyboardActions',
			'change input[type="checkbox"]': '_toggleAddItemButtonState',
			'click .chk-checkall'          : '_checkAll'
		},
		searchTimer              : null,
		searchTerm               : null,
		initialize               : function (options) {
			var that = this;
			this.options = options;
			_.bindAll(this, 'render');
			this.render();
			LP.Hook
				.addAction('learn_press_message_box_before_resize', function () {
					that.$('article').css('height', '');
				})
				.addAction('learn_press_message_box_resize', function (height, app) {
					that.$('article').css({
//						height: height - 135
                                            height: height - 50 - $( '#learn-press-modal-search-items header' ).height() - $( '#learn-press-modal-search-items footer' ).height()
					});
				});
		},
		render                   : function () {
			this.$el.attr({
				tabindex   : 0,
				'data-tmpl': this.options.template
			}).append(LP.template(this.options.template, this.options));

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
				$button.each(function () {
					var $btn = $(this);
					$btn.removeAttr('disabled').html($btn.attr('data-text') + ' (+' + $selected.length + ')');
				});

			} else {
				$button.each(function () {
					var $btn = $(this);
					$btn.attr('disabled', true).html($btn.attr('data-text'));
				});

			}
		},
		_checkAll                : function (e) {
			this.$('.lp-list-items li input[type="checkbox"]').prop('checked', e.target.checked).first().trigger('change');
		},
		_search                  : function () {

		},
		_fetchItems              : function (response) {
			this.$('article .lp-list-items').removeClass('lp-ajaxload').html(response.html);
                        if ( this.$('.learnpress-search-notices').length == 0 ) {
//                            console.debug( $('#learn-press-modal-search-items footer') );
                            $('#learn-press-modal-search-items header').prepend( response.notices );
                        }
			LP.log(response.html);
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

			var current_items = [],
				items = $('.order-items tr[data-item_id]');

			items.each(function () {
				current_items.push($(this).data('item_id'));
            });

			console.log(current_items);

			$.ajax({
				url     : LP_Settings.ajax,
				data    : {
					action    		: 'learnpress_modal_search_items',
					type      		: this.options.type,
					term      		: args.term,
					exclude   		: args.exclude,
					context   		: this.options.context,
					context_id		: this.options.context_id,
					current_items	: current_items,
				},
				type    : 'get',
				dataType: 'text',
				success : function (response) {
					response = LP.parseJSON(response);
					that._fetchItems(response);
//                                        console.debug( that.$('#learn-press-modal-search-items footer') );
//                                        that.$('#learn-press-modal-search-items footer').append( response.notices );
				}
			});
		},
		_closeModal              : function (e) {
			e.preventDefault();
			$(document.body).trigger('learn_press_modal_search_items_before_remove', this);
			this.undelegateEvents();
			$(document).off('focusin');
//			$(document.body).css({
//				'overflow': 'auto'
//			});
                        $(document.body).removeAttr( 'style' );
			this.remove();
			LP.MessageBox.hide();
			$(document.body).trigger('learn_press_modal_search_items_removed', this);
		},
		_addItems                : function (e) {
                        e.preventDefault();
			$(document.body).trigger('learn_press_modal_search_items_response', [this, this.getItems()]);
			this.refreshModal(e);
			if ($(e.target).hasClass('close')) {
				this.$('.close-modal').trigger('click');
			}
		},
		refreshModal             : function (e) {
			this._toggleAddItemButtonState(e);
			this.$('.chk-checkall').prop('disabled', this.$('.lp-list-items li input[type="checkbox"]').length == 0);
			$(window).trigger('resize.message-box');
		},
		getItems                 : function () {
			return this.$('li input:checked').map(function () {
				return $(this).closest('li');
			});
		},
		keyboardActions          : function (e) {
			var that = this,
				button = e.keyCode || e.which;
			// Enter key
			if (e.target.tagName && ( e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea' ) && e.target.value != this.searchTerm) {
				this.searchTimer && clearTimeout(this.searchTimer);
				this.searchTimer = setTimeout(function () {
					that.search({
						term   : e.target.value,
						exclude: LP.Hook.applyFilters('learn_press_modal_search_items_exclude', that.options.exclude, that)
					});
				}, 300);
				this.searchTerm = e.target.value;
				this.$('.chk-checkall').prop('checked', false);
			}

			// ESC key
			if (27 === button) {
				this._closeModal(e);
			}
		}
	});

}(jQuery, Backbone, _));
