(function () {
  "use strict";

  angular
    .module("pkg.rdns.ptr.manage")
    .controller("PkgRdnsServerPtrCtrl", ServerPtrPageCtrl);

  /**
   * @ngInject
   */
  function ServerPtrPageCtrl(Api, $stateParams, RouteHelpers, _, Loader) {
    RouteHelpers.loadLang("pkg:rdns:client:ptr");
    RouteHelpers.loadLang("pkg:rdns:client:manage");
    var vm = this;
    vm.serverId = $stateParams.id;
    vm.server = {};
    vm.entities_filter = [];
    vm.tabs = {
      v4: { rows: [], page: 1 },
      v6: { rows: [], page: 1 },
    };
    vm.tab = "v4";
    vm.hasV6 = false;
    vm.pageSize = 20;
    vm.pageSizes = [20, 50, 100, 500];
    vm.pageSizeChanged = pageSizeChanged;
    vm.newV6 = { ip: "", ptr: "" };
    vm.v6error = null;
    vm.loader = Loader();
    vm.change = ptrChange;
    vm.save = save;
    vm.setTab = setTab;
    vm.prevPage = prevPage;
    vm.nextPage = nextPage;
    vm.pageEnd = pageEnd;
    vm.addV6 = addV6;
    var v6entities = [];
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

    function pageSizeChanged() {
      _.each([vm.tabs.v4, vm.tabs.v6], function (tab) {
        var last = Math.max(1, Math.ceil(tab.rows.length / vm.pageSize));
        if (tab.page > last) {
          tab.page = last;
        }
      });
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

      // Entity IPv6 assignments live in v6_address (optionally with a
      // /prefix), not in full_ip, which is IPv4-only.
      v6entities = _.filter(
        _.map(entities, function (item) {
          return item.v6_address;
        }),
        function (v6) {
          return !!v6;
        }
      );

      // IPv6 ranges are too large to enumerate row-by-row, so the IPv6 tab
      // lists single-address entities plus existing v6 PTRs, and new
      // addresses are added through the add row.
      var v6ips = _.filter(v6entities, isSingleV6);
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

      vm.hasV6 = v6entities.length > 0 || v6rows.length > 0;
      if (!vm.hasV6) {
        vm.tab = "v4";
      }

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

    function addV6() {
      vm.v6error = null;
      var ip = ("" + (vm.newV6.ip || "")).trim();
      var ptr = ("" + (vm.newV6.ptr || "")).trim();

      if (!parseV6(ip)) {
        vm.v6error = "Enter a valid IPv6 address.";
        return;
      }

      if (!ipInV6Entities(ip)) {
        vm.v6error = "That address is not within an IPv6 range assigned to this server.";
        return;
      }

      var exists = _.find(vm.tabs.v6.rows, function (row) {
        return normalizeIp(row.ip) == normalizeIp(ip);
      });
      if (exists) {
        vm.v6error = "That address is already listed.";
        return;
      }

      var row = { id: null, ip: ip, ptr: ptr };
      vm.tabs.v6.rows.push(row);
      if (ptr) {
        ptrChange(row);
      }
      vm.hasV6 = true;
      vm.newV6 = { ip: "", ptr: "" };

      // Jump to the last page so the new row is visible.
      vm.tabs.v6.page = Math.max(
        1,
        Math.ceil(vm.tabs.v6.rows.length / vm.pageSize)
      );
    }

    function isV4Entity(item) {
      // Entities can be IPv6-only (null v4 address); those must not go
      // through the IPv4 range expansion.
      return !!item.address && ("" + item.full_ip).indexOf(".") !== -1;
    }

    function isSingleV6(ip) {
      return (
        ip.indexOf("-") === -1 &&
        ip.indexOf("*") === -1 &&
        ip.indexOf("/") === -1
      );
    }

    function isV6Ip(ip) {
      return ("" + ip).indexOf(":") !== -1;
    }

    /**
     * Parses an IPv6 address into 8 group integers, or null if invalid.
     * Dotted (IPv4-mapped) notation is not supported.
     */
    function parseV6(ip) {
      if (typeof ip !== "string" || !ip || ip.indexOf(".") !== -1) {
        return null;
      }
      var halves = ip.split("::");
      if (halves.length > 2) {
        return null;
      }
      var head = halves[0] === "" ? [] : halves[0].split(":");
      var tail =
        halves.length === 2 && halves[1] !== "" ? halves[1].split(":") : [];
      var groups = [];
      var i;
      if (halves.length === 2) {
        var missing = 8 - head.length - tail.length;
        if (missing < 1) {
          return null;
        }
        for (i = 0; i < head.length; i++) {
          groups.push(head[i]);
        }
        for (i = 0; i < missing; i++) {
          groups.push("0");
        }
        for (i = 0; i < tail.length; i++) {
          groups.push(tail[i]);
        }
      } else {
        groups = head;
      }
      if (groups.length !== 8) {
        return null;
      }
      var parsed = [];
      for (i = 0; i < 8; i++) {
        if (!/^[0-9a-fA-F]{1,4}$/.test(groups[i])) {
          return null;
        }
        parsed.push(parseInt(groups[i], 16));
      }
      return parsed;
    }

    /**
     * Whether the address falls inside any of the server's IPv6 entities.
     * Entities are matched as CIDR prefixes (or exact addresses without a
     * prefix). If no entity is in a parseable format, the check is skipped
     * and the API remains the authority.
     */
    function ipInV6Entities(ip) {
      var parsed = parseV6(ip);
      var parseable = 0;
      var match = false;

      _.each(v6entities, function (entity) {
        var parts = ("" + entity).split("/");
        var base = parseV6(parts[0]);
        var prefix = parts.length === 2 ? parseInt(parts[1], 10) : 128;
        if (!base || isNaN(prefix) || prefix < 0 || prefix > 128) {
          return;
        }
        parseable++;
        if (v6PrefixMatch(parsed, base, prefix)) {
          match = true;
        }
      });

      return parseable === 0 || match;
    }

    function v6PrefixMatch(ip, base, prefix) {
      var bits = prefix;
      for (var i = 0; i < 8 && bits > 0; i++) {
        var groupBits = Math.min(16, bits);
        var mask = (0xffff << (16 - groupBits)) & 0xffff;
        if ((ip[i] & mask) !== (base[i] & mask)) {
          return false;
        }
        bits -= groupBits;
      }
      return true;
    }

    /**
     * Canonical form for comparisons: IPv6 in expanded lowercase groups so
     * notation differences (compression, case) match; other values as
     * lowercase text.
     */
    function normalizeIp(ip) {
      var parsed = parseV6("" + ip);
      if (parsed) {
        var out = [];
        for (var i = 0; i < 8; i++) {
          out.push(parsed[i].toString(16));
        }
        return out.join(":");
      }
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
