<?php

	namespace xd_rest;

	// ----------------------------------------------------------

   //resolveSecurePort: To account for port changes when reverting from HTTP to HTTPS
   
   function resolveSecurePort($port) {
   
      switch($port) {
      
       // Secure (HTTPS)      Non-Secure (HTTP)
          case ':9444':       return ':9001';
         
      }
   
      return $port;
   
   }//resolveSecurePort

	// ----------------------------------------------------------
	
	// @function getExceptionMessageSuffix
	// Returns a string which gets appended to REST-related exceptions
	
	function getExceptionMessageSuffix() {
	
	  $tech_support_recipient = \xd_utilities\getConfiguration('general', 'tech_support_recipient');
	
	  return '. If you require assistance, please contact the XDMoD team at ' . $tech_support_recipient;
	
	}//getExceptionMessageSuffix

	// ----------------------------------------------------------
		   		
   // Use retrospection to enumerate a list of valid response formats based on what is publicly defined in RestResponse.
   // To disable a format from being used, set the visibility of the respective function (e.g. jsonFormat) to non-public (e.g. 'private', 'protected', etc.)
         
	function enumerateOutputFormats () {
	
      $reflection = new \ReflectionClass('RestResponse');
      $allResponseMethods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
      
      $filteredResponseMethods = array_filter(
         $allResponseMethods,
         function ($element) {
            return preg_match('/^(.+)Format$/', $element->name);
         }
      ); 
          
      $validFormats = array();
                 
      // Create a reference to a (dummy) RestResponse object so that the description found in the help() functions for each of the
      // supported formats can be retrieved.
      
      $r = \RestResponse::factory(array());
      
      foreach($filteredResponseMethods as $f) {
      
         // The user should not be able to explicitly request 'raw', as all request handlers may not explicitly provide a content-type 
         
         if ($f->name != 'rawFormat'){
            
            $formatName = substr($f->name, 0, -6);
           
            // To Do: Account for cases where the xxxxxHelp() function does not exist (assumption is being made at this point)
            
            $validFormats[$formatName] = $r->{$formatName.'Help'}();

         }
               
      }//foreach
      
      return $validFormats;
	
	}//enumerateOutputFormats
	
	// ----------------------------------------------------------
	
	// enumerateRAWFormats:
	// Determine the RAW types supported by the REST framework
	
   function enumerateRAWFormats () {
	
      $raw_types = array();
	  
      $raw_formats_dir = REST_BASE_DIRECTORY.'raw_formats/';

      if ($dh = opendir($raw_formats_dir)) {
               
         while (($file = readdir($dh)) !== false) {
               
            if ($file == '.' || $file == '..' || $file == '.svn') continue;
            
            require_once($raw_formats_dir.$file);
            
            $formatClass = substr($file, 0, -4);
            
            $class = new \ReflectionClass($formatClass);
            
            if ($class->implementsInterface('iBinaryFormat')) {

               $obj_format = new $formatClass();
               $raw_types[$obj_format->toString()] = $obj_format->getDescription();
               
            }
                              
         }//while
   
         closedir($dh);
                  
      }//if
	  
	  return $raw_types;
	
	}//enumerateRAWFormats
	
	// ----------------------------------------------------------

	// enumerateRealms:
	// Retrieve a listing of the realms recognized by the REST service
		
   function enumerateRealms() {
                        
      $realms = array();
            
      $directories_to_ignore = array('.svn', '.', '..', 'raw_formats');
                     
      if (is_dir(REST_BASE_DIRECTORY)) {
            
         if ($dh = opendir(REST_BASE_DIRECTORY)) {
               
            while (($file = readdir($dh)) !== false) {
                  
               if (filetype(REST_BASE_DIRECTORY.$file) == 'dir' && !in_array($file, $directories_to_ignore) && isUniqueRealm($file)) {
                     
                  $realms[] = $file;
                                                
               }
                                    
            }//while
   
            closedir($dh);
                  
         }//if
               
      }//if
            
      return $realms;
         
   }//enumerateRealms	

	// ----------------------------------------------------------

	// enumerateCategories:
	// Retrieve a listing of the realms recognized by the REST service
	   
   function enumerateCategories($realm) {
         
      $realmDir = REST_BASE_DIRECTORY.$realm;
      
      $categories = array();
            
      if ($dh = opendir($realmDir)) {
            
         while (($file = readdir($dh)) !== false) {
               
            if (filetype($realmDir . '/' . $file) == 'file' && substr($file, -4) == '.php') {
                  
               $relativeClassName = substr($file, 0, -4);
                     
               $categories[] = $relativeClassName;
                                          
            }
                                 
         }//while

         closedir($dh);
               
      }//if            
         
      return $categories;
            
   }//enumerateCategories

	// ----------------------------------------------------------

	// enumerateActions:
	// Retrieve a listing of the actions for a particular realm and category
	   
   function enumerateActions($realm, $category) {
            
      require_once(REST_BASE_DIRECTORY."$realm/$category.php");
            
      $reflection = new \ReflectionClass($realm.'\\'.$category);
            
      $methods = $reflection->getMethods();
      
      //Only consider method names with 'Action' as their suffix
      
      $actionMethods = array_filter(
         $methods,
         function ($element) {
            return preg_match('/^(.+)Action$/', $element->name);
         }
      ); 
      
      $actions = array();
      
      foreach($actionMethods as $action) {
      
         $actionName = preg_replace('/^(.+)Action$/', '\1', $action->name);
            
         if (!$reflection->hasMethod($actionName . "Visibility")) {
            $actions[] = $actionName;
         }
      
      }//foreach
         
      return $actions;
            
   }//enumerateActions
   
	// ----------------------------------------------------------
	
	// resolveEntity:
	// Used primarily by the REST Catalog, resolveEntity analyzes a user specified
	// entity, checking against available realms, respective categories, and respective actions
	//	for a match.
	
   function resolveEntity($entity, $type, $realm = "", $category = "") {
	
      $pool = array();
	  
      switch($type) {
	  
         case REST_REALM:
	     
            $pool = enumerateRealms();      
            break;
	        
         case REST_CATEGORY:
	     
            if (empty($realm)){
               throw new \Exception('A realm must be specified for any calls to resolveEntity with REST_CATEGORY passed as an argument for type');
            }
	        
            $pool = enumerateCategories($realm);
            break;
	        
         case REST_ACTION:
	     
            if (empty($realm)){
               throw new \Exception('A realm must be specified for any calls to resolveEntity with REST_ACTION passed as an argument for type');
            }

            if (empty($category)){
               throw new \Exception('A category must be specified for any calls to resolveEntity with REST_ACTION passed as an argument for type');
            }
	        	        
            $pool = enumerateActions($realm, $category);
            break;
	        
         default:
	     
            throw new \Exception('Unknown REST entity type passed to resolveEntity');
            break;
	        
      }//switch($type)
	  
      // -----------------
	  
      $index = array_search(strtolower($entity), array_map('strtolower', $pool));
   	
      if ($index === false) {
   	
         throw new \Exception("Unable to resolve REST entity '$entity'");
   	
      }
   	
      return $pool[$index];
   	
	}//resolveEntity

	// ----------------------------------------------------------

   function isUniqueRealm ($realmName) {

      try {
         $directory = resolveRealm($realmName);
         return true;
      }
      catch(\Exception $ex) {
         return false;
      }

   }//isUniqueRealm

	// ----------------------------------------------------------

   function resolveRealm ($realmName) {

      // The backend employs a file system which uses a case-sensitive naming scheme.
      // Therefore, it is possible for multiple directories to have the same alphabetical
      // name (due to multiple combinations of 'character case').  This ambiguity makes
      // it difficult to determine which specific directory to use as the implementation
      // for the realm being considered.

      // resolveRealm determines the directory to use as the implementation for the supplied
      // realm name, accounting for ambiguity.

      $directories_to_ignore = array('.svn', '.', '..', 'raw_formats');

      $input_realm_lower = strtolower($realmName);
      $realm_dir = '';

      // -----------------

      if ($dh = opendir(REST_BASE_DIRECTORY)) {

         while (($file = readdir($dh)) !== false) {

            if (filetype(REST_BASE_DIRECTORY.$file) == 'dir' && !in_array($file, $directories_to_ignore)) {

               if ($input_realm_lower == strtolower($file)) {

                  if (empty($realm_dir)){
                     $realm_dir = $file;
                  }
                  else {
                     throw new \Exception("Ambiguity: unable to resolve realm handler for '$realmName'");
                  }

               }

            }

         }//while

         closedir($dh);

      }//if

      if (empty($realm_dir)) {
         throw new \Exception("Unknown realm '$realmName'");
      }

      return $realm_dir;

   }//resolveRealm
         
?>