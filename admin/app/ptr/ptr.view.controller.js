(function () {
  'use strict';

  angular
    .module('pkg.rdns.ptr')
    .controller('PtrViewCtrl', PtrViewCtrl)
  ;

  /**
   * View Ptr Controller
   *
   * @ngInject
   */
  function PtrViewCtrl(Edit, $stateParams) {
    var vm = this;

    vm.edit = Edit('pkg/rdns/ptr/'+$stateParams.id);
    vm.edit.input = {};
    vm.edit.submit = submit;
    vm.logs = {
      filter: {
        target_type: 'pkg.rdns.ptr',
        target_id: $stateParams.id
      },
    };

    activate();

    //////////

    function activate() {
      vm.edit.getCurrent();
    }

    function submit() {
      vm.edit.patch(vm.edit.getData());
    }
  }
})();
