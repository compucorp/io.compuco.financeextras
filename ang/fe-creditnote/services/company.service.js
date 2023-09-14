(function (angular, $, _, CRM) {
  var module = angular.module('fe-creditnote');

  module.service('Company', Company);

  /**
   * Company Service
   */
  function Company () {
    this.getAll = function () {
      return CRM['fe-creditnote'].companies;
    };
  }
})(angular, CRM.$, CRM._, CRM);
