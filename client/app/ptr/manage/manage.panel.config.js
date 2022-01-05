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
   * @ngInject
   */
  function ManagePanelFactory(Api, ServerManage, RouteHelpers, _, Loader) {
    return function () {
      return new ManagePanel(Api, ServerManage, RouteHelpers, _, Loader);
    };
  }

  function ManagePanel(Api, ServerManage, RouteHelpers, _, Loader) {
    RouteHelpers.loadLang("pkg:rdns:client:ptr");
    var panel = this;
    panel.entities_filter = [];
    panel.ptrs = [];
    var pkg = RouteHelpers.package("rdns");
    var $ptr = pkg.api().all("ptr");

    Api.all("entity")
      .getList({
        server: ServerManage.getServer().id,
        include_pool_ips: true,
      })
      .then(setEntities)
      .then(ipRange)
      .then(ipConcat)
      .then(unionPtr)
      .then(setPtrs);

    setSendData();

    //---------------

    return {
      templateUrl: RouteHelpers.trusted(
        RouteHelpers.package("rdns").asset(
          "client/ptr/manage/manage.panel.html"
        )
      ),
      context: {
        ptrs: panel.ptrs,
        change: ptrChange,
        save: save,
        loader: Loader(),
        isExpanded: false,
      },
    };

    function save() {
      var data = getData();

      _.each(data.post, function (item) {
        $ptr.post(item).then(reList);
      });

      _.each(data.remove, function (item) {
        $ptr
          .one("" + item.id)
          .remove()
          .then(removeItems);
      });

      _.each(data.update, function (item) {
        $ptr
          .one("" + item.id)
          .patch(item)
          .then(reList);
      });
    }

    function setPtrs(items) {
      _.setContents(panel.ptrs, items);
    }

    function setEntities(items) {
      var filter = _.map(items, function (item) {
        return item.id;
      });
      _.setContents(panel.entities_filter, filter);
      return items;
    }

    function unionPtr(items) {
      return $ptr
        .getList({
          "entity[]": panel.entities_filter,
        })
        .then(function (ptrs) {
          return _.map(items, function (item) {
            var ptr = _.find(ptrs, function (tt) {
              return tt.ip == item;
            });
            return {
              id: typeof ptr == "undefined" ? null : ptr.id,
              ip: item,
              ptr: typeof ptr == "undefined" ? null : ptr.ptr,
            };
          });
        });
    }

    function ipRange(items) {
      return _.map(items, function (item) {
        return getRange(item.full_ip);
      });
    }

    function ipConcat(items) {
      return [].concat.apply([], items);
    }

    function getRange(range) {
      var res = range.split(".").map(function (section) {
        if (!isNaN(section)) {
          return [parseInt(section)];
        } else if (section.indexOf("-") !== -1) {
          var r = section.split("-");
          var n = parseInt(r[0]);
          var m = parseInt(r[1]);
          if (n > m) {
            n = parseInt(r[1]);
            m = parseInt(r[0]);
          }
          var a = [];
          for (var i = n; i <= m; i++) {
            a.push(i);
          }
          return a;
        } else if (section === "*") {
          return Array.apply(null, {
            length: 255,
          }).map(Number.call, Number);
        }
      });

      var list = [];
      res[0].forEach(function (a) {
        res[1].forEach(function (b) {
          res[2].forEach(function (c) {
            res[3].forEach(function (d) {
              list.push([a, b, c, d].join("."));
            });
          });
        });
      });

      return list;
    }

    function ptrChange(item) {
      clearSendData(item.ip);
      if (item.id) {
        if (item.ptr) {
          panel.sendData.update[item.ip] = item;
          return;
        }
        panel.sendData.remove[item.ip] = item;
        return;
      }

      if (item.ptr) {
        panel.sendData.post[item.ip] = item;
      }
    }

    function clearSendData(ip) {
      delete panel.sendData.update[ip];
      delete panel.sendData.remove[ip];
      delete panel.sendData.post[ip];
    }

    function getData() {
      var data = panel.sendData;
      setSendData();
      return _.clone(data);
    }

    function reList(items) {
      var list = _.map(panel.ptrs, function (item) {
        if (item.ip == items.ip) {
          return items;
        }
        return item;
      });

      _.assign(panel.ptrs, list);
    }

    function removeItems(items) {
      var list = _.map(panel.ptrs, function (item) {
        if (item.id == items.route) {
          item.id = "";
          item.ptr = "";
          return item;
        }
        return item;
      });

      _.assign(panel.ptrs, list);
    }

    function setSendData() {
      panel.sendData = {
        post: {},
        update: {},
        remove: {},
      };
    }
  }
})();
