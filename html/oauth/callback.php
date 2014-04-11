<?php

   session_start();

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
      
   </head>

   <body>
   
      <center>
      
         <table border=0 width=100% height=100%>
         
         <tr><td colspan=2 align="center">
         <br><br>

<?php

   require_once dirname(__FILE__).'/../../configuration/linker.php';
   require_once dirname(__FILE__).'/../../libraries/oauth.php';
 
   $token =    (isset($_REQUEST['oauth_token']))    ? $_REQUEST['oauth_token']    : '';
   $verifier = (isset($_REQUEST['oauth_verifier'])) ? $_REQUEST['oauth_verifier'] : '';
 
   //$certificate = generateCert(1);
   
   $certificate = OAuth::acquire_certificate($verifier, $token);

   // ========================================================
   
   try {
   
      list($username, $cert_data) = OAuth::process_certificate_response($certificate);
   
      if (isset($cert_data['subject']) && isset($cert_data['subject']['CN'])) {
 
         // At this point, a valid username and CN (from the certificate) can be used
         
         $formal_name = $cert_data['subject']['CN'];
         
         $xsede_username = $username.';'.$formal_name;
         
         if (XDUser::XSEDEUserExists($xsede_username) == false) {
            
            // Create the new account data on our backend
            
            $user = XDUser::initializeXSEDEUser($xsede_username);

         }
         else {
         
            $user = XDUser::deriveUserFromXSEDEUser($xsede_username);
         
         }
         
         if ($user->getAccountStatus() == INACTIVE) {
            throw new Exception('Your access to XDMoD has been disabled');
         }
         
         $token = XDSessionManager::recordLogin($user);
               
         print "Welcome, $formal_name<br /><br />";    
         print '<img src="../gui/images/progbar.gif"><br />';     
         print '<span class="login_message">Logging you into XDMoD</span><br />';     
         print '<script language="JavaScript">window.opener.postXSEDELoginProcedure(self, "'.$formal_name.'", "'.$token.'");</script>';
             
      }
      else {
      
         throw new Exception('There was a problem processing your XSEDE login');
      
      }
      
   }
   catch(Exception $e) {
   
      //session_destroy();
      generateErrorMessage($e->getMessage());
   
   }
   
   // ========================================================
 
   function generateErrorMessage($message) {
   
      print '<span style="color: #f00">'.$message.'</span>';   
      
      print '<br><br><a href="entrypoint.php">Try logging in again</a><br><br>';
      
   }//generateErrorMessage

   // ========================================================
   
function generateCert($index = 1) {

$dummy_certs = array();

$dummy_certs[] = <<<EOF
username=rgentner
-----BEGIN CERTIFICATE-----
MIIEJTCCAw2gAwIBAgIDFqZ4MA0GCSqGSIb3DQEBBQUAMHsxCzAJBgNVBAYTAlVTMTgwNgYDVQQK
Ey9OYXRpb25hbCBDZW50ZXIgZm9yIFN1cGVyY29tcHV0aW5nIEFwcGxpY2F0aW9uczEgMB4GA1UE
CxMXQ2VydGlmaWNhdGUgQXV0aG9yaXRpZXMxEDAOBgNVBAMTB015UHJveHkwHhcNMTExMjE1MTk1
ODQwWhcNMTExMjI2MjAwMzQwWjBeMQswCQYDVQQGEwJVUzE4MDYGA1UEChMvTmF0aW9uYWwgQ2Vu
dGVyIGZvciBTdXBlcmNvbXB1dGluZyBBcHBsaWNhdGlvbnMxFTATBgNVBAMTDFJ5YW4gR2VudG5l
cjCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALcD8gtlgU7flhQXvTCXFTYjFZbGXHNw
KLFatgYp1G3Y/IVxrKH61hj33GBzUuNT+z1inbezAMBls0GHHhMaXPJiQC04ToiE2bcNuobwVha8
JaPQpocMQCqnZcaPljiGtTAN9hT9xX0xyHq/d7smd3q0+tHVOXZZ+6Ary3N1X1kExPFpi80w8y7G
ShFlzmV2OnMh5h0ESt9Op4Lo0d0jajtFH6MupdQKBhByh2eegHOiN9UMgSk8YoHDqDqfOG8wn+fu
mPsPuAgP3de5LxDMNOgIX8QQ3K2sU7v34K5p87v3+8gkc21fFjZPUOf5wZtKhqlDH+1vUsJr2hhq
mkInSNMCAwEAAaOBzjCByzAOBgNVHQ8BAf8EBAMCBLAwHQYDVR0OBBYEFL5T+REtcZDjBn1UbKdy
JT7pX2rEMB8GA1UdIwQYMBaAFNf8pQJ2OvYT+iuh4OZQNccjx3tRMAwGA1UdEwEB/wQCMAAwNAYD
VR0gBC0wKzAMBgorBgEEAaQ+ZAIFMAwGCiqGSIb3TAUCAgMwDQYLKoZIhvdMBQIDAgEwNQYDVR0f
BC4wLDAqoCigJoYkaHR0cDovL2NhLm5jc2EudWl1Yy5lZHUvZjJlODlmZTMuY3JsMA0GCSqGSIb3
DQEBBQUAA4IBAQC2oNNPLwVemx7w1bDjsOjfj4mDhx1i8uiuafRpNAgO9ci+IR4ec6t+mT7lFLXP
ozHvXnkRZRnteY1sFccNJB5bxbftLYRrDGooAmfFnqgn1T7333Vil8DduIGBwUSiozO4c46aOeM/
zhvoD5LVpMQTLs/SfHGN2FWzZ7nWc1EknwQ+Z/h56sz3OwBCEKCa3Jp+VTj2EMDIbV7WD8/nkWfC
isAdrDUNgwtuHMZmdZLp+T3sC0nbbq5B6r2mvpX8weDyd6PpGpj3WlLxmg2HLx65+Kuqfd3lLUN+
dTrKH9FVeWlxQ9FaFVrNaLyCy9+5MhdpK1vrv/vsyJiZ03+Bv1d5
-----END CERTIFICATE-----
EOF;

$dummy_certs[] = <<<EOF
username=xdtas
-----BEGIN CERTIFICATE-----
MIIEJDCCAwygAwIBAgIDFqcGMA0GCSqGSIb3DQEBBQUAMHsxCzAJBgNVBAYTAlVTMTgwNgYDVQQK
Ey9OYXRpb25hbCBDZW50ZXIgZm9yIFN1cGVyY29tcHV0aW5nIEFwcGxpY2F0aW9uczEgMB4GA1UE
CxMXQ2VydGlmaWNhdGUgQXV0aG9yaXRpZXMxEDAOBgNVBAMTB015UHJveHkwHhcNMTExMjE1MjA1
NTMxWhcNMTExMjI2MjEwMDMxWjBdMQswCQYDVQQGEwJVUzE4MDYGA1UEChMvTmF0aW9uYWwgQ2Vu
dGVyIGZvciBTdXBlcmNvbXB1dGluZyBBcHBsaWNhdGlvbnMxFDASBgNVBAMTC1N0ZXZlIEdhbGxv
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtwPyC2WBTt+WFBe9MJcVNiMVlsZcc3Ao
sVq2BinUbdj8hXGsofrWGPfcYHNS41P7PWKdt7MAwGWzQYceExpc8mJALThOiITZtw26hvBWFrwl
o9CmhwxAKqdlxo+WOIa1MA32FP3FfTHIer93uyZ3erT60dU5dln7oCvLc3VfWQTE8WmLzTDzLsZK
EWXOZXY6cyHmHQRK306ngujR3SNqO0Ufoy6l1AoGEHKHZ56Ac6I31QyBKTxigcOoOp84bzCf5+6Y
+w+4CA/d17kvEMw06AhfxBDcraxTu/fgrmnzu/f7yCRzbV8WNk9Q5/nBm0qGqUMf7W9SwmvaGGqa
QidI0wIDAQABo4HOMIHLMA4GA1UdDwEB/wQEAwIEsDAdBgNVHQ4EFgQUvlP5ES1xkOMGfVRsp3Il
PulfasQwHwYDVR0jBBgwFoAU1/ylAnY69hP6K6Hg5lA1xyPHe1EwDAYDVR0TAQH/BAIwADA0BgNV
HSAELTArMAwGCisGAQQBpD5kAgUwDAYKKoZIhvdMBQICAzANBgsqhkiG90wFAgMCATA1BgNVHR8E
LjAsMCqgKKAmhiRodHRwOi8vY2EubmNzYS51aXVjLmVkdS9mMmU4OWZlMy5jcmwwDQYJKoZIhvcN
AQEFBQADggEBAJL0pOh6cytZsUjkQ5nabgCheFNr6WKAtNDq99T1WJyn5djiDQO+OYG9JofmQ2Jb
zAtTiGC2vnM0TSEm9ml7U6x9Cr7Nk9Uy/J/VZ9U5wCOgHfh/jHnNzIBE/VWSe3NL7057B2zs1ama
WTC10yXivc/oNKo6yndTJcPMZXM8uQy7JOpgCQ2/qW/++J4MYOgiwQc7WUEY0oRZg0KJl+VGqZlu
NlBTr0fVzxUpZJ8XLIye5ISTR34nlWM+OxQcftho6nj8QbgNCsV78jmL7SkXPbJcSuNHH8/GO3gs
jWouT/m6Mvg9PTtQZdh0QhuBTZewsp4wnp/Lx8Uq35WU9eaOHZ4=
-----END CERTIFICATE-----
EOF;

   return $dummy_certs[$index];

}//generateCert


?>

</td></tr>

<tr>
   <td width=50% align=left valign="bottom"><img src="../gui/images/oauth_lock_icon.png"></td>
   <td width=50% align=right valign="bottom"><img src="../gui/images/oauth_xsede_logo.png"></td>
</tr>


      

</table>

      </center>
      
   </body>

</html>