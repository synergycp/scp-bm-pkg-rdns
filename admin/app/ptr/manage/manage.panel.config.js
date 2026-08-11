(function () {
  "use strict";

  angular
    .module("pkg.rdns.ptr.manage")
    .config(configurePanels)
    .factory("pkg.rdns.ptr.manage.panel", ManagePanelFactory);

  function configurePanels(ServerManageProvider) {
    ServerManageProvider.panels.left.after(
      "notes",
      "pkg.rdns.ptr.manage.panel"
    );
  }

  /**
   * Small panel on the server page linking to the dedicated
   * rDNS management page (app.hardware.server.view.rdns).
   *
   * @ngInject
   */
  function ManagePanelFactory(ServerManage, RouteHelpers) {
    return function () {
      RouteHelpers.loadLang("pkg:rdns:admin:ptr");

      return {
        templateUrl: RouteHelpers.trusted(
          RouteHelpers.package("rdns").asset("admin/ptr/manage/manage.panel.html")
        ),
        context: {
          serverId: ServerManage.getServer().id,
        },
      };
    };
  }
})();
