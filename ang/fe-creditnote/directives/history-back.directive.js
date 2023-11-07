(function (angular, $window) {
  var module = angular.module('fe-creditnote');

  /* eslint-disable no-unused-vars*/
  module.directive('historyBack', function () {
    return {
      restrict: 'A',
      link: function (scope, elem, attrs) {
        elem.bind('click', function () {
          $window.history.back();
          const currPage = window.location.href;
          setTimeout(function () {
            if ($window.location.href === currPage) {
              $window.close();
            }
          }, 500);
        });
      }
    };
  });
})(angular, window);
