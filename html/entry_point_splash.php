<?php
   
   /*
      XDMoD Portal Entry Point (Public View)
      The Center For Computational Research, University At Buffalo
   */

   require_once dirname(__FILE__).'/../configuration/linker.php';
   
   // =============================================================
   
   function isReferrer($referrer) {
 
      if (isset($_SERVER['HTTP_REFERER'])) {
         
         $pos = strpos($_SERVER['HTTP_REFERER'], $referrer);
      
         if ($pos !== false && $pos == 0) { return true; }
      
      }//if (isset($_SERVER['HTTP_REFERER']))
      
      return false;
     
   }//isReferrer
   
   if (isReferrer('https://go.teragrid.org') || isReferrer('https://portal.xsede.org')) {
   
      // If someone clicks on the 'Cancel' button when consulting the oAuth login UI, it would normally
      // redirect that person to the xdmod main page.  The logic below inhibits this.
         
      header('location: oauth/entrypoint.php');
      exit;
   
   }

   // =============================================================
      
   $page_title = xd_utilities\getConfiguration('general', 'title');
   
   $tech_support_recipient = xd_utilities\getConfiguration('general', 'tech_support_recipient');

   if (!isset($_SESSION['public_session_token'])) {
      $_SESSION['public_session_token'] = 'public-'.microtime(true).'-'.uniqid();
   }
   
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>

   <head lang="en">
   
      <!-- <meta http-equiv="X-UA-Compatible" content="IE=9" /> -->
      <meta charset="utf-8" />

      <?php
      
         $meta_description = "XSEDE Metrics on Demand (XDMoD) is a comprehensive auditing framework for XSEDE, the follow-on to NSF's TeraGrid program.  " .
                             "XDMoD provides detailed information on resource utilization and performance across all resource providers.";
                             
         $meta_keywords = "xdmod, xsede, analytics, metrics on demand, hpc, visualization, statistics, reporting, auditing, nsf, resources, resource providers";
      
      ?>
      
      <meta name="description" content="<?php print $meta_description; ?>" />                                     
      <meta name="keywords" content="<?php print $meta_keywords; ?>"> 
      
      <title><?php print $page_title; ?></title>

      <link rel="shortcut icon" href="gui/icons/favicon_static.ico" />
	
      <?php
         ExtJS::loadSupportScripts('gui/lib');
      ?>
		<script type="text/javascript" src="gui/lib/jquery/jquery.min.js"></script>
        <script type="text/javascript">
        jQuery.noConflict();
        </script>
       
      <link rel="stylesheet" type="text/css" href="gui/css/viewer.css">
      <link rel="stylesheet" type="text/css" href="gui/css/common.css" />            
      <link rel="stylesheet" type="text/css" href="gui/css/LoginPrompt.css" /> 
      <link rel="stylesheet" type="text/css" href="gui/css/MultiSelect.css"/>
      <link rel="stylesheet" type="text/css" href="gui/lib/extjs/examples/ux/css/Spinner.css" /> 
      
      <script type="text/javascript" src="gui/lib/debug.js"></script>
      <script type="text/javascript" src="gui/lib/datadumper.js"></script>
      
      <!-- Globals -->
      <script type="text/javascript" src="gui/js/globals.js"></script>
      <script type="text/javascript" src="gui/js/StringExtensions.js"></script>
      
      <!-- Plugins -->
      <script type="text/javascript" src="gui/js/plugins/ContextSensitiveHelper.js"></script>

      <!-- Libraries -->
      <script type="text/javascript" src="gui/js/libraries/utilities.js"></script> 
      
      <script type="text/javascript" src="gui/js/SessionManager.js"></script>  
      
      <!-- RESTProxy -->
      <script type="text/javascript" src="gui/js/RESTProxy.js"></script>
      <script type="text/javascript">
         XDMoD.REST.token = '<?php print $_SESSION['public_session_token']; ?>';
      </script>


      
      <!-- Support Extensions for 'User Like Me' -->

      <script type="text/javascript" src="gui/lib/extjs/examples/ux/DataViewTransition.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/Reorderer.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/ToolbarReorderer.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/SearchField.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/statusbar/StatusBar.js"> </script>
      <script type="text/javascript" src="gui/js/MultiSelect.js"></script>
      <script type="text/javascript" src="gui/js/ItemSelector.js"></script>

      <script type="text/javascript" src="gui/lib/NumberFormat.js"></script> 
      <script type="text/javascript" src="gui/js/multiline-tree-nodes.js"></script>
      
      <script type="text/javascript" src="gui/js/MessageWindow.js"></script>
      
      <script type="text/javascript" src="gui/js/CCR.js"></script>
      <script type="text/javascript" src="gui/js/RESTDataProxy.js"></script> 

      <script type="text/javascript" src="gui/js/CustomHttpProxy.js"></script>
      <script type="text/javascript" src="gui/js/printer/Printer-all.js"></script>

      <script type="text/javascript" src="gui/js/TGUserDropDown.js"></script>

      <script language="JavaScript" src="gui/js/login.js.php"></script> 
            
      <script type="text/javascript" src="gui/js/CheckColumn.js"></script> 
      <script type="text/javascript" src="gui/js/LimitedField.js"></script> 

      <script type="text/javascript" src="gui/js/ContainerMask.js"></script> 

      <script type='text/javascript'>

      <?php
         
         print "CCR.xdmod.ui.username = '__public__';\n";
         
         print "CCR.xdmod.support_email = '$tech_support_recipient';\n";
         
         print "CCR.xdmod.version = '".xd_versioning\getPortalVersion()."';\n";
         print "CCR.xdmod.short_version = '".xd_versioning\getPortalVersion(true)."';\n";
		             
         print "CCR.xdmod.publicUser = true;\n";
         
         $user = XDUser::getPublicUser();
		 
         print "CCR.xdmod.ui.roleCategories = {$user->getRoleCategories()};\n";
         print "CCR.xdmod.ui.enabledRealms = '".DATA_REALMS."';\n";
         print "CCR.xdmod.ui.disabledMenus = ".json_encode($user->getDisabledMenus(explode(',',DATA_REALMS))).";\n";
         print "CCR.xdmod.ui.minMaxDates = {$user->getActiveRole()->getMinMaxDates()};\n";

         print "CCR.xdmod.logged_in = false;\n";
         print "CCR.xdmod.use_captcha = ".(xd_utilities\getConfiguration('mailer', 'captcha_private_key') !== '' ? 'true' : 'false').";\n";
                  
      ?>
         
      </script>

      <!-- =========================== -->
   
      <script type="text/javascript" src="gui/js/CustomCheckItem.js"></script>
      <script type="text/javascript" src="gui/js/CustomJsonStore.js"></script> 
      <script type="text/javascript" src="gui/js/plugins/CollapsedPanelTitlePlugin.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportEntryTypeMenu.js"></script>
      <script type="text/javascript" src="js_classes/DateUtilities.js"></script>
   
      <script type="text/javascript" src="gui/js/Portal.js"></script>
      <script type="text/javascript" src="gui/js/PortalColumn.js"></script>
      <script type="text/javascript" src="gui/js/Portlet.js"></script>
      
      <script type="text/javascript" src="gui/js/RESTTree.js"></script>
      <script type="text/javascript" src="gui/js/BufferView.js"></script>
      <script type="text/javascript" src="gui/js/Spinner.js"></script>
      <script type="text/javascript" src="gui/js/SpinnerField.js"></script>
      <script type="text/javascript" src="gui/js/CustomCheckItem.js"></script>
      <script type="text/javascript" src="gui/js/CustomDateField.js"></script>	
      <script type="text/javascript" src="gui/js/CustomSplitButton.js"></script>	
      <script type="text/javascript" src="gui/js/CustomRowNumberer.js"></script>	
      <script type="text/javascript" src="gui/js/CustomPagingToolbar.js"></script>
      <script type="text/javascript" src="gui/js/DynamicGridPanel.js"></script>	
      <script type="text/javascript" src="gui/js/DurationToolbar.js"></script>
      <script type="text/javascript" src="gui/js/ChartConfigMenu.js"></script>
      <script type="text/javascript" src="gui/js/ChartToolbar.js"></script>
      <script type="text/javascript" src="gui/js/DrillDownMenu.js"></script>
   
      <script type="text/javascript" src="gui/js/CustomSearch.js"></script>
   
      <script type="text/javascript" src="gui/js/ChartDragDrop.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/DataView-more.js"></script>

      <script type="text/javascript" src="gui/lib/highcharts/highcharts.src.js"></script>
      <script type="text/javascript" src="gui/lib/highcharts/highcharts-more.js"></script>
      <script type="text/javascript" src="gui/lib/highcharts/errorbars.src.js"></script>
          
      <script type="text/javascript" src="gui/lib/highcharts/modules/exporting.src.js"></script>
      <script type="text/javascript" src="gui/js/HighChartPanel.js"></script>       
      <script type="text/javascript" src="gui/js/ChartFilterSelector.js"></script>
      
      <script type="text/javascript" src="gui/js/PortalModule.js"></script>
      
      <script type="text/javascript" src="gui/js/CaptchaField.js"></script>
      <script type="text/javascript" src="gui/js/SignUpDialog.js"></script>
      <script type="text/javascript" src="gui/js/ContactDialog.js"></script>
            
      <script type="text/javascript" src="gui/js/modules/Summary.js"></script>
      <script type="text/javascript" src="gui/js/modules/Usage.js"></script>

      <?php
         xd_utilities\checkForCenterLogo();
      ?>
         
      <script type="text/javascript" src="gui/js/ViewerSplash.js"></script>
      
      <?php
         require_once dirname(__FILE__).'/gaq.php';
      ?>
	  
      <script type="text/javascript">Ext.onReady(xdmodviewer.init, xdmodviewer);</script>

   </head>

   <body> 

      <!-- Fields required for history management -->
      <form id="history-form" class="x-hidden">
         <input type="hidden" id="x-history-field" />
         <iframe id="x-history-frame"></iframe>
      </form>

      <!-- For caching calls (e.g. goToChart(...), made when an image thumbnail is clicked) -->
      <form name="function_call_cacher" class="x-hidden" method="POST" action="index.php">
         <input type="hidden" id="cached_call" name="cached_call" value="">
      </form>
      
      <div class="x-hidden">
         <iframe name="pdf_target_frame"></iframe>
      </div>
      
      <div id="viewer"> </div>

      <noscript>
         <?php xd_web_message\displayMessage('XDMoD requires JavaScript, which is currently disabled in your browser.'); ?>
      </noscript>
      
      <br /><br /><br /><br /><br />

   </body>

</html>