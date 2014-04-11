DashboardStore = function(config) {

   var finalConfig = Ext.apply({
      successProperty: 'success'
   }, config);

   DashboardStore.superclass.constructor.call(this, finalConfig);

   this.proxy.on('exception', function(dp, type, action, options, response, arg) {

         var d = Ext.util.JSON.decode(response.responseText);

         if (d.success == false && d.status == 'not_logged_in') {

            Dashboard.modalLoginMessage();

         }
         else {

            CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'An unknown error has occurred.', false);

         }

   }, this);

}

Ext.extend(DashboardStore, Ext.data.JsonStore);

// -------------------------------------------------------------------------

var ServerRequest = function(config) {

   var final_callback = config.callback;

   config.callback = function(options, success, response) {

      if (success) {

         var json = Ext.util.JSON.decode(response.responseText);

         if (json.success == false && json.status == 'not_logged_in')
            Dashboard.modalLoginMessage();
         else
            final_callback(options, success, response);

         return;

      }

      CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'An unknown error has occurred.', false);

   };//config.callback

   var conn = new Ext.data.Connection;
   conn.request(config);

}//ServerRequest

// -------------------------------------------------------------------------

var synchronousServerRequest = function(config) {

   var paramString = new Array();

   for (var i in config)
      paramString.push(i + '=' + config[i]);

   if (window.XMLHttpRequest) {
       AJAX=new XMLHttpRequest();
   }
   else {
       AJAX=new ActiveXObject("Microsoft.XMLHTTP");
   }

   if (AJAX) {

       AJAX.open("POST", 'controllers/controller.php', false);
       AJAX.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
       AJAX.send(paramString.join('&'));

      var json = Ext.util.JSON.decode(AJAX.responseText);

      if (json.success == false && json.status == 'not_logged_in') {
         Dashboard.modalLoginMessage();
         return {server_provided_response: false};
      }

      json.server_provided_response = true;
      return json;

   }//if (AJAX)

   CCR.xdmod.ui.generalMessage('XDMoD Dashboard', 'An unknown error has occurred.', false);
   return {server_provided_response: false};

}//synchronousServerRequest

