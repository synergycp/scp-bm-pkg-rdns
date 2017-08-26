(function () {
  angular
    .module('pkg.rdns')
    .config(routeConfig)
    ;

  /**
   * @ngInject
   */
  function routeConfig(RouteHelpersProvider) {
    var helper = RouteHelpersProvider;
    var pkg = helper.package('rdns');

    pkg.state('');
  }
})();
