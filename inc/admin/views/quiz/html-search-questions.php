<div ng-controller="modalSearch" class="modal-search" ng-class="{'has-items':hasItems}">
    <div ng-controller="modalSearchQuestion"
         ng-class="{'has-items':hasItems}"
         class="modal-search-questions"
         data-type="questions"
         data-context="lp_quiz"
         data-limit="2"
         data-paged="1">
        <input type="text"
               ng-keypress="onSearchInputKeyEvent($event)"
               ng-keyup="onSearchInputKeyEvent($event)"
               ng-keydown="onSearchInputKeyEvent($event)"
               ng-blur="onSearchInputKeyEvent($event)"
               ng-model="searchTerm"
               class="search-input">
        <div class="search-results-content ng-hide" ng-show="hasItems">
            <ul class="search-results">
                <li ng-repeat="result in getItems() track by $index" class="result-item">
                    <label title="{{result.text}}">
                        <input type="checkbox"
                               value="{{result.id}}"
                               name="item-{{result.id}}"
                               ng-click="addItemToList($event)"
                               ng-checked="maybeCheckItem(result.id)"
                               data-id="{{result.id}}"
                               data-text="{{result.text}}"/>
                        <span>{{result.text}}</span>
                    </label>
                </li>
                <li class="no-item ng-hide" ng-show="showNotFound">
					<?php esc_html_e( 'No item', 'learnpress' ); ?>
                </li>
            </ul>
            {{this.checkedItems}}
            <div class="search-results-footer">
                <div class="checked-items">
                    {{htmlCountSelectedItems('<?php esc_attr_e( '%d/%d selected', 'learnpress' ); ?>')}}
                    <button type="button"
                            class="clear-items button"
                            ng-click="clearCheckedItems($event)"
                            ng-class="{disabled: !checkedItems.length}"><?php _e( 'Clear', 'learnpress' ); ?></button>
                    <button type="button"
                            class="add-items button"
                            ng-click="selectItems($event)"
                            ng-class="{disabled: !checkedItems.length}"><?php _e( 'Add', 'learnpress' ); ?></button>
                </div>
                <div class="search-navigator" ng-show="showNavigator"></div>
            </div>
        </div>
    </div>
</div>