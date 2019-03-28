;
(function ($) {
    "use strict";
    window.$Vue = window.$Vue || Vue;

    $(document).ready(function () {

        var $listItems = $('.list-order-items').find('tbody'),
            $listUsers = $('#list-users'),
            template = function (templateHTML, data) {
                return _.template(templateHTML, {
                    evaluate: /<#([\s\S]+?)#>/g,
                    interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
                    escape: /\{\{([^\}]+?)\}\}(?!\})/g
                })(data);
            },
            advancedListOptions = {
                template: '#tmpl-order-advanced-list-item',
                onRemove: function () {
                    if (this.$el.children().length === 0) {
                        this.$el.append('<li class="user-guest">' + orderOptions.i18n_guest + '</li>')
                    }
                    console.log(this.$el)
                },
                onAdd: function () {
                    this.$el.find('.user-guest').remove();
                }
            },
            orderOptions = lpMetaBoxOrderSettings;

        function getAddedUsers() {
            return $('#list-users').children().map(function () {
                return $(this).data('id')
            }).get();
        }

        function getAddedItems() {
            return $('.list-order-items tbody').children('.order-item-row').map(function () {
                return $(this).data('id')
            }).get();
        }

        if ($listUsers.length) {
            $listUsers.advancedList(advancedListOptions);
            if (orderOptions.users) {
                _.forEach(orderOptions.users, function (userData, userId) {
                    $listUsers.advancedList('add', [
                        template(orderOptions.userTextFormat, userData),
                        userId
                    ]);
                })
            }
        }

        $listItems.on('click', '.remove-order-item', function (e) {
            e.preventDefault();
            var $item = $(this).closest('tr'),
                item_id = $item.data('item_id');

            $item.remove();
            if ($listItems.children(':not(.no-order-items)').length === 0) {
                $listItems.find('.no-order-items').show();
            }

            $Vue.http.post(
                window.location.href, {
                    order_id: $('#post_ID').val(),
                    items: [item_id],
                    'lp-ajax': 'remove_items_from_order'
                }, {
                    emulateJSON: true,
                    params: {}
                }
            ).then(function (response) {
                var result = LP.parseJSON(response.body || response.bodyText);
                $('.order-subtotal').html(result.order_data.subtotal_html);
                $('.order-total').html(result.order_data.total_html);
            });
        });

        $('.order-date.date-picker').on('change', function () {
            var m = this.value.split('-');
            ['aa', 'mm', 'jj'].forEach(function (v, k) {
                $('input[name="' + v + '"]').val(m[k]);
            });
        }).datepicker({
            dateFormat: 'yy-mm-dd',
            numberOfMonths: 1,
            showButtonPanel: true,
            select: function () {
                console.log(arguments)
            }
        });

        $('#learn-press-add-order-item').on('click', function () {
            LP.$modalSearchItems.open({
                data: {
                    postType: 'lp_course',
                    context: 'order-items',
                    contextId: $('#post_ID').val(),
                    exclude: getAddedItems(),
                    show: true
                },
                callbacks: {
                    addItems: function () {
                        var that = this;
                        $Vue.http.post(
                            window.location.href, {
                                order_id: this.contextId,
                                items: this.selected,
                                'lp-ajax': 'add_items_to_order'
                            }, {
                                emulateJSON: true,
                                params: {}
                            }
                        ).then(function (response) {
                            var result = LP.parseJSON(response.body || response.bodyText),
                                $noItem = $listItems.find('.no-order-items').hide();
                            $(result.item_html).insertBefore($noItem);
                            $('.order-subtotal').html(result.order_data.subtotal_html);
                            $('.order-total').html(result.order_data.total_html);
                        });
                        this.close();
                    }
                }
            });
        });

        $(document).on('click', '.change-user', function (e) {
            e.preventDefault();
            LP.$modalSearchUsers.open({
                data: {
                    context: 'order-items',
                    contextId: $('#post_ID').val(),
                    show: true,
                    multiple: $(this).data('multiple') === 'yes',
                    exclude: getAddedUsers(),
                    textFormat: orderOptions.userTextFormat
                },
                callbacks: {
                    addUsers: function (data) {
                        if (this.multiple) {
                            if (!$listUsers.length) {
                                $listUsers = $(LP.template('tmpl-order-data-user')({multiple: true}));
                                $listUsers.advancedList(advancedListOptions);

                                $('.order-data-user').replaceWith($listUsers);
                            }
                            for (var i = 0; i < this.selected.length; i++) {
                                $listUsers.advancedList('add', [template(this.textFormat, this.selected[i]), this.selected[i].id]);
                            }
                        } else {
                            var $html = LP.template('tmpl-order-data-user')({
                                name: template(this.textFormat, this.selected[0]),
                                id: this.selected[0].id
                            });

                            $('.order-data-user').replaceWith($html);
                        }

                        this.close();
                    }
                }
            });
        });
    });
/*
    var LP_Order_View = window.LP_Order_View = Backbone.View.extend({
        el: 'body',
        events: {
            'click #learn-press-add-order-item': '_addItem',
            'click #learn-press-calculate-order-total': 'calculateTotal',
            'click #learn-press-courses-result a[data-id]': 'addItem',
            'click .remove-order-item': 'removeItem',
            'keyup #learn-press-search-item-term': 'searchItems',
            'change select[name="order-status"]': '_updateDescription'
        },
        initialize: function () {
            _.bindAll(this, 'resetModal', 'updateModal', '_updateDescription');
            LP.Hook.addAction('learn_press_message_box_before_resize', this.resetModal);
            LP.Hook.addAction('learn_press_message_box_resize', this.updateModal);

            var $selectUsers = this.$('#order-customer');
            if ($selectUsers.attr('multiple') == 'multiple') {
                $selectUsers.select2({
                    width: $('#minor-publishing .misc-pub-section').innerWidth() - 20
                });
            }
            var $add_new_h2 = $('body.post-type-lp_order').find('.page-title-action, .add-new-h2'),
                $add_h2 = $('<a href="post-new.php?post_type=lp_order&multi-users=yes" class="page-title-action add-new-h2">Add order multiple users</a>');
            $add_h2
                .insertAfter($add_new_h2);

            $('.wp-list-table #order_status').css('width', '135px');

            $('select[name="order-status"]').on('change', function () {
                var $sel = $(this),
                    $sec = $('.order-action-section'),
                    status = $sel.data('status'),
                    order_id = +$sel.closest('tr.type-lp_order').find('td.column-title strong a.row-title').text().replace('#', '');

                // console.log(order_id);
                // console.log($sel.val());

                $.ajax({
                    url: LP_Settings.ajax,
                    data: {
                        action: 'learnpress_update_order_status',
                        order_id: order_id,
                        value: $sel.val(),
                    },
                    dataType: 'text',
                    type: 'post',
                    success: function (response) {
                        LP.log(response);
                        response = LP.parseJSON(response);
                        if (response.result === 'success') {
                            var $order_table = that.$('.order-items'),
                                $no_item = $order_table.find('.no-order-items');
                            $(response.item_html).insertBefore($no_item);
                            $order_table.find('.order-subtotal').html(response.order_data.subtotal_html);
                            $order_table.find('.order-total').html(response.order_data.total_html);

                            $item.remove();
                            $no_item.addClass('hide-if-js');
                        }
                    }
                });

                $sec.toggleClass('hide-if-js', status != $sel.val());
            }).trigger('init');


            $(document).on('learn_press_modal_search_items_response', this.addItem2);
            this.userSuggest();
        },
        _updateDescription: function (e) {
            var $sel = $(e.target),
                $option = $sel.find('option:selected');
            $sel.siblings('.description').hide().html($option.attr('data-desc'))
                .removeClass(function (c, d) {
                    var m = d.match(/(lp-.*)\s?/);
                    return m ? m[0] : '';
                }).addClass($option.val()).show();
        },
        userSuggest: function () {
            var id = ( typeof current_site_id !== 'undefined' ) ? '&site_id=' + current_site_id : '';
            var position = {offset: '0, -1'};
            if (typeof isRtl !== 'undefined' && isRtl) {
                position.my = 'right top';
                position.at = 'right bottom';
            }
            $('.wp-suggest-user').each(function () {
                var $this = $(this),
                    autocompleteType = ( typeof $this.data('autocompleteType') !== 'undefined' ) ? $this.data('autocompleteType') : 'add',
                    autocompleteField = ( typeof $this.data('autocompleteField') !== 'undefined' ) ? $this.data('autocompleteField') : 'user_login';

                $this.autocomplete({
                    source: LP_Settings.ajax + '?action=learnpress_search_users&autocomplete_type=' + autocompleteType + '&autocomplete_field=' + autocompleteField + id,
                    delay: 500,
                    minLength: 2,
                    position: position,
                    open: function () {
                        $(this).addClass('open');
                    },
                    close: function () {
                        $(this).removeClass('open');
                    },
                    select: function (a, b) {
                        LP.log(a, b);
                    }
                });
            });
        },
        resetModal: function (height, $app) {
            this.$('#learn-press-courses-result').css('height', height - 120).css('overflow', 'auto');
        },
        updateModal: function ($app) {
            this.$('#learn-press-courses-result').css('height', '').css('overflow', '');
        },
        showFormItems: function (type) {
            var $form = LP.ModalSearchItems({
                template: 'tmpl-learn-press-search-items',
                type: 'lp_course',
                //section   : $button.closest('.curriculum-section'),
                context: 'course-items',
                context_id: $('#post_ID').val(),
                //exclude   : this.getSelectedItems(),
                notices: false
            });
            LP.MessageBox.show($form.$el);
            $form.$el.find('header input').focus();

        },
        _addItem: function (e) {
            this.showFormItems('lp_course', 'add-lp_course')
//			var $form = $('#learn-press-modal-add-order-courses');
//			if ($form.length == 0) {
//				$form = $(wp.template('learn-press-modal-add-order-courses')());
//			}
//			LP.MessageBox.show($form);
        },
        addItem2: function (e, $view, $items) {
            var that = this;
            var selected = $items; //$form.find('li:visible input:checked'),
            if (e.ctrlKey) {
                //return true;
            }
            var ids = [];
            selected.each(function () {
                ids.push($(this).data('id'));
            });

            $.ajax({
                url: LP_Settings.ajax,
                data: {
                    action: 'learnpress_add_item_to_order',
                    order_id: parseInt($('input#post_ID').val()),
                    item_id: ids,
                    nonce: $('#learn-press-modal-add-order-courses').attr('data-nonce')
                },
                dataType: 'text',
                type: 'post',
                success: function (response) {
                    LP.log(response);
                    response = LP.parseJSON(response);
                    if (response.result === 'success') {
                        var $order_table = $('.order-items'),
                            $no_item = $order_table.find('.no-order-items');
                        $(response.item_html).insertBefore($no_item);
                        $order_table.find('.order-subtotal').html(response.order_data.subtotal_html);
                        $order_table.find('.order-total').html(response.order_data.total_html);

                        selected.each(function () {
                            console.log($(this));
                            $(this).remove();
                        });
                        $no_item.addClass('hide-if-js');
                    }
                }
            });

            return false;
            // restart sortable
//				 _makeListSortable();
        },
        addItem: function (e, ids) {
            console.log('add item to order');

            var that = this,
                $item = $(e.target);
            if (e.ctrlKey) {
                //return true;
            }
            $.ajax({
                url: LP_Settings.ajax,
                data: {
                    action: 'learnpress_add_item_to_order',
                    order_id: parseInt($('input#post_ID').val()),
                    item_id: parseInt($item.attr('data-id')),
                    nonce: $('#learn-press-modal-add-order-courses').attr('data-nonce')
                },
                dataType: 'text',
                type: 'post',
                success: function (response) {
                    LP.log(response);
                    response = LP.parseJSON(response);
                    if (response.result === 'success') {
                        var $order_table = that.$('.order-items'),
                            $no_item = $order_table.find('.no-order-items');
                        $(response.item_html).insertBefore($no_item);
                        $order_table.find('.order-subtotal').html(response.order_data.subtotal_html);
                        $order_table.find('.order-total').html(response.order_data.total_html);

                        $item.remove();
                        $no_item.addClass('hide-if-js');
                    }
                }
            });

            return false;
        },
        removeItem: function (e) {
            e.preventDefault();
            var that = this,
                $item = $(e.target).closest('tr'),
                item_id = parseInt($item.attr('data-item_id'));
            if (!item_id) {
                return;
            }
            $.ajax({
                url: LP_Settings.ajax,
                data: {
                    action: 'learnpress_remove_order_item',
                    order_id: $('#post_ID').val(),
                    item_id: item_id,
                    remove_nonce: $item.attr('data-remove_nonce')
                },
                type: 'post',
                dataType: 'text',
                success: function (response) {
                    response = LP.parseJSON(response);
                    if (response.result === 'success') {
                        var $order_table = that.$('.order-items'),
                            $no_item = $order_table.find('.no-order-items'),
                            $other_items = $item.siblings().filter(function () {
                                return !$(this).is($no_item);
                            });
                        $order_table.find('.order-subtotal').html(response.order_data.subtotal_html);
                        $order_table.find('.order-total').html(response.order_data.total_html);
                        $item.remove();
                        $other_items.length == 0 && $no_item.removeClass('hide-if-js');
                    }
                }
            });
        },
        calculateTotal: function (e) {
            LP.log(e);
        },
        fetchResults: function (results) {
            var $list = this.$('#learn-press-courses-result'),
                $t = $list.find('li').filter(function () {
                    return $(this).hasClass('lp-search-no-results') ? true : $(this).remove() && false;
                });
            _.each(results, function (i, k) {
                $('<li><a href="' + i.permalink + '" data-id="' + k + '">' + i.title + '</a></li>').insertBefore($t);
            });
        },
        searchItems: function (e) {
            var that = this,
                $input = $(e.target),
                timer = $input.data('timer'),
                term = $input.val(),
                _search = function () {
                    $.ajax({
                        url: LP_Settings.ajax,
                        data: {
                            action: 'learnpress_search_courses',
                            nonce: $input.attr('data-nonce'),
                            term: term
                        },
                        type: 'get',
                        dataType: 'text',
                        success: function (response) {
                            response = LP.parseJSON(response);
                            that.fetchResults(response);
                        }
                    });
                };
            timer && clearTimeout(timer);
            if ((term + '').length >= 3) {
                timer = setTimeout(_search, 250);
                $input.data('timer', timer);
            }
        }
    });

    $(document).ready(function () {
        new LP_Order_View();
    });*/
})(jQuery);