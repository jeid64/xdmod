<?php

namespace DataWarehouse;
use \Exception;

require_once("Log.php");

class Explorer extends \aRestAction
{
  protected $logger = NULL;



  // --------------------------------------------------------------------------------
  // @see aRestAction::__call()
  // --------------------------------------------------------------------------------
  
  public function __call($target, $arguments)
  {
    // Verify that the target method exists and call it.
    
    $method = $target . ucfirst($this->_operation);
    
    if ( ! method_exists($this, $method) )
    {
      
      if ($this->_operation == 'Help')
      {
        // The help method for this action does not exist, so attempt to generate a response
        // using that action's Documentation() method
        
        $documentationMethod = $target.'Documentation';
        
        if ( ! method_exists($this, $documentationMethod) )
        {
          throw new Exception("Help cannot be found for action '$target'");
        }
        
        return $this->$documentationMethod()->getRESTResponse();            
        
      }
      else
      {
        throw new Exception("Unknown action '$target' in category '" . strtolower(__CLASS__)."'");
      }
         
    }  // if ( ! method_exists($this, $method) )
         
    return $this->$method($arguments);
    
  } // __call()

  // --------------------------------------------------------------------------------

  public function __construct($request)
  {
    parent::__construct($request);

    // Initialize the logger

    $params = $this->_parseRestArguments("");
    $verbose = ( isset($params['debug']) && $params['debug'] );
    $maxLogLevel = ( $verbose ? PEAR_LOG_DEBUG : PEAR_LOG_INFO );
    $logConf = array('mode' => 0644);
    $logfile = LOG_DIR . "/" . \xd_utilities\getConfiguration('datawarehouse', 'rest_logfile');
    $this->logger = \Log::factory('file', $logfile, 'DataWarehouse', $logConf, $maxLogLevel);

  }  // __construct

  // --------------------------------------------------------------------------------
  // @see aRestAction::factory()
  // --------------------------------------------------------------------------------
  
  public static function factory($request)
  {
    return new Explorer($request);
  }
  
   // returns all of the query groups in the data warehouse for the logged in user.
  private function queryGroupsAction()
  {
	$user = $this->_authenticateUser();
	$role = $user->getActiveRole();
	
	$queryGroups = $role->getAllGroupNames();
	
    return array('success' => true,
                 'results' => $queryGroups);
  }
  private function queryGroupsDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setDescription("Retrieve the list of query groups assigned to this user.");
    $doc->setAuthenticationRequirement(true);
    $doc->addReturnElement("queryGroups", "An array enumerating the query groups assigned to this user.");
    return $doc;
  }
  
  // returns the realms in the data warehouse.
  private function realmsAction()
  {
	$user = $this->_authenticateUser();
	$role = $user->getActiveRole();
	
	$params = $this->_parseRestArguments(); 
	$queryGroup =  isset($params['querygroup']) ? $params['querygroup'] : 'rest' ; 

	$realms = array_keys($role->getAllQueryRealms($queryGroup));
	
    return array('success' => true,
                 'results' => $realms);
  }
  private function realmsDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setDescription("Retrieve the list of query realms. If a query group is specified it will list the realms in that query group. Otherwise, the query realms in the 'rest' query group are enumerated.");
	$doc->addArgument("querygroup", "A valid query group name. If not passed, 'rest' is used. Enumerated by the queryGroups action.", FALSE);
    $doc->setAuthenticationRequirement(true);
    $doc->addReturnElement("realms", "An array enumerating the query realm names.");
    return $doc;
  }
  //given a realm(optional) and querygroup(optional),
  // returns all the dimensions (groupby).
  private function dimensionsAction()
  {
	$user = $this->_authenticateUser();
	$role = $user->getActiveRole();

	$params = $this->_parseRestArguments(); 
	$realm =  isset($params['realm']) ? $params['realm'] : NULL ; 
	$queryGroup =  isset($params['querygroup']) ? $params['querygroup'] : 'rest' ; 
	
	$dimensionsToReturn = array();
	
	$realms = $role->getAllQueryRealms($queryGroup);

	foreach($realms  as $query_realm_key => $query_realm_object)
	{
		if($realm == NULL || $realm == $query_realm_key)
		{
			$dimensionsToReturn = array_merge($dimensionsToReturn, array_keys($query_realm_object));
		}				 
	}
	$dimensionsToReturn = array_values(array_unique($dimensionsToReturn));
    return array('success' => true,
                 'results' => $dimensionsToReturn);
  }
  private function dimensionsDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setDescription("Retrieve the list of dimensions of the data warehouse.");
    $doc->setAuthenticationRequirement(true);
	$doc->addArgument("realm", "A valid query realm name.", FALSE);
	$doc->addArgument("querygroup", "A valid query group name. If not passed, 'rest' is used. Enumerated by the queryGroups action.", FALSE);
    $doc->addReturnElement("dimensions", "An array enumerating the query realm names.");
    return $doc;
  }  
  //given the realm, dimension(groupby) and querygroup(optional, 'rest' used by default)
  // returns all the unique values for the dimension(groupby).
  private function dimensionCatalogAction()
  {
	$user = $this->_authenticateUser();
	$role = $user->getActiveRole();

	$dimensionCatalogToReturn = array();
	  
	$params = $this->_parseRestArguments(); 
	$realm =  isset($params['realm']) ? $params['realm'] : NULL ; 
	$dimension =  isset($params['dimension']) ? $params['dimension'] : NULL ; 
	if ( NULL === $realm )
    {
      throw new Exception("Realm must be specified");
    }
	if ( NULL === $dimension )
    {
      throw new Exception("Dimension must be specified");
    }
	$queryGroup =  isset($params['querygroup']) ? $params['querygroup'] : 'rest' ; 
	
	$realms = $role->getAllQueryRealms($queryGroup);
	
	foreach($realms  as $query_realm_key => $query_realm_object)
	{
		if($realm == $query_realm_key)
		{
			$query_class_name = \DataWarehouse\QueryBuilder::getQueryRealmClassname($query_realm_key);
			
			$query_class_name::registerGroupBys();
			$query_class_name::registerStatistics();
			
			$group_by_instance = $query_class_name::getGroupBy($dimension);
			$dimensionCatalogToReturn = $group_by_instance->getPossibleValues();
		}				 
	}
    return array('success' => true,
                 'results' => $dimensionCatalogToReturn);
  }  
  private function dimensionCatalogDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setDescription("Retrieve the list of possible values for a given realm\dimension.");
    $doc->setAuthenticationRequirement(true);
	$doc->addArgument("realm", "A valid query realm name.", TRUE);
	$doc->addArgument("dimension", "A valid realm dimension name.", TRUE);
	$doc->addArgument("querygroup", "A valid query group name. If not passed, 'rest' is used. Enumerated by the queryGroups action.", FALSE);
    $doc->addReturnElement("dimensionCatalog", "An array enumerating the list of possible values for the given realm\dimension.");
    return $doc;
  } 
  // returns all facts contained in the data warehouse. ie: job_count, wait_time, etc
  // @realm(optional)
  // @dimension(optional)
  // @querygroup(optional: 'rest' used by default)
  private function factsAction()
  {
	$user = $this->_authenticateUser();
	$role = $user->getActiveRole();

	$factsToReturn = array();
	
	$params = $this->_parseRestArguments(); 
	$realm =  isset($params['realm']) ? $params['realm'] : NULL ; 
	$dimension =  isset($params['dimension']) ? $params['dimension'] : NULL ; 	
	$queryGroup =  isset($params['querygroup']) ? $params['querygroup'] : 'rest' ; 
	
	$realms = $role->getAllQueryRealms($queryGroup);
	foreach($realms  as $query_realm_key => $query_realm_object)
	{
		if($realm == NULL || $realm == $query_realm_key)
		{
			$query_class_name = \DataWarehouse\QueryBuilder::getQueryRealmClassname($query_realm_key);
			
			$query_class_name::registerGroupBys();
			$query_class_name::registerStatistics();
			
			$group_bys = array_keys($query_realm_object);
			foreach($group_bys as $group_by)
			{
				if($dimension == NULL || $dimension == $group_by)
				{
					$group_by_instance = $query_class_name::getGroupBy($group_by);
					
					$factsToReturn = array_merge($factsToReturn,$group_by_instance->getPermittedStatistics());
				}
			}	
		}				 
	}
	$factsToReturn = array_values(array_unique($factsToReturn));

    return array('success' => true,
                 'results' => $factsToReturn);
  } 
  private function factsDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setDescription("Retrieve the list of facts in the datawarehouse.");
    $doc->setAuthenticationRequirement(true);
	$doc->addArgument("realm", "A valid query realm name.", FALSE);
	$doc->addArgument("dimension", "A valid realm dimension name.", FALSE);
	$doc->addArgument("querygroup", "A valid query group name. If not passed, 'rest' is used. Enumerated by the queryGroups action.", FALSE);
    $doc->addReturnElement("facts", "An array enumerating the list of facts in the datawarehouse.");
    return $doc;
  } 
  
  private function aggregationUnitsAction()
  {
	  $user = $this->_authenticateUser();
	  $aggregation_units = \DataWarehouse\QueryBuilder::getAggregationUnits();
	  return array('success' => true,
                 'results' => array_keys($aggregation_units));
  }
  
  private function aggregationUnitsDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setAuthenticationRequirement(true);
	$doc->setDescription("Retrieve the list of aggregation units supported by the data warehouse.");
	$doc->addReturnElement("aggregationUnits", "An array enumerating the list of aggregation units.");
    return $doc;
  }  
  
  private function datasetTypesAction()
  {
	  $user = $this->_authenticateUser();
	  $datasetTypes = \DataWarehouse\QueryBuilder::getDatasetTypes();
	  return array('success' => true,
                 'results' => $datasetTypes);
  }
  
  private function datasetTypesDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setAuthenticationRequirement(true);
	$doc->setDescription("Retrieve the list of dataset types supported by the data warehouse dataset action.");
	$doc->addReturnElement("datasetTypes", "An array enumerating the list of datasetTypes.");
    return $doc;
  }  
  
  private function datasetFormatsAction()
  {
	  $user = $this->_authenticateUser();

	  return array('success' => true,
                 'results' => \DataWarehouse\ExportBuilder::$dataset_action_formats);
  }
  
  private function datasetFormatsDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setAuthenticationRequirement(true);
	$doc->setDescription("Retrieve the list of dataset output formats supported by the data warehouse dataset action. If a format is not specified, the first returned format will be used as the default.");
    $doc->addReturnElement("datasetFormats", "An array enumerating the list of output formats by the dataset action.");
	return $doc;
  }  
  
  // @realm(optional)
  // @dimension(optional)
  private function datasetAction()
  {
	$user = $this->_authenticateUser();
	$params = $this->_parseRestArguments(); 
	
	$inline = true;
	if(isset($params['inline']))
	{
		$inline = $params['inline'] == 'true' || $params['inline'] === 'y';
	}
		
	$format = \DataWarehouse\ExportBuilder::getFormat($params, \DataWarehouse\ExportBuilder::getDefault(\DataWarehouse\ExportBuilder::$dataset_action_formats), \DataWarehouse\ExportBuilder::$dataset_action_formats);
	$queries = \DataWarehouse\QueryBuilder::getInstance()->buildQueriesFromRequest($params, $user);
	$returnData = \DataWarehouse\ExportBuilder::buildExport($queries, $params, $user, $format);
	if($format == 'jsonstore' || $format == 'json')
	{	
	echo \DataWarehouse\ExportBuilder:: writeHeader($format, $inline);
	 echo json_encode($returnData);
	 exit;
	}
	else if ($format == 'html')
	{
		echo \DataWarehouse\ExportBuilder:: writeHeader($format, $inline);
	 echo implode("\n",$returnData);
	 exit;
		
	}
  }
  
  private function datasetDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setAuthenticationRequirement(true);
	$doc->setDescription("Retrieve a dataset from the datawarehouse.");
	$doc->addArgument("querygroup", "A valid query group name. If not passed, 'rest' is used. Enumerated by the queryGroups action.", FALSE);
	$doc->addArgument("realm", "A valid query realm name.", TRUE);
	$doc->addArgument("dimension", "A valid realm dimension name. A dimension is synonymous with a query group_by", TRUE);
	$doc->addArgument("fact", "A valid fact name. A fact is synonymous with a statistic from a query. Defaults to all facts in selected dimension.", FALSE);
	$doc->addArgument("start_date", "A valid start date for the query. Format yyyy-mm-dd.", TRUE);
	$doc->addArgument("end_date", "A valid end date for the query. Format yyyy-mm-dd.", TRUE);
	$doc->addArgument("aggregation_unit", "A valid aggregation unit. Defaults to auto. Enumerated by the aggregationUnits action.", FALSE);
	$doc->addArgument("dataset_type", "A valid dataset type. Defaults to aggregate. Enumerated by the datasetTypes action.", FALSE);
	$doc->addArgument("format", "A valid dataset format. Enumerated by the datasetFormats action.", FALSE);
	$doc->addReturnElement("dataset", "An dataset object in the requested output format.");
    return $doc;
  } 
 
  private function plotFormatsAction()
  {
	  $user = $this->_authenticateUser();
	  return array('success' => true,
                 'results' => \DataWarehouse\VisualizationBuilder::$plot_action_formats);
  }
  
  private function plotFormatsDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setAuthenticationRequirement(true);
	$doc->setDescription("Retrieve the list of output formats supported by the data warehouse plot action. If a format is not specified, the first returned format will be used as the default.");
	
	$doc->addReturnElement("plotFormats", "An array enumerating the list of output formats by the plot action.");
    return $doc;
  }   
  
  private function displayTypesAction()
  {
	  $user = $this->_authenticateUser();
	  $datasetTypes = \DataWarehouse\VisualizationBuilder::$display_types;
	  return array('success' => true,
                 'results' => $datasetTypes);
  }
  
  private function displayTypesDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setAuthenticationRequirement(true);
	$doc->setDescription("Retrieve the list of display types supported by the data warehouse plot action.");
	$doc->addReturnElement("displayTypes", "The list of display types supported by the data warehouse plot action.");	
    return $doc;
  } 
  
  private function combineTypesAction()
  {
	  $user = $this->_authenticateUser();
	  $datasetTypes = \DataWarehouse\VisualizationBuilder::$combine_types;
	  return array('success' => true,
                 'results' => $datasetTypes);
  }
  
  private function combineTypesDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setAuthenticationRequirement(true);
	$doc->setDescription("Retrieve the list of data combine types supported by the data warehouse plot action.");
	$doc->addReturnElement("combine", "The list of data combine types supported by the data warehouse plot action.");		
    return $doc;
  } 
  // @realm(optional)
  // @dimension(optional)
  private function plotAction()
  {
	$user = $this->_authenticateUser();
	$params = $this->_parseRestArguments(); 
	
	$inline = true;
	if(isset($params['inline']))
	{
		$inline = $params['inline'] == 'true' || $params['inline'] === 'y';
	}
	$format = \DataWarehouse\ExportBuilder::getFormat($params, \DataWarehouse\ExportBuilder::getDefault(\DataWarehouse\VisualizationBuilder::$plot_action_formats), \DataWarehouse\VisualizationBuilder::$plot_action_formats);
	$queries = \DataWarehouse\QueryBuilder::getInstance()->buildQueriesFromRequest($params, $user);
	$returnData = \DataWarehouse\VisualizationBuilder::getInstance()->buildVisualizationsFromQueries($queries, $params, $user, $format);

	if($format != 'png' && $format != 'png_inline' && $format != 'svg' && $format != 'img_tag')
	{	
	  return array('success' => true,
                 'results' => $returnData);
	}
	foreach($returnData as $d)
	{
		echo \DataWarehouse\ExportBuilder:: writeHeader($format, $inline);
		echo $d;
		exit;
	}
  }
  
  private function plotDocumentation()
  {
	$doc = new \RestDocumentation();
    $doc->setAuthenticationRequirement(true);
	$doc->setDescription("Plots a dataset.");
	$doc->addArgument("querygroup", "A valid query group name. If not passed, 'rest' is used. Enumerated by the queryGroups action.", FALSE);
	$doc->addArgument("realm", "A valid query realm name.", TRUE);
	$doc->addArgument("dimension", "A valid realm dimension name. A dimension is synonymous with a query group_by.", TRUE);
	$doc->addArgument("fact", "A valid fact name. A fact is synonymous with a statistic from a query. Defaults to all facts in selected dimension.", FALSE);
	$doc->addArgument("start_date", "A valid start date for the query. Format yyyy-mm-dd.", TRUE);
	$doc->addArgument("end_date", "A valid end date for the query. Format yyyy-mm-dd.", TRUE);
	$doc->addArgument("aggregation_unit", "A valid aggregation unit. Defaults to auto. Enumerated by the aggregationUnits action.", FALSE);
	$doc->addArgument("dataset_type", "A valid dataset type. Defaults to aggregate. Enumerated by the datasetTypes action.", FALSE);

	
	$doc->addArgument("show_title", "Whether to show the title on the chart or not. 'y' or 'true' evaluate to true, false otherwise. Defaults to false.", FALSE);
	$doc->addArgument("show_guide_lines", "Whether to show the guide lines on the chart or not. 'y' or 'true' evaluate to true, false otherwise. Defaults to true.", FALSE);
	$doc->addArgument("show_legend", "Whether to show the legend on the chart or not. 'y' or 'true' evaluate to true, false otherwise. Defaults to true.", FALSE);
	$doc->addArgument("log_scale", "Whether or not to use log scale for the data. Defaults to false.", FALSE);
	
	$doc->addArgument("scale", "A positive real number to scale the width and height of the chart. Defaults to 1.", FALSE);
	$doc->addArgument("width", "A positive integer to use for the width of the chart in pixels. Defaults to 740.", FALSE);
	$doc->addArgument("height", "A positive integer to use for the height of the chart in pixels. Defaults to 345.", FALSE);
	$doc->addArgument("thumbnail", "If false, the chart map is not generated and returned.. 'y' or 'true' evaluate to true, false otherwise. Defaults to false.", FALSE);	
	$doc->addArgument("display_type", "A valid display type as enumerated by the displayTypes action. Defaults to auto.", FALSE);	
	$doc->addArgument("combine_type", "A valid combine type as enumerated by the combineTypes action. Defaults to auto.", FALSE);	
	$doc->addArgument("offset", "An integer greater than -1 to use as the offset for the dataset for the chart. Defaults to 0.", FALSE);	
	$doc->addArgument("limit", "An integer greater than 0 to use as the limit of data points of the chart. Defaults to 20.", FALSE);	
	
	$doc->addArgument("format", "A valid plot output format. Enumerated by the plotFormats action.", FALSE);
	$doc->addArgument("inline", "Whether the data is returned as inline or attachement. y' or 'true' evaluate to true, false otherwise. Defaults to true.", FALSE);
	
	$doc->addReturnElement("plot", "An plot object in the requested output format.");	
	
    return $doc;
  }      
  
}  // class Explorer

?>
