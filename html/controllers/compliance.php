<?php

	@session_start();
   session_write_close();

   require_once dirname(__FILE__).'/../../configuration/linker.php';

   // Compliance tab is only available to Program Officers and Center Directors
   // Program officers see all resources
   // Center directors see resources only associated with their center
    
   \xd_security\enforceUserRequirements(array(STATUS_LOGGED_IN));

   try {

      $logged_in_user = \xd_security\getLoggedInUser();

      $roles = $logged_in_user->enumAllAvailableRoles();

      $organization_filters = array();

      $has_valid_role = false;

      foreach ($roles as $r) {

         $role_specs = explode(':', $r['param_value'].':');

         if ($role_specs[0] == ROLE_ID_PROGRAM_OFFICER) {
            $has_valid_role = true;
            $organization_filters = array();
            break;
         }

         if ($role_specs[0] == ROLE_ID_CENTER_DIRECTOR || $role_specs[0] == ROLE_ID_CENTER_STAFF) {
            $has_valid_role = true;
            $organization_filters[] = $role_specs[1];
         }

      }//foreach ($roles as $r)

      if ($has_valid_role == false) {
         throw new \Exception('You do not have access to this module');
      }

      $timeframe_mode = isset($_REQUEST['timeframe_mode']) ? $_REQUEST['timeframe_mode'] : 'previous';

      $data = Compliance::generateSnapshot($timeframe_mode, $organization_filters);

      echo json_encode($data);

   }
   catch(\Exception $e) {

      \xd_response\presentError($e->getMessage());

   }

?>