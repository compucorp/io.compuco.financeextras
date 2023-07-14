(function (angular, $, _, CRM) {
  var module = angular.module('fe-creditnote');

  module.service('MoneyFormat', MoneyFormat);

  /**
   * MoneyFormat Service
   * 
   * @param {object} CurrencyCodes CurrencyCodes service
   */
  function MoneyFormat (CurrencyCodes) {
    
    /**
     * Formats a number into the number format of the currently selected currency
     *
     * @param {number} value the number to be formatted
     * @param {string } currency the selected currency
     * @param {boolean} symbol show the symbol
     * @returns {number} the formatted number
     */
    this.formatMoney = (value, currency, symbol) => {
      if (!currency) {
        currency = 'GBP' // If data is still loading, currency could be undefined
      }
      let money = CRM.formatMoney(value, true, CurrencyCodes.getFormat(currency));

      if (symbol) {
        money = `${CurrencyCodes.getSymbol(currency)} ${money}`
      }

      return money
    }
  }
})(angular, CRM.$, CRM._, CRM);
