<html>
   
   <head>
   
      <style type="text/css">
                  
         body, a, table {
         
            font-family: arial;
            font-size: 14px;
            
         }
      
      </style>

      <script language="JavaScript">
               
         function checkStatus() {

            var status_field =   document.getElementById('alloc_status_field');
            
            var username_field = document.getElementById('username');
            
            if (username_field.value.length == 0) {
               status_field.innerHTML = '<span style="color: #f00">A username is required</span>';
               return;
            }
                  
            status_field.innerHTML = '';
            
            abc.location.href = "user_check.php?username=" + username_field.value;
            
         }//checkStatus
         
      </script>
      
   </head>

   <body>
   
      <center>
      <br />
      
      <table border=0 width=90% cellpadding=4 cellspacing=0 style="border: 1px solid #bbb">
      
         <tr><td bgcolor="#dddddd">In order to successfully authenticate to XDMoD using XSEDE credentials, you must:</td></tr>
             
         <tr><td>
           
         <ul>
         <li>Have an XSEDE portal account (if you do not have an account, visit <a href="https://portal.xsede.org/" target="_blank">https://portal.xsede.org</a> to create one)</li>
         <li>Be a member of at least one active allocation</li>
         </ul>
         
         </td></tr>

      </table>

      <br /><br />
      
      <table border=0 width=90% cellpadding=4 cellspacing=0 style="border: 1px solid #bbb">
      
         <tr><td colspan=4 bgcolor="#dddddd">Check your XSEDE allocation status</td></tr>
         
         <tr bgcolor="#eeffdd">
            <td width=20>Username</td>
            <td width=205><input type="text" id="username" style="width: 200px; border: 1px solid #888"></td>
            <td><input type="button" style="border: 1px solid #888" value="Check status" onClick="checkStatus()"></td>
            <td align=right><span id="alloc_status_field"></span></td>
         </tr>
         <tr>
            <td colspan=4><iframe id="abc" name="abc" width=100% height=30 frameborder="0" scrolling="no"></iframe></td>
         </tr>
   
      </table>
      
      </center>
      
   </body>

</html>