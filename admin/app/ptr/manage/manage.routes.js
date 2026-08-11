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
      // The ?v= busts browser/proxy caches (template URLs get no md5sum
      // like package JS does); bump it whenever the template changes.
      templateUrl: pkg.asset('admin/ptr/manage/manage.page.html') + '?v=3.1.0',
      resolve: helper.resolveFor(pkg.lang('admin:ptr'), pkg.lang('admin:manage')),
    });
  }
})();
