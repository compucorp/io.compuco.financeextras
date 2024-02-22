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
      },
      scope: {
        id: '@',
        context: '@',
        contactId: '@',
        contributionId: '@',
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
   * @param {object} CreditNoteStatus service
   * @param {object} Company service
   */
  function creditnoteCreateController ($scope, $location, $window, CurrencyCodes, crmUiHelp, crmApi4, CreditNoteStatus, Company) {
    const defaultCurrency = 'GBP';
    const financialTypesCache = new Map();

    $scope.ts = CRM.ts();
    $scope.taxTerm = '';
    $scope.crmUrl = CRM.url;
    $scope.formValid = true;
    $scope.roundTo = roundTo;
    $scope.disableCurrency = false;
    $scope.formatMoney = formatMoney;
    $scope.isView = $scope.context == 'view'
    $scope.saveCreditnotes = saveCreditnotes;
    $scope.calculateSubtotal = calculateSubtotal;
    $scope.isUpdate = $scope.context == 'update';
    $scope.companies = Company.getAll();
    $scope.currencyCodes = CurrencyCodes.getAll();
    $scope.handleFinancialTypeChange = handleFinancialTypeChange;
    $scope.hs = crmUiHelp({ file: 'CRM/Financeextras/CreditNoteCtrl' });
    $scope.currencySymbol = CurrencyCodes.getSymbol(defaultCurrency);

    (function init () {
      initializeCreditnotes();
      prepopulateCreditnotes();
      prepopulateFromContribution();
      setDefaultContactID();
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
        owner_organization: $scope.companies.length === 1 ? String($scope.companies[0].contact_id) : null,
        cn_number: null,
        date: $.datepicker.formatDate('yy-mm-dd', new Date()),
        status_id: CreditNoteStatus.getValueByName('open'),
        items: [{
          description: null,
          financial_type_id: null,
          unit_price: null,
          quantity: null,
          tax_rate: 0,
          line_total: 0
        }],
        total: 0,
        grandTotal: 0
      };
      $scope.total = 0;
      $scope.taxRates = [];

      getTaxTerm().then((taxTerm) => $scope.taxTerm = taxTerm)
    }

    /**
     * Prepopulates credit note line items from contrbution
     */
    function prepopulateFromContribution() {
      if (!parseInt($scope.contributionId)) {
        return;
      }

      crmApi4('Contribution', 'get', {
        select: ["*", "financeextras_contribution_owner.owner_organization"],
        where: [["id", "=", $scope.contributionId]],
        chain: {"items":["LineItem", "get", {"where":[["contribution_id", "=", "$id"]]}]}
      }).then(function (result) {
        const contribution = result[0] ?? null;

        if (!contribution) {
          return
        }

        $scope.contactId = contribution.contact_id
        $scope.creditnotes.contact_id = contribution.contact_id
        $scope.creditnotes.owner_organization = String(contribution['financeextras_contribution_owner.owner_organization'])
        $scope.creditnotes.currency = contribution.currency
        $scope.disableCurrency = true
        $scope.currencySymbol = CurrencyCodes.getSymbol(contribution.currency);

        const lineItems = contribution.items;
        // Ensure the due amount is not less than zero
        const dueAmount = Math.max(contribution.total_amount - contribution.paid_amount, 0)
        const duePercent = (100 * dueAmount) /contribution.total_amount
        for (let i = 0; i < lineItems.length; i++) {
          let qty = lineItems[i].qty * (duePercent/100)
          console.log(qty, lineItems[i].qty, duePercent)
          // ensure quantity doesn't exceed 4 decimals (note this is not rounding)
          qty = Math.floor(qty * 10000) / 10000
          let unitPrice = Number((lineItems[i].unit_price).toFixed(2))
          $scope.creditnotes.items[i] = {
            quantity: qty,
            unit_price: unitPrice,
            description: lineItems[i].label,
            financial_type_id: lineItems[i].financial_type_id,
            tax_rate: 0,
            line_total: qty * unitPrice
          }

          handleFinancialTypeChange(i);
        }
      });

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
        line_total: 0
      });
    }

    /**
     * Prepopulates credit notes using credit note ID
     */
    function prepopulateCreditnotes () {
      if (!parseInt($scope.id)) {
        return;
      }

      crmApi4('CreditNote', 'get', {
        where: [["id", "=", $scope.id]],
        chain: {"items":["CreditNoteLine", "get", {"where":[["credit_note_id", "=", "$id"]], "select": ['*', 'financial_type_id.name']}]}
      }).then(function (result) {
        const creditnotes = result[0] ?? null;
        $scope.creditnotes.id = creditnotes.id
        $scope.creditnotes.contact_id = creditnotes.contact_id
        $scope.creditnotes.owner_organization = String(creditnotes.owner_organization)
        $scope.creditnotes.currency = creditnotes.currency
        $scope.currencySymbol = CurrencyCodes.getSymbol(creditnotes.currency);
        $scope.creditnotes.cn_number = creditnotes.cn_number
        $scope.creditnotes.date = $.datepicker.formatDate('yy-mm-dd', new Date(creditnotes.date))
        $scope.creditnotes.description = creditnotes.description
        $scope.creditnotes.reference = creditnotes.reference
        $scope.creditnotes.comment = creditnotes.comment
        $scope.creditnotes.status_id = creditnotes.status_id
        $scope.creditnotes.items = []
        $scope.contactId = creditnotes.contact_id

        creditnotes.items.forEach((element, i) => {
          $scope.creditnotes.items.push({
            description: element.description,
            financial_type_id: element.financial_type_id,
            unit_price: element.unit_price,
            quantity: element.quantity,
            line_total: element.line_total,
            financial_type: element['financial_type_id.name']
          })

          handleFinancialTypeChange(i);
        });
        CRM.wysiwyg.setVal('#creditnotes-description', $scope.creditnotes.description);
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
     * Persists creditnotes and redirects on success
     */
    function saveCreditnotes () {
      if (!validateForm()) {
        return;
      }

      $scope.submitInProgress = true;
      crmApi4('CreditNote', 'save', { records: [$scope.creditnotes] })
        .then(function (result) {
          showSucessNotification();
          redirectToAppropraitePage(result[0].id);
        }, function () {
          $scope.submitInProgress = false;
          CRM.alert(`Unable to ${$scope.isUpdate? 'update': 'create'} credit note`, ts('Error'), 'error');
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
     *
     * @param {boolean} the credit note ID
     */
    function redirectToAppropraitePage (creditNoteId) {
      if ($scope.isUpdate) {
        $window.location.href = $window.document.referrer;
        return;
      }

      $window.location.href = CRM.url(`/contribution/creditnote/allocate?crid=${creditNoteId}`);
    }

    /**
     * Show Ceditnotes success create notification.
     */
    function showSucessNotification () {
      const msg = !$scope.isUpdate ? 'Credit Note has been created successfully.' : 'Details updated successfully';
      CRM.alert(msg, ts('Saved'), 'success');
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
          if (entityFinancialAccounts[0]) {
            financialTypesCache.set(financialTypeId, entityFinancialAccounts[0]['financialAccount']);
            updateFinancialTypeDependentFields(financialTypeId);
          }
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
     * Formats a number into the number format of the currently selected currency
     *
     * @param {number} value the number to be formatted
     * @param {string } currency the selected currency
     * @returns {number} the formatted number
     */
    function formatMoney (value, currency) {
      return CRM.formatMoney(value, true, CurrencyCodes.getFormat(currency));
    }

    /**
     * Sums credit note line item without tax, and computes tax rates separately
     *
     * @param {number} index of the credit note line item
     */
    function calculateSubtotal (index) {
      const item = $scope.creditnotes.items[index];
      if (!item) {
        return;
      }

      item.line_total = item.unit_price * item.quantity || 0;
      $scope.$emit('totalChange');
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

    /**
     * Sets default contact ID.
     */
    function setDefaultContactID () {
      if (!parseInt($scope.contributionId) && parseInt($scope.contactId)) {
        $scope.creditnotes.contact_id = $scope.contactId
      }
    }

  }
})(angular, CRM.$, CRM._);
