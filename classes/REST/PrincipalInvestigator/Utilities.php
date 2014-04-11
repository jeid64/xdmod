<?php

namespace PrincipalInvestigator;

class Utilities extends \aRestAction
{

   // --------------------------------------------------------------------------------
   // @see aRestAction::__call()
   // --------------------------------------------------------------------------------

   public function __call($target, $arguments)
   {
         
      // Verify that the target method exists and call it.

      $method = $target . ucfirst($this->_operation);
    
      if ( ! method_exists($this, $method) )
      {
      
         if ($this->_operation == 'Help') {
           
            // The help method for this action does not exist, so attempt to generate a response
            // using that action's Documentation() method
            
            $documentationMethod = $target.'Documentation';
            
            if ( ! method_exists($this, $documentationMethod) ) {
               throw new \Exception("Help cannot be found for action '$target'");
            }
            
            return $this->$documentationMethod()->getRESTResponse();            
         
         }
         else if($this->_operation == "ArgumentSchema") {
         
            $schemaMethod = $target.'ArgumentSchema';
         
            if ( ! method_exists($this, $schemaMethod) ) {
               throw new \Exception("Argument schema information cannot be found for action '$target'");
            }        
         
            return $this->$schemaMethod(); 
                     
         }
         else {
            throw new \Exception("Unknown action '$target' in category '" . strtolower(__CLASS__)."'");
         }
         
      }
         
      return $this->$method($arguments);
  
   }//__call

   // --------------------------------------------------------------------------------
   // @see aRestAction::factory()
   // --------------------------------------------------------------------------------

   public static function factory($request)
   {
      return new Utilities($request); 
   }


   // ACTION: enumerateAction ================================================================================
      
   private function enumerateAction()
   {                  
            
      $authDetails = $this->_requireKeyOrToken();
            
      $pi = new \PrincipalInvestigator();
         
      $principal_investigators = $pi->enumerate();
          
      $response = array(
         'success' => true,
         'results' => $principal_investigators
      );  
      
      return $response;
      
   }//enumerateAction

   // -----------------------------------------------------------

   private function enumerateDocumentation()
   {
      
      $documentation = new \RestDocumentation();
      
      $documentation->setDescription('Retrieve a listing of all principal investigators');
       
      $documentation->setAuthenticationRequirement(true);
      
      $documentation->setOutputFormatDescription('An array of records, each having the following components:');
      
      $documentation->addReturnElement("id", "The unique identifier associated with this principal investigator");
      $documentation->addReturnElement("full_name", "The principal investigator's full name");   

      $documentation->addReturnElement("organization", "The organization (or university) associated with the principal investigator");  
      $documentation->addReturnElement("department", "The department within the organization associated with the principal investigator");
                        
      return $documentation;
   
   }//enumerateDocumentation


   // ACTION: getUserDetails ================================================================================
      
   private function getUserDetailsAction()
   {
                  
      $authDetails = $this->_requireKeyOrToken();
      
      $actionParams = $this->_parseRestArguments('user_id');
                            
      $pi = new \PrincipalInvestigator();
         
      $result_data = $pi->getDetails($actionParams['user_id']);
             
      $response = array(
         'success' => true,
         'results' => $result_data
      );  
      
      return $response;
      
   }//getUserDetailsAction

   // -----------------------------------------------------------

   private function getUserDetailsDocumentation()
   {
      
      $documentation = new \RestDocumentation();
      
      $documentation->setDescription('Get the details of a given user, referenced by id');
       
      $documentation->setAuthenticationRequirement(true);
      
      $documentation->addArgument('user_id', 'A user id');
             
      //$documentation->setOutputFormatDescription('An array of records, each having the following components:');
      
      $documentation->addReturnElement("id", "The id associated with the user");
      $documentation->addReturnElement("name", "The name of the user (full, first, middle, last)");   
      $documentation->addReturnElement("organization", "The organization (or university) associated with the user");
      $documentation->addReturnElement("department", "The department within the organization associated with the user");   

      $documentation->addReturnElement("status", "Description of the NSF status code for this user");  
      $documentation->addReturnElement("governed_allocations", "Lists of active and expired allocations this user is a PI of"); 
      $documentation->addReturnElement("other_allocations", "Lists of active and expired allocations this user is NOT a PI of, though is a member of");       
      $documentation->addReturnElement("resources_of_active_governed_allocations", "A listing of resources associated with all active allocations this user is a PI of");  
                              
      return $documentation;
   
   }//getUserDetailsDocumentation
   
   
   // ACTION: getAllocationDetails ================================================================================
      
   private function getAllocationDetailsAction()
   {

      $authDetails = $this->_requireKeyOrToken();
                        
      $actionParams = $this->_parseRestArguments('allocation_id');
                            
      $pi = new \PrincipalInvestigator();
         
      $result_data = $pi->getAllocationDetails($actionParams['allocation_id']);
             
      $response = array(
         'success' => true,
         'results' => $result_data
      );  
      
      return $response;
      
   }//getAllocationDetailsAction

   // -----------------------------------------------------------

   private function getAllocationDetailsDocumentation()
   {
      
      $documentation = new \RestDocumentation();
      
      $documentation->setDescription('Get the details of a given allocation, referenced by id');
       
      $documentation->setAuthenticationRequirement(true);
      
      $documentation->addArgument('allocation_id', 'An allocation id');
                   
      //$documentation->setOutputFormatDescription('An array of records, each having the following components:');
      
      $documentation->addReturnElement("allocation_id", "The numeric identifier for the allocation");
      $documentation->addReturnElement("jobs_under_allocation", "A count of jobs associated with the allocation");   
      $documentation->addReturnElement("charge_number", "The charge number (XD project number) associated with the allocation");
      $documentation->addReturnElement("status", "The state of the allocation (active, expired)");   

      $documentation->addReturnElement("su_details", "Breakdown of how serviceable units for this allocation have been utilized (base, consumed, remaining)"); 
      $documentation->addReturnElement("lifetime", "The creation and expiration dates of the allocation"); 
      $documentation->addReturnElement("associated_projects", "Projects associated with the allocation, represented by name and grant number");
      $documentation->addReturnElement("resources", "A listing of resources associated with the allocation (including allocation stats)"); 
      $documentation->addReturnElement("members", "A listing of users which can make use of the allocation (along with job contribution and allocation quotas)"); 
                                    
      return $documentation;
   
   }//getAllocationDetailsDocumentation
  
  }// class Utilities

?>
