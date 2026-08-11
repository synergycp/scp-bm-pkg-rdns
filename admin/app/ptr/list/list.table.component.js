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
        showServer: '=?',
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
    // The ?v= busts browser/proxy caches (template URLs get no md5sum
    // like package JS does); bump it whenever the template changes.
    return RouteHelpers.trusted(
      RouteHelpers.package('rdns')
        .asset(
          'admin/ptr/list/list.table.html'
        ) + '?v=3.1.6'
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
        showServer: true,
        showActions: true,
      });
    }
  }
})();
