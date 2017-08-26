(function () {
  'use strict';

  angular
    .module('pkg.rdns.ptr')
    .component('ptrButtons', {
      require: {},
      bindings: {
        ptr: '=',
      },
      controller: 'PtrButtonsCtrl as buttons',
      transclude: true,
      templateUrl: buttonTemplateUrl
    })
    .controller('PtrButtonsCtrl', PtrButtonsCtrl);

    function buttonTemplateUrl(RouteHelpers) {

        "ngInject";
        return RouteHelpers.trusted(
            RouteHelpers.package('rdns').asset(
                'admin/ptr/ptr.view.buttons.html'
            )
        );
    }

  /**
   * @ngInject
   */
  function PtrButtonsCtrl(PtrList, Loader, $state) {
    var buttons = this;

    buttons.loader = Loader();
    buttons.$onInit = init;
    buttons.delete = doDelete;


    //////////

    function init() {

    }

    function doDelete() {
      return buttons.loader.during(
        PtrList()
          .confirm
          .delete([buttons.ptr])
          .result.then(transferToList)
      );
    }

    function transferToList() {
      $state.go('pkg.rdns.ptr.list');
    }
  }
})();
