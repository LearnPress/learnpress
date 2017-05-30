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
            isShowingResults: false,
            searchTimer: null,
            selectedItems: [],
            itemPosition: 0,
            $resultsContainer: null,
            $overlay: '',
            $resultItems: '',
            $input: null,
            init: function () {
                $('#course-editor').on('click', '#learn-press-ajax-search-overlay', function () {
                    //$('#course-editor').removeClass('ajax-search');
                    $scope.isShowingResults = false;
                    $scope.$resultsContainer.html('');
                    $scope.searchData.term = '';
                    $scope.$apply();
                });
                this.$doc.on('learn-press/added-quiz-question', function (e, id) {
                    $scope.onAddedQuizQuestion(e, id);
                });
                this.$overlay = $('<div id="learn-press-ajax-search-overlay"></div>');
                this.$resultsContainer = this.getElement('.lp-search-items');
                this.searchData.context = this.getScreenPostType();
                this.searchData.context_id = this.getScreenPostId();
                this.$overlay.insertAfter(this.getElement());//
                this.$input = this.getElement('.lp-search-term');
            },

            onAddedQuizQuestion: function (e, id) {
                var $item = this.$resultsContainer.find('.lp-result-item[data-id="' + id + '"]'),
                    $next = $item.next(),
                    $prev = $item.prev();
                $item.remove();
                if (!$next.length) {
                    if ($prev.length) {
                        this.itemPosition--;
                    } else {
                        this.itemPosition = -1;
                    }
                }
                if (this.itemPosition == -1) {
                    this.isShowingResults = false;
                } else {
                    this.$resultItems = this.$resultsContainer.children('.lp-result-item');
                    this._setItemSelected();
                }
            },
            startSearch: function (event) {
                if (event.type !== 'keyup') {
                    return;
                }

                var newTerm = event.target.value;
                if (!newTerm) {
                    this.isShowingResults = false;
                    return;
                }
                if (this.searchData.term === newTerm) {
                    return;
                }
                this.searchData.term = newTerm;
                this.searchData.exclude = this.getExcludeItems();
                this.selectedItems = [];
                this.itemPosition = 0;
                this.$resultsContainer.children().remove();

                $timeout.cancel(this.searchTimer);
                this.searchTimer = $timeout(function () {
                    $http({
                        url: $scope.getAjaxUrl('lp-ajax=ajax_search_items'),
                        data: $scope.searchData,
                        method: 'post'
                    }).then(function (r) {
                        //$('#course-editor').addClass('ajax-search');
                        var response = $scope.getHttpJson(r);
                        $scope.isShowingResults = $(response.html).each(function () {
                                var $item = $(this),
                                    $chk = $item.find('input[type="checkbox"]').attr('ng-click', 'selectItem($event)');
                                $item.find('.lp-item-text').append('<a class="lp-add-item" href="" ng-click="addItem($event, ' + parseInt($chk.val()) + ')" href="">add</a>');
                                $scope._addItemToResults(this);
                            }).length > 0;
                        $scope.$resultItems = $scope.$resultsContainer.children('.lp-result-item');
                        $scope.$resultItems.eq(0).addClass('active');

                    });
                }, 300);
            },
            onKeyEvent: function (event) {
                if ((event.type !== 'keydown' || !this.isShowingResults) && event.keyCode !== 13) {
                    return;
                }
                var itemPosition = this.itemPosition;
                switch (event.keyCode) {
                    case 38: // up
                        this.itemPosition = this.itemPosition > 0 ? this.itemPosition - 1 : this.itemPosition;
                        break;
                    case 40: //down
                        this.itemPosition = this.itemPosition < this.$resultItems.length - 1 ? this.itemPosition + 1 : this.itemPosition;
                        break;
                    case 13:
                        event.preventDefault();
                        if (this.$resultItems) {
                            this.addItem(event, this.$resultItems.eq(this.itemPosition));
                        }
                        break;
                    case 27:
                        this.isShowingResults = false;
                        this.$input.focus();
                }
                if (itemPosition !== this.itemPosition) {
                    this._setItemSelected();
                    event.preventDefault();
                }
            },
            _setItemSelected: function (newPosition) {
                switch (typeof newPosition) {
                    case 'number':
                        this.itemPosition = newPosition;
                        break;
                    case 'object':
                        this.itemPosition = this.$resultItems.index(newPosition);

                }
                _.forEach($scope.$resultItems, function (item, i) {
                    var $item = $(item);
                    $item.removeClass('active');

                    if (i === this.itemPosition) {
                        $item.addClass('active').find('input[type="checkbox"]').focus();
                        LP.toElement($item, {
                            container: this.$resultsContainer,
                            delay: 0,
                            offset: 0,
                            duration: 0,
                            invisible: true
                        });
                    }
                }, this);
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
                this._setItemSelected($(event.target).closest('.lp-result-item'));
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
                event && event.preventDefault();
                if (typeof item === 'object') {
                    item = $(item).data('id');
                }
                var position = $.inArray(item, this.selectedItems);
                if (position !== -1) {
                    this.selectedItems.splice(position, 1);
                }
                var ctrl = angular.element($('#learn-press-quiz-questions')[0]).scope();
                ctrl.addExistsQuestions([item])
            },
            addBulkItems: function () {
                var ctrl = angular.element($('#learn-press-quiz-questions')[0]).scope();
                ctrl.addExistsQuestions(this.selectedItems);
                this.selectedItems = [];
            },
            getElement: function (selector) {
                var $el = null;
                try {
                    $el = selector ? $element.find(selector) : $element;
                } catch (e) {
                    console.log(e)
                }
                return $el;
            },
            hideSearchResults: function () {

            },
            needShowResults: function () {
                this.$overlay.toggle(this.isShowingResults);
                return this.isShowingResults;
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