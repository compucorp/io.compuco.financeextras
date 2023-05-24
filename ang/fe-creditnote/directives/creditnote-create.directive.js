(function (angular, $, _) {
  var module = angular.module('fe-creditnote');

  module.directive('creditnoteCreate', function ($timeout) {
    return {
      restrict: 'E',
      controller: 'creditnoteCreateController',
      templateUrl: '~/fe-creditnote/directives/creditnote-create.directive.html',
      link: function() {
        $timeout(function() {
          if (CRM.$("div.ui-dialog").length) {
            const buttonPane = CRM.$("<div>").addClass("ui-dialog-buttonpane ui-widget-content ui-helper-clearfix");
            const buttonSet = CRM.$("<div>").addClass("ui-dialog-buttonset flex-between");
            buttonSet.append(CRM.$('.crm-submit-buttons > button'))
            buttonPane.append(buttonSet);

            CRM.$("div.ui-dialog").append(buttonPane);
          }
        }, 20);
      }
    };
  });

  module.controller('creditnoteCreateController', creditnoteCreateController);

  /**
   * @param {object} $scope the controller scope
   * @param {object} $location the location service
   * @param {object} $window window object of the browser
   * @param {object} CurrencyCodes CurrencyCodes service
   * @param {object} crmUiHelp crm ui help service
   * @param {object} crmApi4 crm api V4 service
   */
  function creditnoteCreateController ($scope, $location, $window, CurrencyCodes, crmUiHelp, crmApi4) {
    const defaultCurrency = 'GBP';
    $scope.isUpdate = false;
    $scope.formValid = true;

    $scope.ts = CRM.ts();
    $scope.saveCreditnotes = saveCreditnotes;
    $scope.currencyCodes = CurrencyCodes.getAll();
    $scope.hs = crmUiHelp({ file: 'CRM/Financeextras/CreditNoteCtrl' });
    $scope.currencySymbol = CurrencyCodes.getSymbol(defaultCurrency);

    (function init () {
      initializeCreditnotes();
      $scope.newCreditnotesItem = newCreditnotesItem;
      CRM.wysiwyg.create('#creditnotes-description');
      $scope.removeCreditnotesItem = removeCreditnotesItem;

      $scope.$on('totalChange', _.debounce(handleTotalChange, 250));
    }());

    /**
     * Initializess the creditnotes object
     */
    function initializeCreditnotes () {
      $scope.creditnotes = {
        currency: defaultCurrency,
        contact_id: null,
        date: $.datepicker.formatDate('yy-mm-dd', new Date()),
        items: [{
          item_description: null,
          financial_type_id: null,
          unit_price: null,
          quantity: null,
          tax_rate: 0,
          subtotal_amount: 0
        }],
        total: 0,
        grandTotal: 0
      };
      $scope.total = 0;
      $scope.taxRates = [];
    }

    /**
     * Initializes empty creditnotes item
     */
    function newCreditnotesItem () {
      $scope.creditnotes.items.push({
        description: null,
        financial_type_id: null,
        unit_price: null,
        quantity: null,
        tax_rate: 0,
        subtotal_amount: 0
      });
    }

    /**
     * Removes a creditnotes line item
     *
     * @param {number} index element index to be removed
     */
    function removeCreditnotesItem (index) {
      $scope.creditnotes.items.splice(index, 1);
      $scope.$emit('totalChange');
    }

    /**
     * Computes total and tax rates from API
     */
    function handleTotalChange () {
      crmApi4('Creditnotes', 'computeTotal', {
        lineItems: $scope.creditnotes.items
      }).then(function (results) {
        $scope.taxRates = results[0].taxRates;
        $scope.creditnotes.total = results[0].totalBeforeTax;
        $scope.creditnotes.grandTotal = results[0].totalAfterTax;
      }, function () {
        // handle failure
      });
    }

    /**
     * Persists creditnotes and redirects on success
     */
    function saveCreditnotes () {
      if (!validateForm()) {
        return;
      }

      $scope.submitInProgress = true;
      crmApi4('Creditnotes', 'save', { records: [$scope.creditnotes] })
        .then(function () {
          showSucessNotification();
          redirectToAppropraitePage();
        }, function () {
          $scope.submitInProgress = false;
          CRM.alert('Unable to create credit notes', ts('Error'), 'error');
        });
    }

    /**
     * Validates form before saving
     *
     * @returns {boolean} true if form is valid, otherwise false
     */
    function validateForm () {
      angular.forEach($scope.creditnotesForm.$$controls, function (control) {
        control.$setDirty();
        control.$validate();
      });

      return $scope.creditnotesForm.$valid;
    }

    /**
     * Handles page rediection after successfully creating creditnotes.
     */
    function redirectToAppropraitePage () {
      if ($scope.isUpdate) {
        $window.location.href = $window.document.referrer;
        return;
      }

      $window.location.href = 'a#/';
    }

    /**
     * Show Ceditnotes success create notification.
     */
    function showSucessNotification () {
      const msg = !$scope.isUpdate ? 'Credit Note has been created successfully.' : 'Details updated successfully';
      CRM.alert(msg, ts('Saved'), 'success');
    }


  }
})(angular, CRM.$, CRM._);
