;(function ($) {
    window['learn-press.controllers.question.search'] = function ($scope, $compile, $element, $timeout, $http) {
        $element = $($element);
        angular.extend($scope, {
            searchData: {
                type: 'lp_question',
                exclude: [],
                term: '',
                context: '',
                context_id: ''
            },
            searchTimer: null,
            selectedItems: [],
            $resultsContainer: null,
            $overlay: '',
            init: function () {
                $('#course-editor').on('click', '#learn-press-ajax-search-overlay', function () {
                    $('#course-editor').removeClass('ajax-search');
                    $scope.$resultsContainer.html('');
                    $scope.searchData.term = '';
                    $scope.$apply();
                });
                this.$doc.on('learn-press/added-quiz-question', function (e, id) {
                    $scope.$resultsContainer.find('.lp-result-item[data-id="' + id + '"]').remove();
                });
                this.$overlay = $('<div id="learn-press-ajax-search-overlay"></div>').insertBefore(this.getElement())
                this.$resultsContainer = this.getElement('.lp-search-items');
                this.searchData.context = this.getScreenPostType();
                this.searchData.context_id = this.getScreenPostId();
            },

            startSearch: function (event) {
                var newTerm = event.target.value;
                if (this.searchData.term === newTerm || !newTerm) {
                    return;
                }
                this.searchData.term = newTerm;
                this.searchData.exclude = this.getExcludeItems();
                this.selectedItems = [];
                this.$resultsContainer.children().remove();

                $timeout.cancel(this.searchTimer);
                this.searchTimer = $timeout(function () {
                    $http({
                        url: $scope.getAjaxUrl('lp-ajax=ajax_search_items'),
                        data: $scope.searchData,
                        method: 'post'
                    }).then(function (r) {
                        $('#course-editor').addClass('ajax-search');
                        var response = $scope.getHttpJson(r),
                            $items = $(response.html);
                        $items.each(function () {
                            var $chk = $(this).find('input[type="checkbox"]').attr('ng-click', 'selectItem($event)');
                            $(this).find('.lp-item-text').append('<a class="lp-add-item" href="" ng-click="addItem($event, ' + parseInt($chk.val()) + ')" href="">add</a>');
                            $scope._addItemToResults(this);
                        })
                    });
                }, 300)

            },
            hasResults: function () {
                return this.$resultsContainer.children('.lp-result-item').length;

            },
            hasSelectedItems: function () {
                return this.$resultsContainer.find('.lp-result-item input[type="checkbox"]:checked').length;
            },
            selectItem: function (event) {
                var item = parseInt(event.target.value);
                if (event.target.checked) {
                    if ($.inArray(item, this.selectedItems) === -1) {
                        this.selectedItems.push(item);
                    }
                } else {
                    var position = $.inArray(item, this.selectedItems);
                    if (position !== -1) {
                        this.selectedItems.splice(position, 1);
                    }
                }
            },
            getExcludeItems: function () {
                var $els = $('#learn-press-questions').children('.learn-press-question'),
                    items = [];
                _.forEach($els, function (el, i) {
                    items.push($(el).data('dbid'));
                });
                return items;
            },
            addItem: function (event, item) {
                event.preventDefault();
                var position = $.inArray(item, this.selectedItems);
                if (position !== -1) {
                    this.selectedItems.splice(position, 1);
                }
                var ctrl = angular.element($('#learn-press-quiz-questions')[0]).scope();
                ctrl.addExistsQuestions([item])
            },
            addBulkItems: function () {
                var ctrl = angular.element($('#learn-press-quiz-questions')[0]).scope();
                ctrl.addExistsQuestions(this.selectedItems)
            },
            getElement: function (selector) {
                return selector ? $element.find(selector) : $element;
            },
            hideSearchResults: function () {

            },
            _addItemToResults: function (item) {
                var $newItem = $compile(item)($scope, function (clonedElement, scope) {
                    return clonedElement;
                });
                this.$resultsContainer.append($newItem);
            }
        });
        $scope.init();
    }
})(jQuery);