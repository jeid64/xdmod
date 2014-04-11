<?php

	require_once dirname(__FILE__).'/../configuration/linker.php';
	
	$email_tech_support = xd_utilities\getConfiguration('general', 'tech_support_recipient');
	
?>

<html>
   
   <head>
      
      <title>Access Denied</title>
      
      <style type="text/css">
      
         body {
            font-family: Arial;
            color: #f00;
            background-color: #fffcea; 
         }
         
         b {
            color: #000;
         }
         
      </style>
      
   </head>
   
   <body>
      
      <table width=100%>
         <tr><td align=right><a href="index.php"><img src="gui/images/xdmod_mini.png" border=0></a></td></tr>   
      </table>
      
      <center>
         Access to this resource has been denied.<br><br>
         If you have been sent here by the XDMoD portal, please report this issue to the XDMoD Development team at <b><?php print $email_tech_support; ?></b>
      </center>
   
   </body>
   
</html>