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
/******/ 	return __webpack_require__(__webpack_require__.s = "./assets/src/js/frontend/utils/index.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./assets/src/js/frontend/utils/index.js":
/*!***********************************************!*\
  !*** ./assets/src/js/frontend/utils/index.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

throw new Error("Module build failed (from ./node_modules/babel-loader/lib/index.js):\nError: Plugin/Preset files are not allowed to export objects, only functions. In /Users/tu/Documents/foobla/repo/LearnPress/node_modules/babel-preset-es2015/lib/index.js\n    at createDescriptor (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-descriptors.js:178:11)\n    at items.map (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-descriptors.js:109:50)\n    at Array.map (<anonymous>)\n    at createDescriptors (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-descriptors.js:109:29)\n    at createPresetDescriptors (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-descriptors.js:101:10)\n    at passPerPreset (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-descriptors.js:58:96)\n    at cachedFunction (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/caching.js:33:19)\n    at presets.presets (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-descriptors.js:29:84)\n    at mergeChainOpts (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-chain.js:320:26)\n    at /Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-chain.js:283:7\n    at buildRootChain (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/config-chain.js:68:29)\n    at loadPrivatePartialConfig (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/partial.js:85:55)\n    at Object.loadPartialConfig (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/@babel/core/lib/config/partial.js:110:18)\n    at Object.<anonymous> (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/babel-loader/lib/index.js:144:26)\n    at Generator.next (<anonymous>)\n    at asyncGeneratorStep (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/babel-loader/lib/index.js:3:103)\n    at _next (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/babel-loader/lib/index.js:5:194)\n    at /Users/tu/Documents/foobla/repo/LearnPress/node_modules/babel-loader/lib/index.js:5:364\n    at new Promise (<anonymous>)\n    at Object.<anonymous> (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/babel-loader/lib/index.js:5:97)\n    at Object.loader (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/babel-loader/lib/index.js:60:18)\n    at Object.<anonymous> (/Users/tu/Documents/foobla/repo/LearnPress/node_modules/babel-loader/lib/index.js:55:12)");

/***/ })

/******/ });
//# sourceMappingURL=utils.js.map