(function (angular) {
  var module = angular.module('fe-creditnote');

  module.config(function ($routeProvider) {
    $routeProvider.when('/add', {
      template: function () {
        return `
          <creditnote-create></creditnote-create>
        `;
      }
    });

  });
})(angular);
