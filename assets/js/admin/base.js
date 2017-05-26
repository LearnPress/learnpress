;(function ($) {
    window.courseEditor = angular.module('courseEditor', [], function ($httpProvider) {
        /*$httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
         var app = angular.module('virmApp', ['ui.sortable','ngSanitize'], function ($httpProvider) {

         // Use x-www-form-urlencoded Content-Type
         $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
         var param = function (obj) {
         var query = '',  name, value, fullSubName, subName, subValue, innerObj, i;
         for ( name in obj ) {
         value = obj[ name];
         if (value instanceof Array) {
         for (i = 0; i < value.length; ++i) {
         subValue = value[i];
         fullSubName =  name + '[' + i + ']';
         innerObj = {};
         innerObj[fullSubName] = subValue;
         query += param(innerObj) + '&';
         }
         }
         else if (value instanceof Object) {
         for (subName in value) {
         subValue = value[subName];
         fullSubName =  name + '[' + subName + ']';
         innerObj = {};
         innerObj[fullSubName] = subValue;
         query += param(innerObj) + '&';
         }
         } else if (value !== undefined && value !== null) {
         query += encodeURIComponent( name) + '=' + encodeURIComponent(value) + '&';
         }
         }
         return query.length ? query.substr(0, query.length - 1) : query;
         };

         // Override $http service's default transformRequest
         $httpProvider.defaults.transformRequest = [function (data) {
         return angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
         }];
         });*/
    });
    courseEditor.controller('courseEditor', ['$scope', '$compile', '$element', '$timeout', window['learn-press.base.controller']]);
    courseEditor.controller('modalSearchQuestion', ['$scope', '$compile', '$element', '$timeout', '$http', window['learn-press.controllers.question.search']]);
    $(document).on('click', '.learn-press-tooltip', function () {
        $('.tipsy').remove();
    });
})(jQuery);