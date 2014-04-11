<?php

   if (isset($_REQUEST['xd_user_formal_name'])) {

      $formal_name = $_REQUEST['xd_user_formal_name'];

?>

<html>

   <head>

      <style type="text/css">


         body, table {

            font-family: arial;
            font-size: 18px;
            color: #00f;
            background-color: #e8e8e8;

         }

         .login_message {

            color: #55f;
            font-size: 14px;

         }

      </style>

      <script language="JavaScript">

         function loadPortal() {

            parent.location.href = '../../index.php';

         }//loadPortal

         // ------------------------

         function initLoad() {

            setTimeout("loadPortal()", 2000);

         }//loadPortal

      </script>

   </head>

   <body onload="initLoad()">

      <center>

         <table border=0 width=100% height=100%>

         <tr><td colspan=2 align="center">

         Welcome, <?php print $formal_name; ?><br /><br />
         <img src="../../gui/images/progbar.gif"><br />
         <span class="login_message">Logging you into XDMoD</span><br />

         </td></tr>

         </table>

      </center>

   </body>

</html>


<?php

   exit;

   }

?>


<html>

   <head>

      <style type="text/css">

         body, td {

            font-family: Arial;
            font-size: 11px;
            background-color: #e8e8e8;
            overflow: hidden;

         }

         .xdmod_login td {
            background-color: #eee;
         }

         .signup_16 {
            background-image: url('../images/signup_16.png') !important;
         }

         .form_container {

            border: 1px solid #bbb;
            background-color: #eee;

         }

      </style>

      <!--[if IE]>
      <style type="text/css">

         .centered_content {
            /*padding-top: 22px;*/
         }

         .ie_container {
            padding: 5px;
         }

         .ie_container_xsede {
            padding: 3px;
         }

      </style>
      <![endif]-->

      <?php

         require_once dirname(__FILE__).'/../../../configuration/linker.php';
         ExtJS::loadSupportScripts('../lib');

      ?>

      <script type="text/javascript" src="../js/CCR.js"></script>
      <script type="text/javascript" src="../js/RESTProxy.js"></script>

      <script language="JavaScript">

         // ------------------------------------------------

         function postXSEDELoginProcedure(wChild, formalName) {

         }//postXSEDELoginProcedure

         // ------------------------------------------------

         function initPage() {

            //document.getElementById('loginFieldUsername').focus();

         }//initPage

         // ------------------------------------------------

         var txtEmailAddress;
         var txtLoginUsername, txtLoginPassword;

         function processReset() {

            var objParams = {

               operation: 'pass_reset',
               email : txtEmailAddress.getValue()

            };

            var conn = new Ext.data.Connection;
            conn.request({

               url: '../../controllers/user_auth.php',
               params: objParams,
               method: 'POST',
               callback: function(options, success, response) {

                  if (success) {

                     var json = Ext.util.JSON.decode(response.responseText);

                     switch(json.status){

                        case 'invalid_email_address':

                           parent.presentLoginPromptOverlay('A valid e-mail address must be specified.', false);

                           break;

                        case 'no_user_mapping':

                           parent.presentLoginPromptOverlay('No XDMoD user could be associated with this e-mail address.', false);

                           break;

                        case 'multiple_accounts_mapped':

                           parent.presentLoginPromptOverlay('Multiple XDMoD accounts are associated with this e-mail address.', false);

                           break;

                        case 'success':

                           parent.presentLoginPromptOverlay('Password reset instructions have been sent to this<br />e-mail address.', true);

                           break;

                     }//switch(json.status)

                  }
                  else {
                     parent.presentLoginPromptOverlay('There was a problem connecting to the portal service provider.', false);
                  }

                  txtEmailAddress.focus();

               }//callback

            });

         }//processReset

         // ------------------------------------------------

         var presentOverlay = function(status, message, customdelay, cb) {

            var delay = customdelay ? customdelay : 2000;
            var section;

            var cStatus = (status == true) ? '#080' : '#f00';

            parent.getEl().mask('<div class="overlay_message" style="color:' + cStatus + '">' + message + '</div>');

         }//presentOverlay

         // ------------------------------------------------

         function switchView(viewToPresent) {

            var oldView = 'panel_account_reset';
            var focusField = txtLoginUsername;

            var header = document.getElementById('right_section_header');
            var subheader = document.getElementById('right_section_subheader');

            if (viewToPresent == 'panel_account_reset') {

               focusField = txtEmailAddress;
               oldView = 'panel_xdmod_login';

               header.innerHTML = 'Trouble Logging In?';
               subheader.innerHTML = 'Reset your password';

            }
            else {

               header.innerHTML = 'Have an XDMoD account?';
               subheader.innerHTML = 'Sign in with your local XDMoD account';

            }

            document.getElementById(oldView).style.visibility = 'hidden';
            document.getElementById(viewToPresent).style.visibility = 'visible';

            focusField.focus();

         }//switchView

         // ------------------------------------------------



         function processLogin() {

            if (txtLoginUsername.getValue().length == 0) {

               parent.presentLoginPromptOverlay('You must specify a username', false, function(){
                  txtLoginUsername.focus();
               });

               return;

            }

            if (txtLoginPassword.getValue().length == 0) {

               parent.presentLoginPromptOverlay('You must specify a password', false, function(){
                  txtLoginPassword.focus();
               });

               return;

            }

            var restArgs = {
               'username' : txtLoginUsername.getValue(),
               'password' : encodeURIComponent(txtLoginPassword.getValue())
            };

            XDMoD.REST.Call({

               action: 'authentication/utilities/login',
               arguments: restArgs,

               callback: function(responseData) {

                  if (responseData.success) {

                     parent.presentLoginPromptOverlay ('Welcome, ' + responseData.results.name, true);

                     parent.location.href = '../../index.php';

                  }
                  else{

                     responseData.message = responseData.message.replace(
                        parent.CCR.xdmod.support_email,
                        '<br /><a href="mailto:' + parent.CCR.xdmod.support_email + '?subject=Problem Logging In">' + parent.CCR.xdmod.support_email + '</a>'
                     );

                     responseData.message = responseData.message.replace(
                        'please contact',
                        '<br />please contact'
                     );

                     parent.presentLoginPromptOverlay (

                        '<center>' + responseData.message + '</center>',
                        false,

                        function(){

                           txtLoginPassword.focus(true);

                        },

                        4000

                     );//presentOverlay

                  }

               }

            });

         }//processLogin

         // ------------------------------------------------

         function presentSignUp() {

            parent.presentSignUpViaLoginPrompt();

         }//presentSignUp

         // ------------------------------------------------

         Ext.onReady(function() {

            new Ext.Button({

               text: 'Sign In',
               width: 80,
               handler: function() {
                  processLogin();
               },
               renderTo: 'btn_sign_in'

            });


            txtLoginUsername = new Ext.form.TextField({
               renderTo: 'txt_login_username',
               width: 184,
               enableKeyEvents: true,
               listeners: {
                  'keydown': function (a,e) {
                     if (e.getCharCode() == 13) txtLoginPassword.focus();
                  }
               }
            });

            txtLoginUsername.focus();

            txtLoginPassword = new Ext.form.TextField({
               inputType: 'password',
               renderTo: 'txt_login_password',
               width: 184,
               enableKeyEvents: true,
               listeners: {
                  'keydown': function (a,e) {
                     if (e.getCharCode() == 13) processLogin();
                  }
               }
            });

            new Ext.Button({

               text: 'Sign Up',
               width: 80,
               handler: function() {
                  presentSignUp();
               },

               iconCls: 'signup_16',
               renderTo: 'btn_sign_up'

            });


            txtEmailAddress = new Ext.form.TextField({
               renderTo: 'txt_email_address',
               width:  184,
               enableKeyEvents: true,
               listeners: {
                  'keydown': function (a,e) {
                     if (e.getCharCode() == 13) processReset();
                  }
               }
            });

            new Ext.Button({

               text: 'Send E-Mail',
               width: 80,
               handler: function() {
                  processReset();
               },
               renderTo: 'btn_send_email'

            });

            var xsede_button_margin = '-2px';

         });//Ext.onReady

      </script>

   </head>

   <body onload="initPage()">

      <table border=0 width=100% height=100%><tr><td valign="middle">

      <div class="centered_content" style="margin-top: -20px">
      <table border=0 width=100% height=90%><tr>

         <td width=240 style="padding: 2px">

            <table border=0 width=100% height=100%>

               <tr><td height=10>

                  <span id="right_section_header" style="color: #000; font-size: 13px; font-weight: bold">Have an XDMoD account?</span><br />
                  <span id="right_section_subheader" style="color: #666">Sign in with your local XDMoD account</span>

               </td></tr>

               <tr><td align=center height=50 style="padding-top: 7px">


                  <div id="panel_xdmod_login">

                     <div class="form_container ie_container" style="width: 200px">
                     <table class="xdmod_login" width=200 border=0 style="padding: 5px">

                        <tr><td>Username</td></tr>
                        <tr><td><div id="txt_login_username"></div></td></tr>

                        <tr><td>Password</td></tr>
                        <tr><td><div id="txt_login_password"></div></td></tr>

                        <tr><td colspan=2 align=right style="padding-top: 3px"><div id="btn_sign_in"></div></td></tr>

                     </table>
                     </div>

                     <div style="padding-top: 5px">
                     <table border=0>
                        <tr><td style="padding-right: 4px">Don't have an account?</td><td><div id="btn_sign_up"></div></td></tr>
                        <tr><td style="padding-right: 4px; padding-top: 9px" colspan=2>Trouble logging in? <a href="javascript:void(0)" onClick="switchView('panel_account_reset')">Click here</a></td></tr>
                     </table>
                     </div>

                  </div>

                  <!-- ======================================= -->

                  <div id="panel_account_reset" style="position: absolute; top: 66px; left: 6px; visibility: hidden">

                     <table border=0 width=230>

                        <tr><td style="padding-left: 7px">

                              Supply the e-mail address associated with<br />
                              your account. You will be emailed a link<br />
                              that will allow you to reset your password.<br /><br />

                        </td></tr>

                        <tr><td align=center>

                           <div class="form_container ie_container" style="width: 200px">
                           <table class="xdmod_login" width=200 border=0 style="padding: 5px">

                              <tr><td>E-Mail Address</td></tr>
                              <tr><td><div id="txt_email_address"></div></td></tr>

                              <tr><td colspan=2 align=right style="padding-top: 3px"><div id="btn_send_email"></div></td></tr>

                           </table>
                           </div>

                        </td></tr>

                        <tr><td style="padding-top: 10px; padding-left: 7px">
                           <a href="javascript:void(0)" onClick="switchView('panel_xdmod_login')">Return to login</a>
                        </td></tr>

                     </table>

                  </div>


               </td></tr>

            </table>

         </td>

      </tr></table>
      </div>

      </td></tr></table>

   </body>

</html>
