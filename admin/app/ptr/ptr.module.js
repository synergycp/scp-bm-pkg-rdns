(function () {
  'use strict';

  angular
    .module('pkg.rdns.ptr', [
      'scp.angle.layout.list',
      'scp.core.api',
      'pkg.rdns.ptr.list',
      'pkg.rdns.ptr.dashboard',
      'pkg.rdns.ptr.manage',
    ]);
})();
