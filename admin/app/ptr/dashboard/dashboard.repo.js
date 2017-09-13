(function () {
  'use strict';

  angular
    .module('pkg.rdns.ptr.dashboard')
    .service('RdnsDashboardRepo', RdnsDashboardRepo)
    .run(addRdnsDashboardRepo)
    ;

  /**
   * @ngInject
   */
  function addRdnsDashboardRepo(Dashboard) {
      //Dashboard.add('RdnsDashboardRepo');
  }

  /**
   * RdnsDashboardRepo
   *
   * @ngInject
   */
  function RdnsDashboardRepo(
    EventEmitter,
    DashboardPtrPanel,
    RouteHelpers
  ) {
    var repo = this;
    repo.all = all;
    EventEmitter().bindTo(repo);

    ///////////

    function all() {
        repo.fire(
            'item',
            DashboardPtrPanel()
        );
        RouteHelpers.loadLang('pkg:rdns:admin:ptr');
    }
  }
})();
