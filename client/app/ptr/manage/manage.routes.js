(function () {
  angular
    .module('pkg.rdns.ptr.manage')
    .config(routeConfig)
    ;

  /**
   * @ngInject
   */
  function routeConfig($stateProvider, RouteHelpersProvider) {
    var helper = RouteHelpersProvider;
    var pkg = helper.package('rdns');

    // Registered under the core server view state so the page stays
    // contextual to the server: /hardware/server/:id/rdns
    $stateProvider.state('app.hardware.server.view.rdns', {
      url: '/rdns',
      title: 'Reverse DNS',
      controller: 'PkgRdnsServerPtrCtrl as vm',
      templateUrl: pkg.asset('client/ptr/manage/manage.page.html'),
      resolve: helper.resolveFor(pkg.lang('client:ptr'), pkg.lang('client:manage')),
    });
  }
})();
