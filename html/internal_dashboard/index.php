<?php

require_once 'user_check.php';

if (isset($_POST['direct_to'])) {
  header('Location: ' . $_POST['direct_to']);
  exit;
}

?>
<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>XDMoD Internal Dashboard</title>
  <link rel="icon" href="../favicon.ico" />

  <link rel="stylesheet" type="text/css" href="css/dashboard.css">
  <link rel="stylesheet" type="text/css" href="css/management.css">
  <link rel="stylesheet" type="text/css" href="css/AdminPanel.css" />

  <?php ExtJS::loadSupportScripts('../gui/lib'); ?>

  <script type="text/javascript" src="../gui/lib/jquery/jquery.min.js"></script>

  <script type="text/javascript">
    jQuery.noConflict();
  </script>

  <link rel="stylesheet" type="text/css" href="../gui/css/viewer.css">

  <script type="text/javascript" src="../gui/lib/debug.js"></script>
  <script type="text/javascript" src="../gui/lib/datadumper.js"></script>

  <!-- Non-GUI JS Class Definitions -->

  <script type="text/javascript" src="../js_classes/DateUtilities.js"></script>
  <script type="text/javascript" src="../js_classes/StringUtilities.js"></script>
  <script type="text/javascript" src="js/messaging.js"></script>
  <script type="text/javascript" src="js/DashboardStore.js"></script>
  <script type="text/javascript" src="../gui/js/MessageWindow.js"></script>
  <script type="text/javascript" src="../gui/js/CCR.js"></script>
  <script type="text/javascript" src="../gui/js/ContainerMask.js"></script>
  <script type="text/javascript" src="../gui/js/TGUserDropDown.js"></script>
  <script type="text/javascript" src="../gui/js/InstitutionDropDown.js"></script>
  <script type="text/javascript" src="../gui/js/CheckColumn.js"></script>
  <script type="text/javascript" src="../gui/js/LimitedField.js"></script>

  <script type="text/javascript">
    var dashboard_user_full_name = <?php echo json_encode($user->getFormalName()); ?>;
  </script>

  <!-- Globals -->

  <script type="text/javascript" src="../gui/js/globals.js"></script>
  <script type="text/javascript" src="../gui/js/StringExtensions.js"></script>

  <!-- Plugins -->

  <script type="text/javascript" src="../gui/js/plugins/ContextSensitiveHelper.js"></script>
  <script type="text/javascript" src="../gui/js/plugins/CollapsedPanelTitlePlugin.js"></script>

  <!-- Libraries -->

  <script type="text/javascript" src="../gui/js/libraries/utilities.js"></script>

  <script type="text/javascript" src="../gui/js/SessionManager.js"></script>

  <!-- RESTProxy -->

  <script type="text/javascript" src="../gui/js/RESTProxy.js"></script>

  <link rel="stylesheet" type="text/css" href="../gui/css/MultiSelect.css"/>
  <link rel="stylesheet" type="text/css" href="../gui/lib/extjs/examples/ux/css/Spinner.css" />
  <link rel="stylesheet" type="text/css" href="../gui/lib/extjs/examples/ux/css/LockingGridView.css" />

  <!-- Support Extensions for 'User Like Me' -->

  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/DataViewTransition.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/Reorderer.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/ToolbarReorderer.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/SearchField.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/statusbar/StatusBar.js"> </script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/LockingGridView.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/SlidingPager.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/ProgressBarPager.js"></script>
  <script type="text/javascript" src="../gui/js/MultiSelect.js"></script>
  <script type="text/javascript" src="../gui/js/ItemSelector.js"></script>

  <script type="text/javascript" src="../gui/lib/NumberFormat.js"></script>
  <script type="text/javascript" src="../gui/js/multiline-tree-nodes.js"></script>

  <script type="text/javascript" src="../gui/js/RESTDataProxy.js"></script>
  <script type="text/javascript" src="../gui/js/printer/Printer-all.js"></script>

  <script type="text/javascript" src="../gui/js/login.js.php"></script>
  <script type="text/javascript" src="../gui/js/LoginPrompt.js"></script>

  <script type="text/javascript" src="../gui/js/ContainerBodyMask.js"></script>

  <link rel="stylesheet" type="text/css" href="../gui/css/common.css" />

  <script type="text/javascript" src="../gui/lib/highcharts/highcharts.src.js"></script>
  <script type="text/javascript" src="../gui/lib/highcharts/highcharts-more.js"></script>

  <script type="text/javascript" src="../gui/lib/highcharts/errorbars.src.js"></script>

  <script type="text/javascript" src="../gui/js/HighChartPanel.js"></script>

  <link rel="stylesheet" type="text/css" href="../gui/css/ChartDragDrop.css" />

  <script type="text/javascript" src="../gui/js/CustomJsonStore.js"></script>
  <script type="text/javascript" src="../gui/js/Portal.js"></script>
  <script type="text/javascript" src="../gui/js/PortalColumn.js"></script>
  <script type="text/javascript" src="../gui/js/Portlet.js"></script>

  <script type="text/javascript" src="../gui/js/RESTTree.js"></script>
  <script type="text/javascript" src="../gui/js/BufferView.js"></script>
  <script type="text/javascript" src="../gui/js/Spinner.js"></script>
  <script type="text/javascript" src="../gui/js/SpinnerField.js"></script>
  <script type="text/javascript" src="../gui/js/CustomCheckItem.js"></script>
  <script type="text/javascript" src="../gui/js/CustomDateField.js"></script>
  <script type="text/javascript" src="../gui/js/CustomSplitButton.js"></script>
  <script type="text/javascript" src="../gui/js/CustomRowNumberer.js"></script>
  <script type="text/javascript" src="../gui/js/DynamicGridPanel.js"></script>
  <script type="text/javascript" src="../gui/js/DurationToolbar.js"></script>
  <script type="text/javascript" src="../gui/js/ChartConfigMenu.js"></script>
  <script type="text/javascript" src="../gui/js/ChartToolbar.js"></script>
  <script type="text/javascript" src="../gui/js/DrillDownMenu.js"></script>
  <script type="text/javascript" src="../gui/js/CustomSearch.js"></script>
  <script type="text/javascript" src="../gui/js/ChartDragDrop.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/DataView-more.js"></script>
  <script type="text/javascript" src="../gui/js/FilterDimensionPanel.js"></script>
  <script type="text/javascript" src="../gui/js/ChartFilterSelector.js"></script>

  <script type="text/javascript" src="../gui/js/CustomMenu.js"></script>
  <script type="text/javascript" src="../gui/js/AddDataPanel.js"></script>

  <script type="text/javascript" src="../gui/js/Viewer.js"></script>

  <script type="text/javascript" src="../gui/js/RowExpander.js"></script>

  <!-- User Management Panel -->

  <script type="text/javascript" src="js/admin_panel/RoleGrid.js"></script>
  <script type="text/javascript" src="js/admin_panel/SectionNewUser.js"></script>
  <script type="text/javascript" src="js/admin_panel/SectionExistingUsers.js"></script>
  <script type="text/javascript" src="js/admin_panel/AdminPanel.js"></script>
  <script type="text/javascript" src="js/UserManagement/Panel.js"></script>

  <script type="text/javascript" src="js/CommentEditor.js"></script>
  <script type="text/javascript" src="js/AccountRequests.js"></script>

  <script type="text/javascript" src="js/RecipientVerificationPrompt.js"></script>
  <script type="text/javascript" src="js/BatchMailClient.js"></script>
  <script type="text/javascript" src="js/CurrentUsers.js"></script>

  <script type="text/javascript" src="js/ExceptionLister.js"></script>

  <script type="text/javascript" src="js/UserStats.js"></script>
 
  <!-- Summary Panel -->

  <script type="text/javascript" src="js/Summary/ConfigStore.js"></script>
  <script type="text/javascript" src="js/Summary/PortletsStore.js"></script>
  <script type="text/javascript" src="js/Summary/Portlet.js"></script>
  <script type="text/javascript" src="js/Summary/Portal.js"></script>
  <script type="text/javascript" src="js/Summary/TabPanel.js"></script>
  <script type="text/javascript" src="js/DashboardTools.js"></script>

  <script type="text/javascript" src="js/Log/SummaryStore.js"></script>
  <script type="text/javascript" src="js/Log/SummaryPortlet.js"></script>
  <script type="text/javascript" src="js/Log/LevelsStore.js"></script>
  <script type="text/javascript" src="js/Log/Store.js"></script>
  <script type="text/javascript" src="js/Log/GridPanel.js"></script>
  <script type="text/javascript" src="js/Log/TabPanel.js"></script>

  <script type="text/javascript" src="js/UsersSummary/Store.js"></script>
  <script type="text/javascript" src="js/UsersSummary/Portlet.js"></script>

  <script type="text/javascript" src="js/Arr/SummaryStore.js"></script>
  <script type="text/javascript" src="js/Arr/SummaryPortlet.js"></script>
  <script type="text/javascript" src="js/Arr/ActiveTasksStore.js"></script>
  <script type="text/javascript" src="js/Arr/ActiveTasksGrid.js"></script>
  <script type="text/javascript" src="js/Arr/AppKerSuccessRateStore.js"></script>
  <script type="text/javascript" src="js/Arr/AppKerSuccessRateGrid.js"></script>
  <script type="text/javascript" src="js/Arr/AppKerSuccessRatePanel.js"></script>
  <script type="text/javascript" src="js/Arr/AppKerSuccessRatePlotPanel.js"></script>
  <script type="text/javascript" src="js/Arr/AppKerStatsOverNodesStore.js"></script>
  <script type="text/javascript" src="js/Arr/AppKerStatsOverNodesGrid.js"></script>
  <script type="text/javascript" src="js/Arr/AppKerStatsOverNodesPanel.js"></script>
  <script type="text/javascript" src="js/Arr/StatusPanel.js"></script>
  <script type="text/javascript" src="js/Arr/ErrorMessageStore.js"></script>
  <script type="text/javascript" src="js/Arr/ErrorMessagePanel.js"></script>

  <script type="text/javascript" src="js/AppKernel/InstancePanel.js"></script>
  <script type="text/javascript" src="js/AppKernel/InstanceWindow.js"></script>

  <script type="text/javascript" src="js/Ingestion/AppKernelStore.js"></script>
  <script type="text/javascript" src="js/Ingestion/AppKernelGrid.js"></script>
  <script type="text/javascript" src="js/Ingestion/ReportsPanel.js"></script>

  <script type="text/javascript" src="js/Dashboard/Factory.js"></script>
  <script type="text/javascript" src="js/Dashboard/MenuStore.js"></script>
  <script type="text/javascript" src="js/Dashboard/FramePanel.js"></script>
  <script type="text/javascript" src="js/Dashboard/Viewport.js"></script>

  <script type="text/javascript" src="js/dashboard.js"></script>
</head>
<body></body>
</html>
