(function (angular, $, _) {
  var module = angular.module('allocated-payments');

  module.config(function ($routeProvider) {
    $routeProvider.when('/report', {
      template: function () {
        return `
          <allocated-payments-report></allocated-payments-report>
        `;
      }
    });
  });
})(angular, CRM.$, CRM._);
