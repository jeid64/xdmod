<?php

	namespace xd_response;

	// ----------------------------------------------------------
		
   /*
      @function presentError
      -- The argument passed in can either be a string (message) or an Exception
      -- If an Exception is passed in, the respective message and code are returned in the $response array
   */
   
   function presentError($message_or_exception) {

      $response = array();

      if ($message_or_exception instanceof \Exception) {
      
         $response['message'] = $message_or_exception->getMessage();
         $response['code'] = $message_or_exception->getCode();
         
      }
      else {
      
         $response['message'] = $message_or_exception;
         
      }
      
      $response['success'] = false;
      $response['results'] = array();
      $response['count'] = 0;
      $response['total'] = 0;
      $response['totalCount'] = 0;
      $response['data'] = array();

      echo json_encode($response);
      exit;
         
   }//presentError
	
?>