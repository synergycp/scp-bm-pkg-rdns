(function () {
  'use strict';

  angular
    .module('pkg.rdns.ptr.list.filters')
    .component('ptrFilters', {
      require: {
        list: '\^list',
      },
      bindings: {
        show: '<',
        current: '=',
        change: '&?',
      },
      controller: 'PtrFiltersCtrl as filters',
      transclude: true,
      templateUrl: function(RouteHelpers) {
          "ngInject";
          return RouteHelpers.trusted(
              RouteHelpers.package('rdns').asset(
                  'admin/ptr/list/filters/filters.html'
              )
          );
      }
    })
    ;
})();
