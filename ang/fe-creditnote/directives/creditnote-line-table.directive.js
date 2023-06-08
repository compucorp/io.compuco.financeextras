(function (angular) {
  var module = angular.module('fe-creditnote');

  module.directive('creditnoteLineTable', function () {
    return {
      restrict: 'E',
      controller: 'creditnoteLineTableController',
      templateUrl: '~/fe-creditnote/directives/creditnote-line-table.directive.html',
      scope: {
        creditNote: '@',
      }
    };
  });

  module.controller('creditnoteLineTableController', creditnoteLineTableController);

  /**
   * @param {object} $scope the controller scope
   * @param {object} MoneyFormat service
   */
  function creditnoteLineTableController($scope, MoneyFormat) {
    $scope.ts = CRM.ts();
    $scope.crmUrl = CRM.url;
  
    $scope.$watch("creditNote", function(newValue, oldValue){
      if(newValue != oldValue){
        const creditNote = JSON.parse($scope.creditNote)
        $scope.currency = creditNote.currency
        $scope.items = creditNote.items;
      }
  });

    $scope.formatMoney = MoneyFormat.formatMoney;

  }

})(angular);
