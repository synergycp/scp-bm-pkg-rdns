(function () {
  "use strict";

  angular
    .module("pkg.rdns.ptr.manage")
    .controller("PkgRdnsServerPtrCtrl", ServerPtrPageCtrl);

  /**
   * @ngInject
   */
  function ServerPtrPageCtrl(Api, $stateParams, RouteHelpers, _, Loader) {
    RouteHelpers.loadLang("pkg:rdns:admin:ptr");
    RouteHelpers.loadLang("pkg:rdns:admin:manage");
    var vm = this;
    vm.serverId = $stateParams.id;
    vm.server = {};
    vm.entities_filter = [];
    vm.tabs = {
      v4: { rows: [], page: 1 },
      v6: { rows: [], page: 1 },
    };
    vm.tab = "v4";
    vm.pageSize = 50;
    vm.loader = Loader();
    vm.change = ptrChange;
    vm.save = save;
    vm.setTab = setTab;
    vm.prevPage = prevPage;
    vm.nextPage = nextPage;
    vm.pageEnd = pageEnd;
    var pkg = RouteHelpers.package("rdns");
    var $ptr = pkg.api().all("ptr");

    // ServerManage.getServer() is only populated on the manage page's own
    // controller, so this page loads the server itself.
    Api.all("server")
      .one("" + vm.serverId)
      .get()
      .then(setServer);

    Api.all("entity")
      .getList({
        server: vm.serverId,
        include_pool_ips: true,
      })
      .then(setEntities)
      .then(loadPtrs);

    setSendData();

    //---------------

    function setServer(server) {
      vm.server = server;
    }

    function setTab(name) {
      vm.tab = name;
    }

    function prevPage(tab) {
      if (tab.page > 1) {
        tab.page--;
      }
    }

    function nextPage(tab) {
      if (tab.page * vm.pageSize < tab.rows.length) {
        tab.page++;
      }
    }

    function pageEnd(tab) {
      return Math.min(tab.page * vm.pageSize, tab.rows.length);
    }

    function setEntities(items) {
      var filter = _.map(items, function (item) {
        return item.id;
      });
      _.setContents(vm.entities_filter, filter);
      return items;
    }

    function loadPtrs(entities) {
      return $ptr
        .getList({
          "entity[]": vm.entities_filter,
        })
        .then(function (ptrs) {
          buildRows(entities, ptrs);
        });
    }

    function buildRows(entities, ptrs) {
      var v4ips = ipConcat(
        _.map(_.filter(entities, isV4Entity), function (item) {
          return getRange(item.full_ip);
        })
      );

      // IPv6 ranges cannot be enumerated the way IPv4 ranges are, so the
      // IPv6 tab lists single-address entities plus any existing v6 PTRs.
      var v6ips = _.map(_.filter(entities, isSingleV6Entity), function (item) {
        return item.full_ip;
      });
      var v6seen = {};
      _.each(v6ips, function (ip) {
        v6seen[normalizeIp(ip)] = true;
      });
      var v6rows = _.map(v6ips, toRow);
      _.each(ptrs, function (ptr) {
        if (!isV6Ip(ptr.ip) || v6seen[normalizeIp(ptr.ip)]) {
          return;
        }
        v6seen[normalizeIp(ptr.ip)] = true;
        v6rows.push({
          id: ptr.id,
          ip: ptr.ip,
          ptr: ptr.ptr,
        });
      });

      _.setContents(vm.tabs.v4.rows, _.map(v4ips, toRow));
      _.setContents(vm.tabs.v6.rows, v6rows);

      function toRow(ip) {
        var ptr = _.find(ptrs, function (tt) {
          return normalizeIp(tt.ip) == normalizeIp(ip);
        });
        return {
          id: typeof ptr == "undefined" ? null : ptr.id,
          ip: ip,
          ptr: typeof ptr == "undefined" ? null : ptr.ptr,
        };
      }
    }

    function isV4Entity(item) {
      return !isV6Ip(item.full_ip);
    }

    function isSingleV6Entity(item) {
      return (
        isV6Ip(item.full_ip) &&
        item.full_ip.indexOf("-") === -1 &&
        item.full_ip.indexOf("*") === -1 &&
        item.full_ip.indexOf("/") === -1
      );
    }

    function isV6Ip(ip) {
      return ("" + ip).indexOf(":") !== -1;
    }

    function normalizeIp(ip) {
      return ("" + ip).toLowerCase();
    }

    function allRows() {
      return vm.tabs.v4.rows.concat(vm.tabs.v6.rows);
    }

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
          vm.sendData.update[item.ip] = item;
          return;
        }
        vm.sendData.remove[item.ip] = item;
        return;
      }

      if (item.ptr) {
        vm.sendData.post[item.ip] = item;
      }
    }

    function clearSendData(ip) {
      delete vm.sendData.update[ip];
      delete vm.sendData.remove[ip];
      delete vm.sendData.post[ip];
    }

    function getData() {
      var data = vm.sendData;
      setSendData();
      return _.clone(data);
    }

    function reList(items) {
      _.each(allRows(), function (row) {
        if (normalizeIp(row.ip) == normalizeIp(items.ip)) {
          row.id = items.id;
          row.ptr = items.ptr;
        }
      });
    }

    function removeItems(items) {
      _.each(allRows(), function (row) {
        if (row.id == items.route) {
          row.id = "";
          row.ptr = "";
        }
      });
    }

    function setSendData() {
      vm.sendData = {
        post: {},
        update: {},
        remove: {},
      };
    }
  }
})();
