(function (angular, $, _) {
  var module = angular.module('fe-creditnote');

  module.directive('creditnoteView', function ($timeout) {
    return {
      restrict: 'E',
      controller: 'creditnoteViewController',
      templateUrl: '~/fe-creditnote/directives/creditnote-view.directive.html',
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
      },
      scope: {
        id: '@',
        context: '@',
      }
    };
  });

  module.controller('creditnoteViewController', creditnoteViewController);

  /**
   * @param {object} $scope the controller scope
   * @param {object} CurrencyCodes CurrencyCodes service
   * @param {object} crmApi4 crm api V4 service
   * @param {object} MoneyFormat service
   */
  function creditnoteViewController ($scope, CurrencyCodes, crmApi4, MoneyFormat) {
    const defaultCurrency = 'GBP';
    const financialTypesCache = new Map();

    $scope.taxTerm = '';
    $scope.ts = CRM.ts();
    $scope.crmUrl = CRM.url;
    $scope.roundTo = roundTo;
    $scope.formatMoney = MoneyFormat.formatMoney;
    $scope.getContactLink = getContactLink;
    $scope.currencySymbol = CurrencyCodes.getSymbol(defaultCurrency);
    $scope.hasEditPermission = CRM['fe-creditnote'].canEditContribution;

    (function init () {
      prepopulateCreditnotes();

      getTaxTerm().then((taxTerm) => $scope.taxTerm = taxTerm)
      $scope.$on('totalChange', _.debounce(handleTotalChange, 250));
    }());

    /**
     * Prepopulates credit notes using credit note ID
     */
    function prepopulateCreditnotes () {
      if (!$scope.id) {
        return;
      }

      crmApi4('CreditNote', 'get', {
        where: [["id", "=", $scope.id]],
        select: ['*', 'contact_id.display_name', 'status_id:label'],
        chain: {"items":["CreditNoteLine", "get", {"where":[["credit_note_id", "=", "$id"]], "select": ['*', 'financial_type_id.name']}]}
      }).then(function (result) {
        const creditnotes = result[0] ?? null;
        $scope.creditnotes = creditnotes
        $scope.currency = creditnotes.currency
        $scope.currencySymbol = CurrencyCodes.getSymbol(creditnotes.currency);
        /*eslint-disable no-undef*/
        $scope.creditnotes.date = strftime(CRM['fe-creditnote'].shortDateFormat, creditnotes.date)
        creditnotes.items.forEach((element, i) => {
          element.financial_type = element['financial_type_id.name']
          handleFinancialTypeChange(i);
        });
        CRM.wysiwyg.setVal('#creditnotes-description', $scope.creditnotes.description);
      });
    }

    /**
     * Computes total and tax rates from API
     */
    function handleTotalChange () {
      crmApi4('CreditNote', 'computeTotal', {
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
     * Returns link to the contact dashboard
     *
     * @param {number} id the contact ID
     *
     * @returns {string} dashboard link
     */
    function getContactLink (id) {
      return CRM.url(`/contact/view?reset=1&cid=${id}`);
    }

    /**
     * Update tax filed and regenrate line item tax rates for line itme financial types
     *
     * @param {number} index of the credit note line item
     */
    function handleFinancialTypeChange (index) {
      $scope.creditnotes.items[index].tax_rate = 0;
      $scope.$emit('totalChange');

      if ($scope.creditnotes.items[index]['financial_type_id.name']) {
        $scope.creditnotes.items[index]['financial_type_id.name'] = '';
      }

      const updateFinancialTypeDependentFields = (financialTypeId) => {
        $scope.creditnotes.items[index].tax_rate = financialTypesCache.get(financialTypeId).tax_rate;
        $scope.creditnotes.items[index].tax_name = financialTypesCache.get(financialTypeId).name;
        $scope.$emit('totalChange');
      };

      const financialTypeId = $scope.creditnotes.items[index].financial_type_id;
      if (financialTypeId && financialTypesCache.has(financialTypeId)) {
        updateFinancialTypeDependentFields(financialTypeId);
        return;
      }

      if (financialTypeId) {
        crmApi4('EntityFinancialAccount', 'get', {
          where: [["account_relationship:name", "=", "Sales Tax Account is"], ["entity_table", "=", "civicrm_financial_type"], ["entity_id", "=", financialTypeId]],
          limit: 1,
          chain: {"financialAccount":["FinancialAccount", "get", {"where":[["id", "=", "$financial_account_id"]]}, 0]}
        }).then(function(entityFinancialAccounts) {
          financialTypesCache.set(financialTypeId, entityFinancialAccounts[0]['financialAccount']);
          updateFinancialTypeDependentFields(financialTypeId);
        });
      }
    }

    /**
     * Rounds floating ponumber n to specified number of places
     *
     * @param {*} n number to round
     * @param {*} place decimal places to round to
     * @returns {number} the rounded off number
     */
    function roundTo (n, place) {
      return +(Math.round(n + 'e+' + place) + 'e-' + place);
    }

    /**
     * Retrieves the contribution tax term from settings
     * 
     * @returns {string} tax term
     */
    async function getTaxTerm() {
      const setting = await crmApi4('Setting', 'get', {
        select: ["contribution_invoice_settings"]
      });

      return setting[0]['value']['tax_term'] ?? '';
    }

  }
})(angular, CRM.$, CRM._);
