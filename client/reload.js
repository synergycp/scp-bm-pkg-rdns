(function () {
  'use strict';

  angular.element('body').append(
    '<script async src=\'http://HOST:3002/browser-sync/browser-sync-client.2.13.0.js\'><\/script>'.replace("HOST", location.hostname)
  );
})();
