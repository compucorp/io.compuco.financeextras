(function (angular, _, $) {
  var module = angular.module('allocated-payments');

  module.directive('allocatedPaymentsReport', function () {
    return {
      restrict: 'E',
      controller: 'allocatedPaymentsReportController',
      templateUrl: '~/allocated-payments/directives/allocated-payments-report.directive.html',
    };
  });

  module.controller('allocatedPaymentsReportController', allocatedPaymentsReportController);

  /**
   * @param {object} $scope the controller scope
   * @param {object} $location the location service
   * @param {object} $window window object of the browser
   */
  function allocatedPaymentsReportController ($scope, $location, $window) {
    (function init () {
    }());
  }
})(angular, CRM._, CRM.$);
