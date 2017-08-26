(function () {
    'use strict';

    angular
        .module('pkg.rdns.ptr')
        .constant('PkgRdnsPtrNav', {
            text: "rDNS PTRs",
            sref: "app.pkg.rdns.ptr.list",
        })
        .config(NavConfig)
    ;

    /**
     * @ngInject
     */
    function NavConfig(NavProvider, PkgRdnsPtrNav) {
        NavProvider
            .group('network')
            .item(PkgRdnsPtrNav)
        ;
    }
})();
