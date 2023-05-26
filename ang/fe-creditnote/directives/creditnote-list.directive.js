(function (angular) {
  var module = angular.module('fe-creditnote');

  module.directive('creditnoteList', function () {
    return {
      restrict: 'E',
      controller: 'creditNoteListController',
      templateUrl: '~/fe-creditnote/directives/creditnote-list.directive.html',
      scope: {
        view: '@',
        contactId: '@'
      }
    };
  });

  module.controller('creditNoteListController', creditNoteListController);

  /**
   * @param {object} $scope the controller scope
   * @param {object} $location the location service
   * @param {object} $window window object of the browser
   */
  function creditNoteListController ($scope, $location, $window) {
    $location.search().cid = $scope.contactId;
    $window.history.replaceState('', '', `#?cid=${$scope.contactId}`)

  }
})(angular);
