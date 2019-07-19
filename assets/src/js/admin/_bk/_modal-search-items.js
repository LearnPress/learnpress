/*global jQuery, Backbone, _ */
(function ($, _) {
    'use strict';
    window.$Vue = window.$Vue || Vue;
    var $VueHTTP = $Vue.http;

    $(document).ready(function () {
        $Vue.component('learn-press-modal-search-items', {
            template: '#learn-press-modal-search-items',
            data: function () {
                return {
                    paged: 1,
                    term: '',
                    hasItems: false,
                    selected: []
                }
            },
            watch: {
                show: function (value) {
                    if (value) {
                        $(this.$refs.search).focus();
                    }
                }
            },
            props: ['postType', 'context', 'contextId', 'show', 'callbacks', 'exclude'],
            created: function () {
            },
            mounted: function () {
                this.term = '';
                this.paged = 1;
                this.search();
            },
            methods: {
                doSearch: function (e) {
                    this.term = e.target.value;
                    this.paged = 1;
                    this.search();
                },
                search: _.debounce(function (term) {
                    $('#modal-search-items').addClass('loading');
                    var that = this;
                    $VueHTTP.post(
                        window.location.href, {
                            type: this.postType,
                            context: this.context,
                            context_id: this.contextId,
                            term: term || this.term,
                            paged: this.paged,
                            exclude: this.exclude,
                            'lp-ajax': 'modal_search_items'
                        }, {
                            emulateJSON: true,
                            params: {}
                        }
                    ).then(function (response) {
                        var result = LP.parseJSON(response.body || response.bodyText);
                        that.hasItems = !!_.size(result.items);

                        $('#modal-search-items').removeClass('loading');

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
                        pos = _.indexOf(this.selected, id);

                    if ($chk.is(':checked')) {
                        if (pos === -1) {
                            this.selected.push(id);
                        }
                    } else {
                        if (pos >= 0) {
                            this.selected.splice(pos, 1);
                        }
                    }
                },
                addItems: function () {
                    var close = true;
                    if (this.callbacks && this.callbacks.addItems) {
                        this.callbacks.addItems.call(this);
                    }
                    $(document).triggerHandler('learn-press/add-order-items', this.selected);
                },
                close: function () {
                    this.$emit('close');
                }
            }
        });

        window.LP.$modalSearchItems = new $Vue({
            el: '#vue-modal-search-items',
            data: {
                show: false,
                term: '',
                postType: '',
                callbacks: {},
                exclude: '',
                context: ''
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
                focusSearch: _.debounce(function () {
                    $('input[name="search"]', this.$el).focus();
                }, 200)
            }
        });
    });
}(jQuery, _));
