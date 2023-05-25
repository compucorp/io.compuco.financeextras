(function (angular, $, _, CRM) {
  var module = angular.module('fe-creditnote');

  module.service('CurrencyCodes', CurrencyCodes);

  /**
   * CurrencyCodes Service
   */
  function CurrencyCodes () {
    this.getAll = function () {
      return CRM['fe-creditnote'].currencyCodes;
    };

    this.getSymbol = function (name) {
      return CRM['fe-creditnote']
        .currencyCodes
        .filter(currency => currency.name === name)
        .pop().symbol || '£';
    };

    this.getFormat = function (name) {
      return CRM['fe-creditnote']
        .currencyCodes
        .filter(currency => currency.name === name)
        .pop().format || null;
    };
  }
})(angular, CRM.$, CRM._, CRM);
