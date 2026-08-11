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
    vm.ptrs = [];
    vm.loader = Loader();
    vm.change = ptrChange;
    vm.save = save;
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
      .then(ipRange)
      .then(ipConcat)
      .then(unionPtr)
      .then(setPtrs);

    setSendData();

    //---------------

    function setServer(server) {
      vm.server = server;
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

    function setPtrs(items) {
      _.setContents(vm.ptrs, items);
    }

    function setEntities(items) {
      var filter = _.map(items, function (item) {
        return item.id;
      });
      _.setContents(vm.entities_filter, filter);
      return items;
    }

    function unionPtr(items) {
      return $ptr
        .getList({
          "entity[]": vm.entities_filter,
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
      var list = _.map(vm.ptrs, function (item) {
        if (item.ip == items.ip) {
          return items;
        }
        return item;
      });

      _.assign(vm.ptrs, list);
    }

    function removeItems(items) {
      var list = _.map(vm.ptrs, function (item) {
        if (item.id == items.route) {
          item.id = "";
          item.ptr = "";
          return item;
        }
        return item;
      });

      _.assign(vm.ptrs, list);
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
