(function () {
  'use strict';

  angular
    .module('pkg.rdns')
    .component('ptrListForm', {
      require: {
      },
      bindings: {
        form: '=',
      },
      controller: 'PtrListFormCtrl as ptrListForm',
      transclude: true,
      templateUrl: function(RouteHelpers) {
          "ngInject";
          return RouteHelpers.trusted(
              RouteHelpers.package('rdns').asset(
                  'admin/ptr/list/list.form.html'
              )
          );
      }
    })
    .controller('PtrListFormCtrl', PtrListFormCtrl)
    ;

  /**
   * @ngInject
   */
  function PtrListFormCtrl(_, RouteHelpers, Loader, EventEmitter) {
      var ptrListForm = this;

      var pkg = RouteHelpers.package('rdns');
      var $ptr = pkg.api().all('ptr');

      ptrListForm.ptrs = [{
              ip:'',
              ptr:''
          }];
      ptrListForm.submit = onSubmit;
      ptrListForm.addRow = addRow;
      ptrListForm.loader = Loader();
      ptrListForm.$onInit = init;
      // ptrListForm.form.getData = getData;

      EventEmitter().bindTo(ptrListForm);

      RouteHelpers.loadLang('pkg:rdns:admin:ptr');

      //////////
      function addRow(item) {
          if ((item.ip.length || item.ptr.length)
              && (ptrListForm.ptrs[ptrListForm.ptrs.length-1].ip.length
              || ptrListForm.ptrs[ptrListForm.ptrs.length-1].ptr.length)
          ) {
              ptrListForm.ptrs.push({
                  ip:'',
                  ptr:''
              });
          }
      }

      function onSubmit() {
          var items = _.filter(ptrListForm.ptrs, function(item){
              if (item.ip && item.ptr) {
                  return true;
              }
              return false;
          });

          _.each(items, function(item){
              $ptr.post(item);
          });
          setPtr();
      }

      function setPtr() {
          ptrListForm.ptrs = [{
              ip:'',
              ptr:''
          }];
      }

      function getData() {
          var items = _.filter(ptrListForm.ptrs, function(item){
              if (item.ip && item.ptr) {
                  return true;
              }
              return false;
          });

          setPtr();

          return items;
      }

      function init() {
          ptrListForm.form.getData = getData;
      }
  }
})();
