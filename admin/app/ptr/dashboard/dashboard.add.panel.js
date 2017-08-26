(function () {
  'use strict';

  var DIR = 'admin/ptr/dashboard/';

  angular
    .module('pkg.rdns.ptr.dashboard')
    .factory('DashboardPtrPanel', DashboardPtrPanelFactory);

  /**
   * DashboardPtrPanel Factory
   *
   * @ngInject
   */
  function DashboardPtrPanelFactory(
    RouteHelpers,
    EventEmitter,
    Loader,
    _
  ) {
    return function () {
      return new DashboardPtrPanel(
        RouteHelpers,
        EventEmitter,
        Loader,
        _
      );
    };
  }

  function DashboardPtrPanel(
    RouteHelpers,
    EventEmitter,
    Loader,
    _
  ) {
    var panel = this;

    var pkg = RouteHelpers.package('rdns');
    var $ptr = pkg.api().all('ptr');
    panel.templateUrl = RouteHelpers.trusted(pkg.asset(
        DIR + 'dashboard.add.html'
    ));

    panel.context = {
      ptrs: [{
        ip:'',
        ptr:''
      }],
      submit: onSubmit,
      addRow: addRow,
      loader: Loader(),
    };
    EventEmitter().bindTo(panel);

    RouteHelpers.loadLang('pkg:rdns:admin:ptr');

      //////////
    function addRow(item) {
      if ((item.ip.length || item.ptr.length)
          && (panel.context.ptrs[panel.context.ptrs.length-1].ip.length
              || panel.context.ptrs[panel.context.ptrs.length-1].ptr.length)
      ) {
          panel.context.ptrs.push({
            ip:'',
            ptr:''
          });
      }
    }

    function onSubmit() {
      var items = _.filter(panel.context.ptrs, function(item){
        if (item.ip && item.ptr) {
          return true;
        }
        return false;
      });

      _.each(items, function(item){
          $ptr.post(item);
      });
      setPtr();
    }

    function setPtr() {
        panel.context.ptrs = [{
                ip:'',
                ptr:''
            }];
    }
  }
})();
