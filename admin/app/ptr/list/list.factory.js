(function () {
  'use strict';

  angular
    .module('pkg.rdns.ptr.list')
    .factory('PtrList', PtrListFactory);

  /**
   * PtrList Factory
   *
   * @ngInject
   */
  function PtrListFactory(ListConfirm, List, RouteHelpers) {
    var pkg = RouteHelpers.package('rdns');
    return function () {
      var list = List(pkg.api().all('ptr'));
      list.confirm = ListConfirm(list, 'pkg.rdns.admin.ptr.modal.delete');

      list.bulk.add('Delete', list.confirm.delete);

      return list;
    };
  }
})();
