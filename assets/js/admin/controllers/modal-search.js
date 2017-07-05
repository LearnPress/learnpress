/**
 * Question controller
 *
 * @plugin LearnPress
 * @author ThimPress
 * @package LearnPress/AdminJS/Question/Controller
 * @version 3.0
 */
;(function ($) {
    $(function () {

    })

    /**
     * Question controller
     *
     * @param $scope
     */
    window['learn-press.modal-search-controller'] = function ($scope, $compile, $element, $timeout, $http, searchItems) {
        $element = $($element);
        var xxx = $scope;
        angular.extend($scope, {
            timeout: null,
            /**
             * Configuration
             *
             * @type {Object}
             */
            config: null,

            /**
             * Show/Hide navigator
             *
             * @type {boolean}
             */
            showNavigator: false,

            /**
             * There is any items found while searching?
             *
             * @type {boolean}
             */
            hasItems: false,

            /**
             * Need to show the 'Not found' message?
             *
             * @type {boolean}
             */
            showNotFound: false,

            /**
             * The search keywork user has entered
             *
             * @type {string}
             */
            searchTerm: '',

            /**
             * Search data to send to server for searching
             *
             * @type {Object}
             */
            searchData: {},

            /**
             * Items has checked by checking the checkbox.
             *
             * @type {array}
             */
            checkedItems: [],

            /**
             * Items checked data
             */
            checkedItemData: [],

            /**
             * Document
             */
            $doc: $(document),

            /**
             * Init, main entry point.
             */
            init: function () {
                this.config = $element.data();
                $element.on('click', '.search-navigator .page-numbers', function (e) {
                    e.preventDefault();
                    var $scp = $scope.$$childHead,
                        paged = parseInt($(this).html());
                    $scp.$apply(function () {
                        $scp.searchData.paged = paged;
                        $scp.request();
                    });
                })

                $scope.$on('jquery-click', function () {
                    //var $scp = angular.element($el[0]).scope()

                });
            },

            /**
             * Add/Remove item to/from checked list when clicking on checkbox.
             *
             * @param event
             * @param item
             */
            addItemToList: function (event) {
                var item = parseInt(event.target.value);
                this.addOrRemoveCheckedItems(item, event.target.checked, $(event.target.outerHTML).data());
            },

            /**
             * Add/Remove item to/from checked list.
             *
             * @param item Item ID to add or remove
             * @param add True to add and false to remove
             */
            addOrRemoveCheckedItems: function (item, add, data) {
                if (_.isArray(item)) {
                    _.forEach(item, function (i) {
                        this.addOrRemoveCheckedItems(i, add, data);
                    }, this);
                } else {
                    if (true === add) {
                        if ($.inArray(item, this.checkedItems) === -1) {
                            this.checkedItems.push(item);
                            this.checkedItemData.push(data);
                        }
                    } else {
                        var position = $.inArray(item, this.checkedItems);
                        if (position !== -1) {
                            this.checkedItems.splice(position, 1);
                            this.checkedItemData.splice(position, 1);
                        }
                    }
                }
                console.log(item, JSON.stringify(data))
            },

            /**
             * Check if the item is already existing on checked items.
             *
             * @param id
             * @returns {boolean}
             */
            maybeCheckItem: function (id) {
                return !(this.checkedItems.indexOf(parseInt(id)) === -1);
            },

            /**
             * Clear all checked items.
             *
             * @param event
             */
            clearCheckedItems: function (event) {
                // If we are clicking on a button/link
                if (event) {
                    event.preventDefault();
                    if ($(event.target).hasClass('disabled')) {
                        return false;
                    }
                }

                this.checkedItems = [];
                this.checkedItemData = [];
            },

            /**
             * Event on clicking Add button to do something with checked list.
             * Then, clear all items.
             *
             * @param event
             * @returns {boolean}
             */
            selectItems: function (event) {
                if (event) {
                    event.preventDefault();
                    if ($(event.target).hasClass('disabled')) {
                        return false;
                    }
                }
                var callbacks = $(document).triggerHandler('learn-press/modal-search/select-items', [this.checkedItemData]);
                if (callbacks) {
                    callbacks.apply(this);
                }
                this.checkedItems = [];
                this.checkedItemData = [];
            },

            /**
             * Return string for displaying number of checked items per total items.
             *
             * @param msg String
             *
             * @return string
             */
            htmlCountSelectedItems: function (msg) {
                return msg.replace('%d', this.checkedItems.length).replace('%d', searchItems.total);
            },

            /**
             * Call me when you type anything into the search input.
             *
             * @param event
             */
            onSearchInputKeyEvent: function (event) {
                var eventType = event.type,
                    val = event.target.value;

                switch (event.keyCode) {
                    case 13:

                        break;
                    case 38:
                    case 40:

                        break;
                    case 8:
                        break;
                    default:
                        this.searchData.paged = 1;

                }
                this.search();

                if (('keypress' === eventType || 'keydown' === eventType ) && event.keyCode === 13) {
                    event.preventDefault();

                }
            },

            /**
             * Prepare for request
             *
             * @param data
             */
            search: function (data) {
                this.searchData = $.extend(
                    this.config, {
                        'context_id': this.getScreenPostId(),
                        'term': this.searchTerm,
                        exclude: this.getExcludeItems()
                    },
                    data
                );

                this.timeout && $timeout.cancel(this.timeout);

                if (this.searchTerm.length >= 3) {
                    this.timeout = $timeout(this.request.bind(this), 300, false);
                } else {
                    searchItems.results = [];
                }
            },

            /**
             * Set request data
             *
             * @param data Object{ key: value, [...]}
             */
            setRequestData: function (data) {
                _.forEach(data, function (v, k) {
                    this.searchData[k] = v;
                }, this);
            },

            /**
             * Make request to get results.
             */
            request: function () {
                this.searchData.exclude = this.getExcludeItems();
                var data = {
                    data: this.searchData,
                    done: function () {
                        $scope.getElement('.search-navigator').html(searchItems.htmlNavigator);
                        $(document).triggerHandler('learn-press/modal-search-success', searchItems);
                    }
                }
                searchItems.search(data);
            },

            close: function(){
                this.searchTerm = '';
                this.searchData.paged = 1;
                this.hasItems = false;
                this.getElement().removeClass('has-items');
                console.log(this.getElement())
            },

            /**
             * Get item found from request.
             *
             * @returns {Array}
             */
            getItems: function () {
                this.showNavigator = this.getHtmlNavigator() ? true : false;
                this.hasItems = searchItems.results.length;
                this.showNotFound = !this.hasItems && (this.searchTerm || '').length >= 3;
                return searchItems.results;
            },

            /**
             * Get html string of navigation if it's there.
             *
             * @returns {*|null}
             */
            getHtmlNavigator: function () {
                return searchItems.htmlNavigator;
            },

            /**
             * Get Ajax URL
             *
             * @return {string}
             */
            getAjaxUrl: function () {
                return window.location.href.addQueryVar('lp-ajax', 'modal-search-' + this.config.type);
            }
        });
        $scope.init();
    }
})(jQuery);