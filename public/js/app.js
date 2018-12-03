var myApp = angular.module('myApp', ['angularFileUpload','ngLoadingSpinner', 'angularUtils.directives.dirPagination', 
    'ngAnimate', 'ngSanitize', 'ui.bootstrap','media']);

myApp.directive('dynamicUrl', function () {
    return {
        restrict: 'A',
        link: function postLink(scope, element, attr) {
            element.attr('src', attr.dynamicUrlSrc);
        }
    };
});

window.myApp.filter('unsafe', function ($sce) {
    return $sce.trustAsHtml;
});

//
//myApp.filter("trustUrl", function($sce) {
//    return function(Url) {
//        return $sce.trustAsResourceUrl(Url);
//    };
//});
