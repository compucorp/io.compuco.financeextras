(function (angular, $, _, CRM) {
  var module = angular.module('fe-creditnote');

  module.service('CreditNoteStatus', CreditNoteStatus);

  /**
   * CreditNoteStatus Service
   */
  function CreditNoteStatus () {
    this.getAll = function () {
      return CRM['fe-creditnote'].creditNoteStatus;
    };

    this.getValueByName = function (name) {
      return CRM['fe-creditnote']
        .creditNoteStatus
        .filter(status => status.name === name)
        .pop().value || '';
    };
  }
})(angular, CRM.$, CRM._, CRM);
