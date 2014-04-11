<?php

   /*
      XDMoD Portal Entry Point
      The Center For Computational Research, University At Buffalo
   */

   @session_start();
   
   if (!isset($_SESSION['xdUser'])){
      include 'access_denied.php';
      exit;
   }
   
   require_once dirname(__FILE__).'/../configuration/linker.php';
   $page_title = xd_utilities\getConfiguration('general', 'title');

   // The (initial) token used for REST calls
   $token = (isset($_SESSION['session_token'])) ? $_SESSION['session_token'] : '';
   
   try {
   
      $user = \xd_security\getLoggedInUser();
   
   }
   catch(Exception $e) {
   
      xd_web_message\displayMessage('There was a problem initializing your account.', $e->getMessage(), true);
      exit;
      
   }
   
   if (!isset($user) || !isset($_SESSION['session_token'])) {
   
      // There is an issue with the account (most likely deleted while the user was logged in, and the user refreshed the entire site)
      session_destroy();
      header("Location: index.php");
      exit;
   
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
      
      <script type="text/javascript" src="gui/lib/jquery.spotlight.js"></script>
      <link rel="stylesheet" type="text/css" href="gui/css/viewer.css">
      
      <script type="text/javascript" src="gui/lib/debug.js"></script>
      <script type="text/javascript" src="gui/lib/datadumper.js"></script>
      
      <script type="text/javascript" src="gui/js/RowExpander.js"></script>
      
      <!-- Non-GUI JS Class Definitions -->
      <script type="text/javascript" src="js_classes/DateUtilities.js"></script>
      <script type="text/javascript" src="js_classes/StringUtilities.js"></script>
            
      <!-- Globals -->
      <script type="text/javascript" src="gui/js/globals.js"></script>
      <script type="text/javascript" src="gui/js/StringExtensions.js"></script>
      <!-- Plugins -->
      <script type="text/javascript" src="gui/js/plugins/ContextSensitiveHelper.js"></script>
      <script type="text/javascript" src="gui/js/plugins/CollapsedPanelTitlePlugin.js"></script>
      
      <!-- Libraries -->
      <script type="text/javascript" src="gui/js/libraries/utilities.js"></script> 
      
      <script type="text/javascript" src="gui/js/SessionManager.js"></script>  
      
      <!-- RESTProxy -->

      <script type="text/javascript" src="gui/js/RESTProxy.js"></script>
      <script type="text/javascript">
         XDMoD.REST.token = '<?php print $token; ?>';
      </script>

      <link rel="stylesheet" type="text/css" href="gui/css/MultiSelect.css"/>
      <link rel="stylesheet" type="text/css" href="gui/lib/extjs/examples/ux/css/Spinner.css" />
      <link rel="stylesheet" type="text/css" href="gui/lib/extjs/examples/ux/css/LockingGridView.css" />
      <!-- Support Extensions for 'User Like Me' -->

      <script type="text/javascript" src="gui/lib/extjs/examples/ux/DataViewTransition.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/Reorderer.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/ToolbarReorderer.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/SearchField.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/statusbar/StatusBar.js"> </script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/LockingGridView.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/SlidingPager.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/ProgressBarPager.js"></script>
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
      <script type="text/javascript" src="gui/js/LoginPrompt.js"></script>

      <script type="text/javascript" src="gui/js/CheckColumn.js"></script> 
      <script type="text/javascript" src="gui/js/LimitedField.js"></script> 

      <script type="text/javascript" src="gui/js/ContainerMask.js"></script> 
	   <script type="text/javascript" src="gui/js/ContainerBodyMask.js"></script> 
      
      <link rel="stylesheet" type="text/css" href="gui/css/common.css" />    
      
      <?php
        
         $manager = $user->isManager() ? 'true' : 'false';
         $developer = $user->isDeveloper() ? 'true' : 'false';
         
         $primary_center_director = (
               ($user->getActiveRole()->getIdentifier() == ROLE_ID_CENTER_DIRECTOR) && 
               true //($user->getPromoter(ROLE_ID_CENTER_DIRECTOR, $user->getActiveRole()->getActiveCenter()) == -1)
         ) ? 'true' : 'false';
                  
      ?>

      <script type='text/javascript'>

         <?php
         			
            $tech_support_recipient = xd_utilities\getConfiguration('general', 'tech_support_recipient');
            print "CCR.xdmod.tech_support_recipient = '$tech_support_recipient';\n";
          
            print "CCR.xdmod.version = '".xd_versioning\getPortalVersion()."';\n";
            print "CCR.xdmod.short_version = '".xd_versioning\getPortalVersion(true)."';\n";

            print "CCR.xdmod.ui.username = '{$user->getUsername()}';\n";
            print "CCR.xdmod.ui.fullName = '{$user->getFormalName()}';\n";   
            
            print "CCR.xdmod.ui.mappedPID = '{$user->getPersonID(TRUE)}';\n";
            
            $obj_warehouse = new XDWarehouse();
            print "CCR.xdmod.ui.mappedPName = '{$obj_warehouse->resolveName($user->getPersonID(TRUE))}';\n";  
                        
            print "CCR.xdmod.ui.isManager = $manager;\n";
            print "CCR.xdmod.ui.isDeveloper = $developer;\n";
            print "CCR.xdmod.ui.isCenterDirector = $primary_center_director;\n";
            
            print "CCR.xdmod.ui.active_role_label = '{$user->getActiveRole()->getFormalName()}';\n";
                        
            print "CCR.xdmod.ui.roleCategories = {$user->getRoleCategories()};\n";		
			
            print "CCR.xdmod.ui.enabledRealms = '".DATA_REALMS."';\n";
			
            print "CCR.xdmod.ui.disabledMenus = ".json_encode($user->getDisabledMenus(explode(',',DATA_REALMS))).";\n";
			
            print "CCR.xdmod.ui.allRoles = ".json_encode($user->enumAllAvailableRoles())."\n";
            
            print "CCR.xdmod.ui.activeRole = '".$user->getActiveRole()->getIdentifier(true)."';\n";
            
            $university_name = ($user->getActiveRole()->getIdentifier() == ROLE_ID_CAMPUS_CHAMPION) ? $user->getActiveRole()->getUniversityName() : 'XSEDE';
            
            print "CCR.xdmod.ui.activeOrganization = '$university_name';\n";
            
            print "CCR.xdmod.ui.minMaxDates = {$user->getActiveRole()->getMinMaxDates()};\n";
            
            print "CCR.xdmod.org_abbrev = ".json_encode(ORGANIZATION_NAME_ABBREV).";\n";
            
            print "CCR.xdmod.logged_in = true;\n";
            print "CCR.xdmod.use_captcha = ".(xd_utilities\getConfiguration('mailer', 'captcha_private_key') !== '' ? 'true' : 'false').";\n";
            
         ?>

      </script>

      <script type="text/javascript" src="gui/js/RoleSelector.js"></script>
      
      <!-- Profile Editor -->

      <link rel="stylesheet" type="text/css" href="gui/css/ProfileEditor.css" />
      <script type="text/javascript" src="gui/js/profile_editor/ProfileGeneralSettings.js"></script>
      <script type="text/javascript" src="gui/js/profile_editor/ProfileRoleDelegation.js"></script>
      <script type="text/javascript" src="gui/js/profile_editor/ProfileEditor.js"></script>

      <!-- Reporting  -->

      <link rel="stylesheet" type="text/css" href="gui/css/ChartDateEditor.css" />
      <link rel="stylesheet" type="text/css" href="gui/css/ReportManager.css" />
      <link rel="stylesheet" type="text/css" href="gui/css/AvailableCharts.css" />

      <script type="text/javascript" src="gui/js/report_builder/ChartThumbPreview.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportExportMenu.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportCloneMenu.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportEntryTypeMenu.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ChartDateEditor.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/Reporting.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportManager.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/AvailableCharts.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ChartAnnotator.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/SaveReportAsDialog.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportCreatorGrid.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportCreator.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportsOverview.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportPreview.js"></script>
      
      <script type="text/javascript" src="gui/js/report_builder/RoleBreakdownGrid.js"></script>
    
      <link rel="stylesheet" type="text/css" href="gui/css/compliance.css" /> 
      
     
      <script type="text/javascript" src="gui/js/Assistant.js"></script>
       
      <script type="text/javascript" src="gui/lib/highcharts/highcharts.src.js"></script>
      <script type="text/javascript" src="gui/lib/highcharts/highcharts-more.js"></script>
      <script type="text/javascript" src="gui/lib/highcharts/errorbars.src.js"></script>

      <script type="text/javascript" src="gui/js/HighChartPanel.js"></script> 

      <link rel="stylesheet" type="text/css" href="gui/css/ChartDragDrop.css" />
      
      <script type="text/javascript" src="gui/js/CustomJsonStore.js"></script> 
      <script type="text/javascript" src="gui/js/Portal.js"></script>  
      <script type="text/javascript" src="gui/js/PortalColumn.js"></script>
      <script type="text/javascript" src="gui/js/Portlet.js"></script> 
      
      <link rel="stylesheet" type="text/css" href="gui/css/TreeCheckbox.css" />
      <link rel="stylesheet" type="text/css" href="gui/css/TriStateNodeUI.css" />
      
      <script type="text/javascript" src="gui/js/TreeCheckbox.js"></script>
      <script type="text/javascript" src="gui/js/TriStateNodeUI.js"></script>
      
      <script type="text/javascript" src="gui/js/RESTTree.js"></script>
      <script type="text/javascript" src="gui/js/BufferView.js"></script>
      <script type="text/javascript" src="gui/js/Spinner.js"></script>
      <script type="text/javascript" src="gui/js/SpinnerField.js"></script>
      <script type="text/javascript" src="gui/js/CustomCheckItem.js"></script>
      <script type="text/javascript" src="gui/js/CustomDateField.js"></script>	
      <script type="text/javascript" src="gui/js/CustomSplitButton.js"></script>	
      <script type="text/javascript" src="gui/js/CustomTwinTriggerField.js"></script>	
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
      <script type="text/javascript" src="gui/js/FilterDimensionPanel.js"></script>
      <script type="text/javascript" src="gui/js/ChartFilterSelector.js"></script>
      
      <script type="text/javascript" src="gui/js/CustomMenu.js"></script> 
      <script type="text/javascript" src="gui/js/AddDataPanel.js"></script> 
      
      <!-- Custom query panel for one-off queries -->
         
      <link rel="stylesheet" type="text/css" href="gui/css/Allocations.css" />

      <script type="text/javascript" src="gui/js/CaptchaField.js"></script>
      <script type="text/javascript" src="gui/js/ContactDialog.js"></script>
                  
      <!-- Modules -->
      
      <script type="text/javascript" src="gui/js/PortalModule.js"></script>
      
      <script type="text/javascript" src="gui/js/modules/Summary.js"></script>
      <script type="text/javascript" src="gui/js/modules/Usage.js"></script>
      <script type="text/javascript" src="gui/js/modules/UsageExplorer.js"></script> 
      <script type="text/javascript" src="gui/js/modules/Allocations.js"></script>
      <script type="text/javascript" src="gui/js/modules/AppKernels.js"></script>
      <script type="text/javascript" src="gui/js/modules/AppKernelExplorer.js"></script>
      <script type="text/javascript" src="gui/js/modules/ReportGenerator.js"></script>
      <script type="text/javascript" src="gui/js/modules/Compliance.js"></script>
      <script type="text/javascript" src="gui/js/modules/CustomQueries.js"></script> 
      <script type="text/javascript" src="gui/js/modules/SciImpact.js"></script>
   
      <?php
         xd_utilities\checkForCenterLogo();
      ?>
      
      <script type="text/javascript" src="gui/js/Viewer.js"></script>
      
      <?php
         require_once dirname(__FILE__).'/gaq.php';
      ?>
	  
      <script type="text/javascript"> 
      
         var isCallCached = false;
         var consultCallCache = function() {}; 
         var xsedeProfilePrompt = function() {};
         
         <?php  
            if (isset($_SESSION['cached_call']) && !empty($_SESSION['cached_call'])) {
         ?>
            
            isCallCached = true;
            consultCallCache = function() { CCR.xdmod.ui.Viewer.<?php print $_SESSION['cached_call']; ?> }
            
         <?php
         
               unset($_SESSION['cached_call']);
         
            }
            
            // ==============================================
                        
            $profile_editor_init_flag = '';
            
            if ($user->isXSEDEUser() == true) {
            
               // If the user logging in is an XSEDE user, he/she may or may not have
               // an e-mail address set. The logic below assists in presenting the Profile Editor
               // with the appropriate (initial) view
            
               $xsede_user_first_login = ($user->getCreationTimestamp() == $user->getLastLoginTimestamp());
            
               $xsede_user_email_specified = ($user->getEmailAddress() != NO_EMAIL_ADDRESS_SET);
               
               // ------------------------------------
               
               // NOTE: $_SESSION['suppress_profile_autoload'] will be set only upon update of the user's profile (see respective REST call)
               
               if ($xsede_user_first_login && $xsede_user_email_specified && (!isset($_SESSION['suppress_profile_autoload'])) ) {
                  
                  // If the user is logging in for the first time and does have an e-mail address set
                  // (due to it being specified in the XDcDB), welcome the user and inform them they
                  // have an opportunity to update their e-mail address.
                  
                  $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.WELCOME_EMAIL_CHANGE';
                  
               }
               elseif ($xsede_user_first_login && !$xsede_user_email_specified) {
               
                  // If the user is logging in for the first time and does *not* have an e-mail address set,
                  // welcome the user and inform them that he/she needs to set an e-mail address.
                  
                  $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.WELCOME_EMAIL_NEEDED';
               
               }
               elseif(!$xsede_user_email_specified) {
               
                  // Regardless of whether the user is logging in for the first time or not, the lack of 
                  // an e-mail address requires attention
                  
                  $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.EMAIL_NEEDED';
               
               }
               
            }//if ($user->isXSEDEUser() == true)
            
            // ==============================================           
            
            if (!empty($profile_editor_init_flag)) {
         
         ?>
         
            xsedeProfilePrompt = function() { 
            
               (function() {
               
                  var profileEditor = new XDMoD.ProfileEditor();
                  profileEditor.init();
                  
               }).defer(1000);
					
            };
         
         <?php } ?>
      
      </script>
      
      <script type="text/javascript">Ext.onReady(xdmodviewer.init, xdmodviewer);</script>

   </head>

   <body> 

      <!-- Fields required for history management -->
      <form id="history-form" class="x-hidden">
         <input type="hidden" id="x-history-field" />
         <iframe id="x-history-frame"></iframe>
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