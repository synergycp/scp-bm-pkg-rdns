(function () {
  angular
    .module('pkg.rdns.ptr.list')
    .config(routeConfig)
    ;

  /**
   * @ngInject
   */
  function routeConfig($stateProvider, RouteHelpersProvider) {
    var helper = RouteHelpersProvider;
      var pkg = helper.package('rdns');
      pkg
      .state('ptr.list', {
        url: '?q',
        title: 'Ptrs',
        controller: 'PtrIndexCtrl as vm',
        reloadOnSearch: false,
        templateUrl: pkg.asset('admin/ptr/list/list.index.html'),
      })
      ;
  }
})();
