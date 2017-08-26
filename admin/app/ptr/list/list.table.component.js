(function () {
  'use strict';

  angular
    .module('pkg.rdns.ptr.list')
    .component('ptrTable', {
      require: {
        list: '\^list',
      },
      bindings: {
        showIp: '=?',
        showPtr: '=?',
        showActions: '=?',
      },
      controller: 'PtrTableCtrl as table',
      transclude: true,
      templateUrl: tableTemplateUrl
    })
    .controller('PtrTableCtrl', PtrTableCtrl)
  ;

  /**
   * @ngInject
   */
  function tableTemplateUrl(RouteHelpers) {
    return RouteHelpers.trusted(
      RouteHelpers.package('rdns')
        .asset(
          'admin/ptr/list/list.table.html'
        )
    );
  }

  /**
   * @ngInject
   */
  function PtrTableCtrl() {
    var table = this;

    table.$onInit = init;

    ///////////

    function init() {
      _.defaults(table, {
        showIp: true,
        showPtr: true,
        showActions: true,
      });
    }
  }
})();
