/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/admin/editor/course.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/admin/editor/actions/course-section.js":
/*!**************************************************************!*\
  !*** ./assets/src/js/admin/editor/actions/course-section.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var $ = window.jQuery;
var CourseCurriculum = {
  toggleAllSections: function toggleAllSections(context) {
    var hidden = context.getters['isHiddenAllSections'];

    if (hidden) {
      context.commit('OPEN_ALL_SECTIONS');
    } else {
      context.commit('CLOSE_ALL_SECTIONS');
    }

    LP.Request({
      type: 'hidden-sections',
      hidden: context.getters['hiddenSections']
    });
  },
  updateSectionsOrder: function updateSectionsOrder(context, order) {
    LP.Request({
      type: 'sort-sections',
      order: JSON.stringify(order)
    }).then(function (response) {
      var result = response.body;
      var order_sections = result.data;
      context.commit('SORT_SECTION', order_sections);
    }, function (error) {
      console.error(error);
    });
  },
  toggleSection: function toggleSection(context, section) {
    if (section.open) {
      context.commit('CLOSE_SECTION', section);
    } else {
      context.commit('OPEN_SECTION', section);
    }

    LP.Request({
      type: 'hidden-sections',
      hidden: context.getters['hiddenSections']
    });
  },
  updateSection: function updateSection(context, section) {
    context.commit('UPDATE_SECTION_REQUEST', section.id);
    LP.Request({
      type: 'update-section',
      section: JSON.stringify(section)
    }).then(function () {
      context.commit('UPDATE_SECTION_SUCCESS', section.id);
    })["catch"](function () {
      context.commit('UPDATE_SECTION_FAILURE', section.id);
    });
  },
  removeSection: function removeSection(context, payload) {
    context.commit('REMOVE_SECTION', payload.index);
    LP.Request({
      type: 'remove-section',
      section_id: payload.section.id
    }).then(function (response) {
      var result = response.body;
    }, function (error) {
      console.error(error);
    });
  },
  newSection: function newSection(context, name) {
    var newSection = {
      type: 'new-section',
      section_name: name,
      temp_id: LP.uniqueId()
    };
    context.commit('ADD_NEW_SECTION', {
      id: newSection.temp_id,
      items: [],
      open: false,
      title: newSection.section_name
    });
    LP.Request(newSection).then(function (response) {
      var result = response.body;

      if (result.success) {
        var section = $.extend({}, result.data, {
          open: true
        }); // update course section

        context.commit('ADD_NEW_SECTION', section);
      }
    }, function (error) {
      console.error(error);
    });
  },
  updateSectionItem: function updateSectionItem(context, payload) {
    context.commit('UPDATE_SECTION_ITEM_REQUEST', payload.item.id);
    LP.Request({
      type: 'update-section-item',
      section_id: payload.section_id,
      item: JSON.stringify(payload.item)
    }).then(function (response) {
      context.commit('UPDATE_SECTION_ITEM_SUCCESS', payload.item.id);
      var result = response.body;

      if (result.success) {
        var item = result.data;
        context.commit('UPDATE_SECTION_ITEM', {
          section_id: payload.section_id,
          item: item
        });
      }
    }, function (error) {
      context.commit('UPDATE_SECTION_ITEM_FAILURE', payload.item.id);
      console.error(error);
    });
  },
  removeSectionItem: function removeSectionItem(context, payload) {
    var id = payload.item.id;
    context.commit('REMOVE_SECTION_ITEM', payload);
    payload.item.temp_id = 0;
    LP.Request({
      type: 'remove-section-item',
      section_id: payload.section_id,
      item_id: id
    }).then(function () {
      context.commit('REMOVE_SECTION_ITEM', payload);
    });
  },
  deleteSectionItem: function deleteSectionItem(context, payload) {
    var id = payload.item.id;
    context.commit('REMOVE_SECTION_ITEM', payload);
    payload.item.temp_id = 0;
    LP.Request({
      type: 'delete-section-item',
      section_id: payload.section_id,
      item_id: id
    }).then(function () {
      context.commit('REMOVE_SECTION_ITEM', payload);
    });
  },
  newSectionItem: function newSectionItem(context, payload) {
    context.commit('APPEND_EMPTY_ITEM_TO_SECTION', payload); //context.commit('UPDATE_SECTION_ITEMS', {section_id: payload.section_id, items: result.data});

    LP.Request({
      type: 'new-section-item',
      section_id: payload.section_id,
      item: JSON.stringify(payload.item)
    }).then(function (response) {
      var result = response.body;

      if (result.success) {
        // context.commit('UPDATE_SECTION_ITEMS', {section_id: payload.section_id, items: result.data});
        var items = {};
        $.each(result.data, function (i, a) {
          items[a.old_id ? a.old_id : a.id] = a;
        });
        context.commit('UPDATE_ITEM_SECTION_BY_ID', {
          section_id: payload.section_id,
          items: items
        });
      }
    }, function (error) {
      console.error(error);
    });
  },
  updateSectionItems: function updateSectionItems(_ref, payload) {
    var state = _ref.state;
    LP.Request({
      type: 'update-section-items',
      section_id: payload.section_id,
      items: JSON.stringify(payload.items),
      last_section: state.sections[state.sections.length - 1] === payload.section_id
    }).then(function (response) {
      var result = response.body;

      if (result.success) {// console.log(result);
      }
    }, function (error) {
      console.error(error);
    });
  }
};
/* harmony default export */ __webpack_exports__["default"] = (CourseCurriculum);

/***/ }),

/***/ "./assets/src/js/admin/editor/actions/course.js":
/*!******************************************************!*\
  !*** ./assets/src/js/admin/editor/actions/course.js ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Course = {
  heartbeat: function heartbeat(context) {
    LP.Request({
      type: 'heartbeat'
    }).then(function (response) {
      var result = response.body;
      context.commit('UPDATE_HEART_BEAT', !!result.success);
    }, function (error) {
      context.commit('UPDATE_HEART_BEAT', false);
    });
  },
  draftCourse: function draftCourse(context, payload) {
    var auto_draft = context.getters['autoDraft'];

    if (auto_draft) {
      LP.Request({
        type: 'draft-course',
        course: JSON.stringify(payload)
      }).then(function (response) {
        var result = response.body;

        if (!result.success) {
          return;
        }

        context.commit('UPDATE_AUTO_DRAFT_STATUS', false);
      });
    }
  },
  newRequest: function newRequest(context) {
    context.commit('INCREASE_NUMBER_REQUEST');
    context.commit('UPDATE_STATUS', 'loading');

    window.onbeforeunload = function () {
      return '';
    };
  },
  requestCompleted: function requestCompleted(context, status) {
    context.commit('DECREASE_NUMBER_REQUEST');

    if (context.getters.currentRequest === 0) {
      context.commit('UPDATE_STATUS', status);
      window.onbeforeunload = null;
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Course);

/***/ }),

/***/ "./assets/src/js/admin/editor/actions/modal-course-items.js":
/*!******************************************************************!*\
  !*** ./assets/src/js/admin/editor/actions/modal-course-items.js ***!
  \******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var ModalCourseItems = {
  toggle: function toggle(context) {
    context.commit('TOGGLE');
  },
  open: function open(context, sectionId) {
    context.commit('SET_SECTION', sectionId);
    context.commit('RESET');
    context.commit('TOGGLE');
  },
  searchItems: function searchItems(context, payload) {
    context.commit('SEARCH_ITEMS_REQUEST');
    LP.Request({
      type: 'search-items',
      query: payload.query,
      item_type: payload.type,
      page: payload.page,
      exclude: JSON.stringify([])
    }).then(function (response) {
      var result = response.body;

      if (!result.success) {
        return;
      }

      var data = result.data;
      context.commit('SET_LIST_ITEMS', data.items);
      context.commit('UPDATE_PAGINATION', data.pagination);
      context.commit('SEARCH_ITEMS_SUCCESS');
    }, function (error) {
      context.commit('SEARCH_ITEMS_FAILURE');
      console.error(error);
    });
  },
  addItem: function addItem(context, item) {
    context.commit('ADD_ITEM', item);
  },
  removeItem: function removeItem(context, index) {
    context.commit('REMOVE_ADDED_ITEM', index);
  },
  addItemsToSection: function addItemsToSection(context) {
    var items = context.getters.addedItems;

    if (items.length > 0) {
      LP.Request({
        type: 'add-items-to-section',
        section_id: context.getters.section,
        items: JSON.stringify(items)
      }).then(function (response) {
        var result = response.body;

        if (result.success) {
          context.commit('TOGGLE');
          var items = result.data;
          context.commit('ss/UPDATE_SECTION_ITEMS', {
            section_id: context.getters.section,
            items: items
          }, {
            root: true
          });
        }
      }, function (error) {
        console.error(error);
      });
    }
  }
};
/* harmony default export */ __webpack_exports__["default"] = (ModalCourseItems);

/***/ }),

/***/ "./assets/src/js/admin/editor/course.js":
/*!**********************************************!*\
  !*** ./assets/src/js/admin/editor/course.js ***!
  \**********************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _http__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./http */ "./assets/src/js/admin/editor/http.js");
/* harmony import */ var _store_course__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./store/course */ "./assets/src/js/admin/editor/store/course.js");


window.$Vue = window.$Vue || Vue;
window.$Vuex = window.$Vuex || Vuex;
var $ = window.jQuery;
/**
 * Init app.
 *
 * @since 3.0.0
 */

$(document).ready(function () {
  window.LP_Curriculum_Store = new $Vuex.Store(Object(_store_course__WEBPACK_IMPORTED_MODULE_1__["default"])(lpAdminCourseEditorSettings));
  Object(_http__WEBPACK_IMPORTED_MODULE_0__["default"])({
    ns: 'LPCurriculumRequest',
    store: LP_Curriculum_Store
  });
  setTimeout(function () {
    window.LP_Course_Editor = new $Vue({
      el: '#admin-editor-lp_course',
      template: '<lp-course-editor></lp-course-editor>'
    });
  }, 100);
});

/***/ }),

/***/ "./assets/src/js/admin/editor/getters/course-section.js":
/*!**************************************************************!*\
  !*** ./assets/src/js/admin/editor/getters/course-section.js ***!
  \**************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var CourseCurriculum = {
  sections: function sections(state) {
    return state.sections || [];
  },
  urlEdit: function urlEdit(state) {
    return state.urlEdit;
  },
  hiddenSections: function hiddenSections(state) {
    return state.sections.filter(function (section) {
      return !section.open;
    }).map(function (section) {
      return parseInt(section.id);
    });
  },
  isHiddenAllSections: function isHiddenAllSections(state, getters) {
    var sections = getters['sections'];
    var hiddenSections = getters['hiddenSections'];
    return hiddenSections.length === sections.length;
  },
  statusUpdateSection: function statusUpdateSection(state) {
    return state.statusUpdateSection;
  },
  statusUpdateSectionItem: function statusUpdateSectionItem(state) {
    return state.statusUpdateSectionItem;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (CourseCurriculum);

/***/ }),

/***/ "./assets/src/js/admin/editor/getters/course.js":
/*!******************************************************!*\
  !*** ./assets/src/js/admin/editor/getters/course.js ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Course = {
  heartbeat: function heartbeat(state) {
    return state.heartbeat;
  },
  action: function action(state) {
    return state.action;
  },
  id: function id(state) {
    return state.course_id;
  },
  autoDraft: function autoDraft(state) {
    return state.auto_draft;
  },
  disable_curriculum: function disable_curriculum(state) {
    return state.disable_curriculum;
  },
  status: function status(state) {
    return state.status || 'error';
  },
  currentRequest: function currentRequest(state) {
    return state.countCurrentRequest || 0;
  },
  urlAjax: function urlAjax(state) {
    return state.ajax;
  },
  nonce: function nonce(state) {
    return state.nonce;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Course);

/***/ }),

/***/ "./assets/src/js/admin/editor/getters/modal-course-items.js":
/*!******************************************************************!*\
  !*** ./assets/src/js/admin/editor/getters/modal-course-items.js ***!
  \******************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Getters = {
  status: function status(state) {
    return state.status;
  },
  pagination: function pagination(state) {
    return state.pagination;
  },
  items: function items(state, _getters) {
    return state.items.map(function (item) {
      var find = _getters.addedItems.find(function (_item) {
        return item.id === _item.id;
      });

      item.added = !!find;
      return item;
    });
  },
  addedItems: function addedItems(state) {
    return state.addedItems;
  },
  isOpen: function isOpen(state) {
    return state.open;
  },
  types: function types(state) {
    return state.types;
  },
  section: function section(state) {
    return state.sectionId;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Getters);

/***/ }),

/***/ "./assets/src/js/admin/editor/http.js":
/*!********************************************!*\
  !*** ./assets/src/js/admin/editor/http.js ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return HTTP; });
function HTTP(options) {
  var $ = window.jQuery;
  var $VueHTTP = Vue.http;
  options = $.extend({
    ns: 'LPRequest',
    store: false
  }, options || {});
  var $publishingAction = null;

  LP.Request = function (payload) {
    $publishingAction = $('#publishing-action');
    payload['id'] = options.store.getters.id;
    payload['nonce'] = options.store.getters.nonce;
    payload['lp-ajax'] = options.store.getters.action;
    payload['code'] = options.store.getters.code;
    $publishingAction.find('#publish').addClass('disabled');
    $publishingAction.find('.spinner').addClass('is-active');
    $publishingAction.addClass('code-' + payload['code']);
    return $VueHTTP.post(options.store.getters.urlAjax, payload, {
      emulateJSON: true,
      params: {
        namespace: options.ns,
        code: payload['code']
      }
    });
  };

  $VueHTTP.interceptors.push(function (request, next) {
    if (request.params['namespace'] !== options.ns) {
      next();
      return;
    }

    options.store.dispatch('newRequest');
    next(function (response) {
      if (!jQuery.isPlainObject(response.body)) {
        response.body = LP.parseJSON(response.body);
      }

      var body = response.body;
      var result = body.success || false;

      if (result) {
        options.store.dispatch('requestCompleted', 'successful');
      } else {
        options.store.dispatch('requestCompleted', 'failed');
      }

      $publishingAction.removeClass('code-' + request.params.code);

      if (!$publishingAction.attr('class')) {
        $publishingAction.find('#publish').removeClass('disabled');
        $publishingAction.find('.spinner').removeClass('is-active');
      }
    });
  });
}

/***/ }),

/***/ "./assets/src/js/admin/editor/mutations/course-section.js":
/*!****************************************************************!*\
  !*** ./assets/src/js/admin/editor/mutations/course-section.js ***!
  \****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var CourseCurriculum = {
  'SORT_SECTION': function SORT_SECTION(state, orders) {
    state.sections = state.sections.map(function (section) {
      section.order = orders[section.id];
      return section;
    });
  },
  'SET_SECTIONS': function SET_SECTIONS(state, sections) {
    state.sections = sections;
  },
  'ADD_NEW_SECTION': function ADD_NEW_SECTION(state, newSection) {
    if (newSection.open === undefined) {
      newSection.open = true;
    }

    var pos;

    if (newSection.temp_id) {
      state.sections.map(function (section, i) {
        if (newSection.temp_id == section.id) {
          pos = i;
          return false;
        }
      });
    }

    if (pos !== undefined) {
      $Vue.set(state.sections, pos, newSection);
    } else {
      state.sections.push(newSection);
    }
  },
  'ADD_EMPTY_SECTION': function ADD_EMPTY_SECTION(state, section) {
    section.open = true;
    state.sections.push(section);
  },
  'REMOVE_SECTION': function REMOVE_SECTION(state, index) {
    state.sections.splice(index, 1);
  },
  'REMOVE_SECTION_ITEM': function REMOVE_SECTION_ITEM(state, payload) {
    var section = state.sections.find(function (section) {
      return section.id === payload.section_id;
    });
    var items = section.items || [],
        item = payload.item,
        index = -1;
    items.forEach(function (it, i) {
      if (it.id === item.id) {
        index = i;
      }
    });

    if (index !== -1) {
      if (item.temp_id) {
        items[index].id = item.temp_id;
      } else {
        items.splice(index, 1);
      }
    }
  },
  'UPDATE_SECTION_ITEMS': function UPDATE_SECTION_ITEMS(state, payload) {
    var section = state.sections.find(function (section) {
      return parseInt(section.id) === parseInt(payload.section_id);
    });

    if (!section) {
      return;
    }

    section.items = payload.items;
  },
  'UPDATE_SECTION_ITEM': function UPDATE_SECTION_ITEM(state, payload) {},
  'CLOSE_SECTION': function CLOSE_SECTION(state, section) {
    state.sections.forEach(function (_section, index) {
      if (section.id === _section.id) {
        state.sections[index].open = false;
      }
    });
  },
  'OPEN_SECTION': function OPEN_SECTION(state, section) {
    state.sections.forEach(function (_section, index) {
      if (section.id === _section.id) {
        state.sections[index].open = true;
      }
    });
  },
  'OPEN_ALL_SECTIONS': function OPEN_ALL_SECTIONS(state) {
    state.sections = state.sections.map(function (_section) {
      _section.open = true;
      return _section;
    });
  },
  'CLOSE_ALL_SECTIONS': function CLOSE_ALL_SECTIONS(state) {
    state.sections = state.sections.map(function (_section) {
      _section.open = false;
      return _section;
    });
  },
  'UPDATE_SECTION_REQUEST': function UPDATE_SECTION_REQUEST(state, sectionId) {
    $Vue.set(state.statusUpdateSection, sectionId, 'updating');
  },
  'UPDATE_SECTION_SUCCESS': function UPDATE_SECTION_SUCCESS(state, sectionId) {
    $Vue.set(state.statusUpdateSection, sectionId, 'successful');
  },
  'UPDATE_SECTION_FAILURE': function UPDATE_SECTION_FAILURE(state, sectionId) {
    $Vue.set(state.statusUpdateSection, sectionId, 'failed');
  },
  'UPDATE_SECTION_ITEM_REQUEST': function UPDATE_SECTION_ITEM_REQUEST(state, itemId) {
    $Vue.set(state.statusUpdateSectionItem, itemId, 'updating');
  },
  'UPDATE_SECTION_ITEM_SUCCESS': function UPDATE_SECTION_ITEM_SUCCESS(state, itemId) {
    $Vue.set(state.statusUpdateSectionItem, itemId, 'successful');
  },
  'UPDATE_SECTION_ITEM_FAILURE': function UPDATE_SECTION_ITEM_FAILURE(state, itemId) {
    $Vue.set(state.statusUpdateSectionItem, itemId, 'failed');
  },
  'APPEND_EMPTY_ITEM_TO_SECTION': function APPEND_EMPTY_ITEM_TO_SECTION(state, data) {
    var section = state.sections.find(function (section) {
      return parseInt(section.id) === parseInt(data.section_id);
    });

    if (!section) {
      return;
    }

    section.items.push({
      id: data.item.id,
      title: data.item.title,
      type: 'empty-item'
    });
  },
  'UPDATE_ITEM_SECTION_BY_ID': function UPDATE_ITEM_SECTION_BY_ID(state, data) {
    var section = state.sections.find(function (section) {
      return parseInt(section.id) === parseInt(data.section_id);
    });

    if (!section) {
      return;
    }

    for (var i = 0; i < section.items.length; i++) {
      try {
        if (!section.items[i]) {
          continue;
        }

        var item_id = section.items[i].id;

        if (item_id) {
          if (data.items[item_id]) {
            $Vue.set(section.items, i, data.items[item_id]);
          }
        }
      } catch (ex) {
        console.log(ex);
      }
    } //section.items.push({id: data.item.id, title: data.item.title, type: 'empty-item'});

  }
};
/* harmony default export */ __webpack_exports__["default"] = (CourseCurriculum);

/***/ }),

/***/ "./assets/src/js/admin/editor/mutations/course.js":
/*!********************************************************!*\
  !*** ./assets/src/js/admin/editor/mutations/course.js ***!
  \********************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Course = {
  'UPDATE_HEART_BEAT': function UPDATE_HEART_BEAT(state, status) {
    state.heartbeat = !!status;
  },
  'UPDATE_AUTO_DRAFT_STATUS': function UPDATE_AUTO_DRAFT_STATUS(state, status) {
    // check auto draft status
    state.auto_draft = status;
  },
  'UPDATE_STATUS': function UPDATE_STATUS(state, status) {
    state.status = status;
  },
  'INCREASE_NUMBER_REQUEST': function INCREASE_NUMBER_REQUEST(state) {
    state.countCurrentRequest++;
  },
  'DECREASE_NUMBER_REQUEST': function DECREASE_NUMBER_REQUEST(state) {
    state.countCurrentRequest--;
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Course);

/***/ }),

/***/ "./assets/src/js/admin/editor/mutations/modal-course-items.js":
/*!********************************************************************!*\
  !*** ./assets/src/js/admin/editor/mutations/modal-course-items.js ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var Mutations = {
  'TOGGLE': function TOGGLE(state) {
    state.open = !state.open;
  },
  'SET_SECTION': function SET_SECTION(state, sectionId) {
    state.sectionId = sectionId;
  },
  'SET_LIST_ITEMS': function SET_LIST_ITEMS(state, items) {
    state.items = items;
  },
  'ADD_ITEM': function ADD_ITEM(state, item) {
    state.addedItems.push(item);
  },
  'REMOVE_ADDED_ITEM': function REMOVE_ADDED_ITEM(state, item) {
    state.addedItems.forEach(function (_item, index) {
      if (_item.id === item.id) {
        state.addedItems.splice(index, 1);
      }
    });
  },
  'RESET': function RESET(state) {
    state.addedItems = [];
    state.items = [];
  },
  'UPDATE_PAGINATION': function UPDATE_PAGINATION(state, pagination) {
    state.pagination = pagination;
  },
  'SEARCH_ITEMS_REQUEST': function SEARCH_ITEMS_REQUEST(state) {
    state.status = 'loading';
  },
  'SEARCH_ITEMS_SUCCESS': function SEARCH_ITEMS_SUCCESS(state) {
    state.status = 'successful';
  },
  'SEARCH_ITEMS_FAILURE': function SEARCH_ITEMS_FAILURE(state) {
    state.status = 'failed';
  }
};
/* harmony default export */ __webpack_exports__["default"] = (Mutations);

/***/ }),

/***/ "./assets/src/js/admin/editor/store/course-section.js":
/*!************************************************************!*\
  !*** ./assets/src/js/admin/editor/store/course-section.js ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _actions_course_section__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../actions/course-section */ "./assets/src/js/admin/editor/actions/course-section.js");
/* harmony import */ var _mutations_course_section__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mutations/course-section */ "./assets/src/js/admin/editor/mutations/course-section.js");
/* harmony import */ var _getters_course_section__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../getters/course-section */ "./assets/src/js/admin/editor/getters/course-section.js");



var $ = jQuery;
/* harmony default export */ __webpack_exports__["default"] = (function (data) {
  var state = $.extend({}, data.sections);
  state.statusUpdateSection = {};
  state.statusUpdateSectionItem = {};
  state.sections = state.sections.map(function (section) {
    var hiddenSections = state.hidden_sections;
    var find = hiddenSections.find(function (sectionId) {
      return parseInt(section.id) === parseInt(sectionId);
    });
    section.open = !find;
    return section;
  });
  return {
    namespaced: true,
    state: state,
    getters: _getters_course_section__WEBPACK_IMPORTED_MODULE_2__["default"],
    mutations: _mutations_course_section__WEBPACK_IMPORTED_MODULE_1__["default"],
    actions: _actions_course_section__WEBPACK_IMPORTED_MODULE_0__["default"]
  };
});

/***/ }),

/***/ "./assets/src/js/admin/editor/store/course.js":
/*!****************************************************!*\
  !*** ./assets/src/js/admin/editor/store/course.js ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _store_modal_course_items__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../store/modal-course-items */ "./assets/src/js/admin/editor/store/modal-course-items.js");
/* harmony import */ var _store_course_section__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../store/course-section */ "./assets/src/js/admin/editor/store/course-section.js");
/* harmony import */ var _store_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../store/i18n */ "./assets/src/js/admin/editor/store/i18n.js");
/* harmony import */ var _getters_course__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../getters/course */ "./assets/src/js/admin/editor/getters/course.js");
/* harmony import */ var _mutations_course__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../mutations/course */ "./assets/src/js/admin/editor/mutations/course.js");
/* harmony import */ var _actions_course__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../actions/course */ "./assets/src/js/admin/editor/actions/course.js");






var $ = window.jQuery;

var Course = function Course(data) {
  var state = $.extend({}, data.root);
  state.status = 'success';
  state.heartbeat = true;
  state.countCurrentRequest = 0;
  return {
    state: state,
    getters: _getters_course__WEBPACK_IMPORTED_MODULE_3__["default"],
    mutations: _mutations_course__WEBPACK_IMPORTED_MODULE_4__["default"],
    actions: _actions_course__WEBPACK_IMPORTED_MODULE_5__["default"],
    modules: {
      ci: Object(_store_modal_course_items__WEBPACK_IMPORTED_MODULE_0__["default"])(data),
      i18n: Object(_store_i18n__WEBPACK_IMPORTED_MODULE_2__["default"])(data.i18n),
      ss: Object(_store_course_section__WEBPACK_IMPORTED_MODULE_1__["default"])(data)
    }
  };
};

/* harmony default export */ __webpack_exports__["default"] = (Course);

/***/ }),

/***/ "./assets/src/js/admin/editor/store/i18n.js":
/*!**************************************************!*\
  !*** ./assets/src/js/admin/editor/store/i18n.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
var $ = window.jQuery;

var i18n = function i18n(i18n) {
  var state = $.extend({}, i18n);
  var getters = {
    all: function all(state) {
      return state;
    }
  };
  return {
    namespaced: true,
    state: state,
    getters: getters
  };
};

/* harmony default export */ __webpack_exports__["default"] = (i18n);

/***/ }),

/***/ "./assets/src/js/admin/editor/store/modal-course-items.js":
/*!****************************************************************!*\
  !*** ./assets/src/js/admin/editor/store/modal-course-items.js ***!
  \****************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _getters_modal_course_items__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../getters/modal-course-items */ "./assets/src/js/admin/editor/getters/modal-course-items.js");
/* harmony import */ var _mutations_modal_course_items__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mutations/modal-course-items */ "./assets/src/js/admin/editor/mutations/modal-course-items.js");
/* harmony import */ var _actions_modal_course_items__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../actions/modal-course-items */ "./assets/src/js/admin/editor/actions/modal-course-items.js");



var $ = jQuery;
/* harmony default export */ __webpack_exports__["default"] = (function (data) {
  var state = $.extend({}, data.chooseItems);
  state.sectionId = false;
  state.pagination = '';
  state.status = '';
  return {
    namespaced: true,
    state: state,
    getters: _getters_modal_course_items__WEBPACK_IMPORTED_MODULE_0__["default"],
    mutations: _mutations_modal_course_items__WEBPACK_IMPORTED_MODULE_1__["default"],
    actions: _actions_modal_course_items__WEBPACK_IMPORTED_MODULE_2__["default"]
  };
});

/***/ })

/******/ });