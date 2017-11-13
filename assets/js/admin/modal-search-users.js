/*global jQuery, Backbone, _ */
( function ($, Vue, _) {
    'use strict';

    $(document).ready(function () {
        Vue.component('learn-press-modal-search-users', {
            template: '#learn-press-modal-search-users',
            data: function () {
                return {
                    paged: 1,
                    term: '',
                    hasUsers: false,
                    selected: []
                }
            },
            watch: {
                show: function (value) {
                    if (value) {
                        $(this.$refs.search).focus();
                    }
                    console.log('dsfsdf')

                }
            },
            props: ['multiple', 'context', 'contextId', 'show', 'callbacks', 'textFormat'],
            created: function () {

            },
            methods: {
                doSearch: function (e) {
                    this.term = e.target.value;
                    this.paged = 1;
                    this.search();
                },
                search: _.debounce(function (term) {
                    var that = this;
                    Vue.http.post(
                        window.location.href, {
                            type: this.postType,
                            context: this.context,
                            context_id: this.contextId,
                            term: term || this.term,
                            paged: this.paged,
                            multiple: this.multiple ? 'yes': 'no',
                            text_format: this.textFormat,
                            'lp-ajax': 'modal-search-users'
                        }, {
                            emulateJSON: true,
                            params: {}
                        }
                    ).then(function (response) {
                        var result = LP.parseJSON(response.body);
                        that.hasUsers = !!_.size(result.users);

                        $(that.$el).find('.search-results').html(result.html).find('input[type="checkbox"]').each(function () {
                            var id = parseInt($(this).val());
                            if (_.indexOf(that.selected, id) >= 0) {
                                this.checked = true;
                            }
                        });
                        _.debounce(function () {
                            $(that.$el).find('.search-nav').html(result.nav).find('a, span').addClass('button').filter('span').addClass('disabled');
                        }, 10)();
                    });
                }, 500),
                loadPage: function (e) {
                    e.preventDefault();
                    var $button = $(e.target);
                    if ($button.is('span')) {
                        return;
                    }
                    if ($button.hasClass('next')) {
                        this.paged++;
                    } else if ($button.hasClass('prev')) {
                        this.paged--;
                    } else {
                        var paged = $button.html();
                        this.paged = parseInt(paged);
                    }
                    this.search();
                },
                selectItem: function (e) {
                    var $select = $(e.target).closest('li'),
                        $chk = $select.find('input[type="checkbox"]'),
                        id = parseInt($chk.val()),
                        //pos = _.indexOf(this.selected, id),
                        pos = _.findLastIndex(this.selected, {id: id});
                    if(this.multiple) {
                        if ($chk.is(':checked')) {
                            if (pos === -1) {
                                this.selected.push($select.closest('li').data('data'));
                            }
                        } else {
                            if (pos >= 0) {
                                this.selected.splice(pos, 1);
                            }
                        }
                    }else{
                        e.preventDefault();
                        this.selected = [$select.closest('li').data('data')];
                        this.addUsers();
                    }
                },
                addUsers:function(){
                    var $els = $(this.$el).find('.lp-result-item');
                    if(this.callbacks && this.callbacks.addUsers){
                        this.callbacks.addUsers.call(this, this.selected);
                    }
                    $(document).triggerHandler('learn-press/modal-add-users', this.selected);
                },
                close: function () {
                    this.$emit('close');
                }
            }
        });

        window.LP.$modalSearchUsers = new Vue({
            el: '#vue-modal-search-users',
            data: {
                show: false,
                term: '',
                multiple: false,
                callbacks: {},
                textFormat: '{{display_name}} ({{email}})'
            },
            methods: {
                open: function (options) {
                    _.each(options.data, function (v, k) {
                        this[k] = v;
                    }, this);
                    this.callbacks = options.callbacks;
                    this.focusSearch();
                },
                close: function () {
                    this.show = false;
                },
                focusSearch: _.debounce(function(){
                    $('input[name="search"]', this.$el).focus();
                }, 200)
            }
        });
    });

// return;
// 	LP.ModalSearchUsers = function (options) {
// 		return new LP.ModalSearchUsers.View(options);
// 	};
//
// 	LP.ModalSearchUsers.View = Backbone.View.extend({
// 		tagName                  : 'div',
// 		id                       : 'learn-press-modal-search-items',
// 		options                  : {
// 			template: 'tmpl-learn-press-search-items',
// 			type    : ''
// 		},
// 		events                   : {
// 			'click .close-modal'           : '_closeModal',
// 			'click .lp-add-item'           : '_addUsers',
// 			'keydown'                      : 'keyboardActions',
// 			'change input[type="checkbox"]': '_toggleAddItemButtonState',
// 			'click .chk-checkall'          : '_checkAll'
// 		},
// 		searchTimer              : null,
// 		searchTerm               : null,
// 		initialize               : function (options) {
// 			var that = this;
// 			this.options = options;
// 			_.bindAll(this, 'render');
// 			this.render();
// 			LP.Hook
// 				.addAction('learn_press_message_box_before_resize', function () {
// 					that.$('article').css('height', '');
// 				})
// 				.addAction('learn_press_message_box_resize', function (height, app) {
// 					that.$('article').css({
// //						height: height - 135
//                                             height: height - 50 - $( '#learn-press-modal-search-items header' ).height() - $( '#learn-press-modal-search-items footer' ).height()
// 					});
// 				});
// 		},
// 		render                   : function () {
// 			this.$el.attr({
// 				tabindex   : 0,
// 				'data-tmpl': this.options.template
// 			}).append(LP.template(this.options.template, this.options));
//
// 			$(document.body).css({
// 				'overflow': 'hidden'
// 			}).append(this.$el);
// 			this.search({
// 				exclude: this.options.exclude
// 			});
//
// 			$(document.body).trigger('learn_press_modal_search_items_loaded', this);
// 		},
// 		_toggleAddItemButtonState: function (e) {
// 			var
// 				$selected = this.$('li input:checked'),
// 				$button = this.$('.lp-add-item');
// 			if ($selected.length) {
// 				$button.each(function () {
// 					var $btn = $(this);
// 					$btn.removeAttr('disabled').html($btn.attr('data-text') + ' (+' + $selected.length + ')');
// 				});
//
// 			} else {
// 				$button.each(function () {
// 					var $btn = $(this);
// 					$btn.attr('disabled', true).html($btn.attr('data-text'));
// 				});
//
// 			}
// 		},
// 		_checkAll                : function (e) {
// 			this.$('.lp-list-items li input[type="checkbox"]').prop('checked', e.target.checked).first().trigger('change');
// 		},
// 		_search                  : function () {
//
// 		},
// 		_fetchUsers              : function (response) {
// 			this.$('article .lp-list-items').removeClass('lp-ajaxload').html(response.html);
//                         if ( this.$('.learnpress-search-notices').length == 0 ) {
// //                            console.debug( $('#learn-press-modal-search-items footer') );
//                             $('#learn-press-modal-search-items header').prepend( response.notices );
//                         }
// 			LP.log(response.html);
// 			this.refreshModal();
// 			$(document.body).trigger('learn_press_modal_search_items_fetch', this);
// 		},
// 		search                   : function (args) {
// 			var that = this;
// 			this.$('article ul').addClass('lp-ajaxload');
// 			this.refreshModal();
// 			args = $.extend({
// 				term   : '',
// 				exclude: ''
// 			}, args || {});
//
// 			var current_items = [],
// 				items = $('.order-items tr[data-item_id]');
//
// 			items.each(function () {
// 				current_items.push($(this).data('item_id'));
//             });
//
// 			console.log(current_items);
//
// 			$.ajax({
// 				url     : LP_Settings.ajax,
// 				data    : {
// 					action    		: 'learnpress_modal_search_items',
// 					type      		: this.options.type,
// 					term      		: args.term,
// 					exclude   		: args.exclude,
// 					context   		: this.options.context,
// 					context_id		: this.options.context_id,
// 					current_items	: current_items,
// 				},
// 				type    : 'get',
// 				dataType: 'text',
// 				success : function (response) {
// 					response = LP.parseJSON(response);
// 					that._fetchUsers(response);
// //                                        console.debug( that.$('#learn-press-modal-search-items footer') );
// //                                        that.$('#learn-press-modal-search-items footer').append( response.notices );
// 				}
// 			});
// 		},
// 		_closeModal              : function (e) {
// 			e.preventDefault();
// 			$(document.body).trigger('learn_press_modal_search_items_before_remove', this);
// 			this.undelegateEvents();
// 			$(document).off('focusin');
// //			$(document.body).css({
// //				'overflow': 'auto'
// //			});
//                         $(document.body).removeAttr( 'style' );
// 			this.remove();
// 			LP.MessageBox.hide();
// 			$(document.body).trigger('learn_press_modal_search_items_removed', this);
// 		},
// 		_addUsers                : function (e) {
//                         e.preventDefault();
// 			$(document.body).trigger('learn_press_modal_search_items_response', [this, this.getUsers()]);
// 			this.refreshModal(e);
// 			if ($(e.target).hasClass('close')) {
// 				this.$('.close-modal').trigger('click');
// 			}
// 		},
// 		refreshModal             : function (e) {
// 			this._toggleAddItemButtonState(e);
// 			this.$('.chk-checkall').prop('disabled', this.$('.lp-list-items li input[type="checkbox"]').length == 0);
// 			$(window).trigger('resize.message-box');
// 		},
// 		getUsers                 : function () {
// 			return this.$('li input:checked').map(function () {
// 				return $(this).closest('li');
// 			});
// 		},
// 		keyboardActions          : function (e) {
// 			var that = this,
// 				button = e.keyCode || e.which;
// 			// Enter key
// 			if (e.target.tagName && ( e.target.tagName.toLowerCase() === 'input' || e.target.tagName.toLowerCase() === 'textarea' ) && e.target.value != this.searchTerm) {
// 				this.searchTimer && clearTimeout(this.searchTimer);
// 				this.searchTimer = setTimeout(function () {
// 					that.search({
// 						term   : e.target.value,
// 						exclude: LP.Hook.applyFilters('learn_press_modal_search_items_exclude', that.options.exclude, that)
// 					});
// 				}, 300);
// 				this.searchTerm = e.target.value;
// 				this.$('.chk-checkall').prop('checked', false);
// 			}
//
// 			// ESC key
// 			if (27 === button) {
// 				this._closeModal(e);
// 			}
// 		}
// 	});

}(jQuery, Vue, _));
