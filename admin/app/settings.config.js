(function () {
  'use strict';

  angular
    .module('pkg.rdns')
    .config(settingsTabConfig);

  /**
   * @ngInject
   */
  function settingsTabConfig($provide) {
    $provide.decorator('SettingsTab', ['$delegate', function ($delegate) {
      var OrigTab = $delegate;

      return function (vm, id, trans, items, parent, parentActualSetting) {
        OrigTab.call(this, vm, id, trans, items, parent, parentActualSetting);

        var tab = this;
        var typeItem = findBySlug(items, 'pkg.rdns.api.type');

        if (!typeItem) {
          return;
        }

        var cloudflareValue = getOptionValue(typeItem.options, 'Cloudflare');

        if (cloudflareValue === null) {
          return;
        }

        var allItems = items.slice();
        var hiddenSlugs = ['pkg.rdns.api.host', 'pkg.rdns.nameservers'];

        applyVisibility(typeItem.value);

        var origOnFieldChanged = tab.onFieldChanged;
        tab.onFieldChanged = function (item) {
          origOnFieldChanged.call(tab, item);

          if (item.slug === 'pkg.rdns.api.type') {
            applyVisibility(item.value);

            // Re-mark form control as dirty after applyVisibility replaces
            // tab.items, since the ng-repeat re-render can reset $dirty state.
            var formElem = tab.form && tab.form[item.id + '.value'];
            if (formElem) {
              formElem.$setDirty();
            }
          }
        };

        function applyVisibility(value) {
          if (value === cloudflareValue) {
            tab.items = allItems.filter(function (item) {
              return hiddenSlugs.indexOf(item.slug) === -1;
            });
          } else {
            tab.items = allItems.slice();
          }
        }
      };

      function findBySlug(items, slug) {
        for (var i = 0; i < items.length; i++) {
          if (items[i].slug === slug) return items[i];
        }

        return null;
      }

      function getOptionValue(options, text) {
        if (!options) return null;

        for (var i = 0; i < options.length; i++) {
          if (options[i].text === text) return options[i].value;
        }

        return null;
      }
    }]);
  }
})();
