<html>

   <?php
      
      require_once dirname(__FILE__).'/../../../configuration/linker.php';
      
      $affiliates_image = (xd_utilities\checkForCenterLogo(false)) ? "../images/affiliates_os.png" : "../images/affiliates.png";
      
   ?>
   
   <head>
   
      <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
      <meta content="utf-8" http-equiv="encoding">

      <style type="text/css">
      
         html, body {
            margin:0;
            padding:0;
            height:100%;
            background-color: #eee;
            overflow: hidden;
         }
         
         a {
            color: #00f;
         }
         
         #container {
            min-height:100%;
            position:relative;
         }

         #header {
            
            font-family: arial;
            font-size: 12px;
            background:#ff0;
            padding:0px;
            background-image: url("../images/about_top_banner.png");
            background-repeat: no-repeat;
            height: 41px;
            
         }
         
         #header .links {
         
            float: right;
            margin-top: 12px;
            margin-right: 9px;
            
         }
         
         #body {
         
            padding:7px;
            background-color: #eee;
            padding-bottom:56px;   /* Height of the footer */

         }

         body {
         
            font-family: arial;
            font-size: 12px;
         }
         
         #footer {
            position:absolute;
            bottom:0;
            width:100%;
            height:60px;   /* Height of the footer */
         }

         table {
            font-family: arial;
            font-size: 12px;
         }

         li {
            padding-top: 6px;
         }

         .pub_info {

            /*color: #888;*/
            font-size: 11px;
            margin-top: 7px;
            margin-left: 3px;

         }

      </style>
      
      <!--[if lt IE 7]>
      <style media="screen" type="text/css">
         #container {
            height:100%;
         }
      </style>
      <![endif]-->

   </head>
   
   <body>
   
      <?php
      
         function createPublicationReference($config = array()) {

            $config['details'] = implode('<br />', $config['details']);

            print "<br /><table border=0>
               <tr>
               
                  <td rowspan=2 valign=middle width=65>
                     <img src=\"../images/publication_icon.png\">
                  </td>
                  
                  <td>

                     <div class=\"pub_info\">
                        {$config['details']}
                     </div>

                  </td>
                  
               </tr>
               
               <tr>
               
                  <td>
               
                     <table border=0>
                        <tr>
                           <td><img src=\"../images/report_generator/download_report.png\"></td>
                           <td><a href=\"dl_publication.php?file={$config['file']}\">Download Publication</a></td>
                        </tr>
                     </table>
                                       
                  </td>
                  
               </tr>
            </table>";
       
          
         }//createPublicationReference
         
         // ---------------------------------------------------
  
         if (isset($_REQUEST['disp'])) {

            switch ($_REQUEST['disp']) {
            
               case 'general':
                  
print <<<EOF

         <p style="color: #00c"><b>X</b>SE<b>D</b>E Metrics on Demand (XDMoD) is a comprehensive auditing framework for XSEDE, the follow-on to NSF's TeraGrid program.  XDMoD provides detailed information on resource utilization and performance across all resource providers.
</p>


				<p>To effectively address the needs of the XD community, XDMoD provides an active set of tools and services to monitor the
				cyberinfrastructure including, most importantly, its ability to effectively and as seamlessly as possible meet the research needs of the end user.</p>

			<table width=100% style="border-bottom: 1px solid #bbb; height: 10px; margin-top: -15px"><tr><td></td></tr></table>
				
				<p style="margin-top: 4px">XDMoD is intended for the following members of the XD community:</p>

			<ul>

				<li style="padding-top: 0px">The end user and principal investigator who need detailed feedback to improve throughput and
				facilitate utilization, including the novice users, Gateway users, and the nontraditional users.

				<li>The scientific service developer to deploy new services that improve utilization of existing dynamic infrastructure.

				<li>The funding agency program officer who needs to determine how efficiently and effectively the
				supported programs are meeting the needs of the research community by enabling simulation based
				engineering and science.

				<li>The system administrators who need to identify issues with their site's cluster and network resources.

				<li>The center directors who have the obligation to report results to the funding agency in order to justify
				the cost of hardware and operation of XD resources.

			</ul>
			
			<b>To reference TAS (XDMoD), please refer to <a href="about.php?disp=publications" target="about_content">Publications</a></b>

EOF;
                  break;
                  
               case 'publications':

print "<b>To reference TAS (XDMoD), please refer to the following publications:</b><br />\n";


                  createPublicationReference(array(
                     'file' => '10.1002_cpe.2871',
                     'details' => array(
                        '<span style="font-size: 10px">T. R. Furlani, M. D. Jones, S. M. Gallo, A. E. Bruno, C. Lu, A. Ghadersohi, R. J. Gentner, A. Patra, R. L. DeLeon, G. von Lazewski, L. Wang and A. Zimmerman,</span>',
                        '<b>"Performance Metrics and Auditing Framework Using Applications Kernels for High Performance Computer Systems"</b> ,',
                        '<i>Concurrency and Computation: Practice and Experience.</i> Vol 25, Issue 7, p 918, (2013). DOI:10.1002/cpe.2871'
                     )
                  ));
            
                  break;
                  
               default: 
               
                  print "Unknown display mode";
                  break;
            
            }//switch ($_REQUEST['disp'])

            print "</body></html>";
            exit;
               
         }//if (isset($_REQUEST['disp']))
         
      ?>
      
      
      <div id="container">
      
         <div id="header">
            <div class="header_logo"></div>
            <div class="links">
               <a href="about.php?disp=general" target="about_content">General</a> |
               <a href="about.php?disp=publications" target="about_content">Publications</a>
            </div>
         </div>
      
         <div id="body">

      <center>
		<table width=97% border=0>
		
			<tr><td height=290>

            <iframe name="about_content" src="about.php?disp=general" style="border: 0px solid #bbb" width=100% height=270></iframe>

			</td></tr>
			
		    </table>
		    
         </center>
         
         </div>
      
         <div id="footer"><img src="<?php print $affiliates_image; ?>"></div>
      
      </div>

   </body>

</html>