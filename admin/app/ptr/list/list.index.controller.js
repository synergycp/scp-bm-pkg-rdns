(function () {
  'use strict';

  angular
    .module('pkg.rdns.ptr.list')
    .controller('PtrIndexCtrl', PtrIndexCtrl)
  ;

  /**
   * @ngInject
   */
  function PtrIndexCtrl(PtrList, ListFilter, ApiUpload, _) {
    var vm = this;

    vm.list = PtrList();
    vm.filters = ListFilter(vm.list);

    vm.create = {
      input: {},
      submit: create,
    };

    vm.logs = {
      filter: {
        target_type: 'pkg.rdns.ptr',
      },
    };

    vm.import = {
      file: null,
      submit: importFile,
    };

    activate();

    ////////////

    function activate() {
      vm.list.load();
    }

    function importFile() {
      ApiUpload.post('pkg/rdns/ptr/zone', vm.import.file, {
        file: vm.import.file,
      }).then(function() {
        vm.list.load();
      });
    }

    function create() {
      var data = vm.create.getData();

      _.each(data, function (item) {
        vm.list
          .create(item)
        ;
      });
    }
  }
})();
