(function () {
  angular
    .module('pkg.rdns.ptr')
    .config(routeConfig)
    ;

  /**
   * @ngInject
   */
  function routeConfig($stateProvider, RouteHelpersProvider) {
    var helper = RouteHelpersProvider;
      var pkg = helper.package('rdns');
      pkg
        .state('ptr', {
          url: '/ptr',
          abstract: true,
          template: helper.dummyTemplate,
          resolve: helper.resolveFor(pkg.lang('admin:ptr')),
        })
        .state('ptr.view', {
          url: '/:id',
          title: 'View Ptr',
          controller: 'PtrViewCtrl as vm',
          templateUrl: pkg.asset('admin/ptr/ptr.view.html'),
        })
        .url('ptr/?([0-9]*)', mapReportUrl)
        .sso('ptr', function($state, options) {
            return mapReportUrl($state, options.id);
        })
      ;

      function mapReportUrl($state, id) {
          return $state.href('ptr.' + (id ? 'view' : 'list'), {
              id: id,
          });
      }
  }
})();
