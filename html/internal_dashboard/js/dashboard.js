var current_users;

var processLDIFExport = function (config) {
   (function () {
      var json = synchronousServerRequest({
         operation: 'enum_existing_users',
         group_filter: config.group_filter,
         role_filter: config.role_filter,
         context_filter: config.context_filter
      });

      if (!json.server_provided_response) { return; }

      if (json.success && json.count > 0) {
         location.href = 'controllers/controller.php?operation=generate_ldif' +
            '&group_filter=' + config.group_filter +
            '&role_filter=' + config.role_filter +
            '&context_filter=' + config.context_filter;
         return;
      } else {
         CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'No accounts would be present in the LDIF you are attempting to export.', false);
         return;
      }
   }).defer(200);
};//processLDIFExport

// ------------------------------------------------

var actionLogout = function () {
   ServerRequest({
      url: 'controllers/controller.php',
      params: {operation: 'logout'},
      method: 'POST',
      callback: function(options, success, response) {
         if (success) {
            location.href = 'index.php';
         } else {
            CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'There was a problem connecting to the dashboard service provider.', false);
        }
      }//callback
   });//ServerRequest
};//actionLogout

// ------------------------------------------------

Ext.onReady(function () {
    var factory = new XDMoD.Dashboard.Factory();

    factory.load(function (items) {
        new XDMoD.Dashboard.Viewport({ items: items });
    });
}, window, true);

