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
        // Template URLs are not cache-busted like package JS is, so this
        // file is named link.panel.html (renamed from manage.panel.html)
        // to bypass HTTP caches that hold the pre-3.0 template. Rename it
        // again if its content ever changes.
        templateUrl: RouteHelpers.trusted(
          RouteHelpers.package("rdns").asset(
            "client/ptr/manage/link.panel.html"
          )
        ),
        context: {
          serverId: ServerManage.getServer().id,
        },
      };
    };
  }
})();
