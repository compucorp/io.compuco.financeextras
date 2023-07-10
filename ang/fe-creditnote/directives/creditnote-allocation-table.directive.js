(function (angular) {
  var module = angular.module('fe-creditnote');

  module.directive('creditnoteAllocationTable', function () {
    return {
      restrict: 'E',
      controller: 'creditnoteAllocationTableController',
      templateUrl: '~/fe-creditnote/directives/creditnote-allocation-table.directive.html',
      scope: {
        creditNoteId: '@',
        context: '@'
      }
    };
  });

  module.controller('creditnoteAllocationTableController', creditnoteAllocationTableController);

  /**
   * @param {object} $scope the controller scope
   * @param {object} crmApi4 crm api V4 service
   * @param {object} MoneyFormat service
   */
  function creditnoteAllocationTableController($scope, crmApi4, MoneyFormat) {
    $scope.ts = CRM.ts();
    $scope.crmUrl = CRM.url;
    $scope.currency = 'GBP'
    $scope.allocations = [];
    $scope.total_credit = 0;
    $scope.allocated_credit = 0;
    $scope.remaining_credit = 0;
    $scope.isView = $scope.context == 'view'
    $scope.formatDate = CRM.utils.formatDate;
    $scope.formatMoney = MoneyFormat.formatMoney;
    $scope.isUpdate = $scope.context == 'update';
    $scope.hasAllocatePermission = CRM['fe-creditnote'].canEditContribution;

    crmApi4('CreditNote', 'get', {
      where: [["id", "=", $scope.creditNoteId]],
      limit: 25,
      chain: {"allocations":["CreditNoteAllocation", "get", {"where":[["credit_note_id", "=", "$id"]], "select":["*", "type_id:label", "contribution_id.invoice_number"]}]}
    }).then(function(result) {
      const creditnotes = result[0] ?? null;

      $scope.currency = creditnotes.currency
      $scope.allocations = creditnotes.allocations ?? []
      $scope.total_credit = creditnotes.total_credit
      $scope.remaining_credit = creditnotes.remaining_credit
      $scope.allocated_credit = creditnotes.total_credit - creditnotes.remaining_credit
    });

    $scope.deleteAllocation = () => {
      //not yet implemented.
      CRM.alert('', 'No implementation', '', 'success');
    }

  }

})(angular);
