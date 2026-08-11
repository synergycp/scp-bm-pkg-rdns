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
      RouteHelpers.loadLang("pkg:rdns:client:ptr");
      RouteHelpers.loadLang("pkg:rdns:client:manage");

      return {
        // Template URLs get no md5sum cache-busting like package JS does;
        // the ?v= keeps browser/proxy caches from serving stale copies.
        // Bump it whenever the template changes.
        templateUrl: RouteHelpers.trusted(
          RouteHelpers.package("rdns").asset(
            "client/ptr/manage/link.panel.html"
          ) + "?v=3.1.0"
        ),
        context: {
          serverId: ServerManage.getServer().id,
        },
      };
    };
  }
})();
