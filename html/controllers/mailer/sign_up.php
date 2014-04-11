<?php

use CCR\DB;

// Operation: mailer->sign_up

$response = array();

\xd_security\assertParametersSet(array(
  'first_name'       => RESTRICTION_FIRST_NAME,
  'last_name'        => RESTRICTION_LAST_NAME,
  'email'            => RESTRICTION_EMAIL,
  'title'            => RESTRICTION_NON_EMPTY,
  'organization'     => RESTRICTION_NON_EMPTY,
  'field_of_science' => RESTRICTION_NON_EMPTY
));

// ----------------------------------------------------------

$captcha_private_key = xd_utilities\getConfiguration('mailer', 'captcha_private_key');

if ($captcha_private_key !== '') {
  if (!isset($_POST["recaptcha_challenge_field"]) || !isset($_POST["recaptcha_response_field"])){
    \xd_response\presentError('Recaptcha information not specified');
  }

  $recaptcha_check = recaptcha_check_answer(
    $captcha_private_key,
    $_SERVER["REMOTE_ADDR"],
    $_POST["recaptcha_challenge_field"],
    $_POST["recaptcha_response_field"]
  );

  if (!$recaptcha_check->is_valid) {
    \xd_response\presentError('You must enter the words in the Recaptcha box properly.');
  };
}

$additional_info = '';

if (isset($_POST['additional_information']) && !empty($_POST['additional_information'])) {
  $additional_info = $_POST['additional_information'];
}

// ----------------------------------------------------------

// Throw account request into database (so it appears in the internal dashboard under 'XDMoD Account Requests')
$pdo = DB::factory('database');

$pdo->execute(
  "INSERT INTO AccountRequests
   (first_name, last_name, organization, title, email_address, field_of_science,
   additional_information, time_submitted, status, comments)
   VALUES (:first_name, :last_name, :organization, :title, :email_address, :field_of_science,
   :additional_information, NOW(), 'new', '')",
  array(
    'first_name'             => $_POST['first_name'],
    'last_name'              => $_POST['last_name'],
    'organization'           => $_POST['organization'],
    'title'                  => $_POST['title'],
    'email_address'          => $_POST['email'],
    'field_of_science'       => $_POST['field_of_science'],
    'additional_information' => $additional_info
  )
);

// ----------------------------------------------------------

$mailer_sender = xd_utilities\getConfiguration('mailer', 'sender_email');

$recipient
  = (xd_utilities\getConfiguration('general', 'debug_mode') == 'on')
  ? xd_utilities\getConfiguration('general', 'debug_recipient')
  : xd_utilities\getConfiguration('general', 'contact_page_recipient');

$subject = "[XDMoD] A visitor has signed up";

$message  = "The following person has signed up for an account on XDMoD:\n\n";
$message .= "Person Details ---------------------------------- \n\n";
$message .= "Name:                     {$_POST['first_name']} {$_POST['last_name']}\n";
$message .= "E-Mail:                   {$_POST['email']}\n";
$message .= "Title:                    {$_POST['title']}\n";
$message .= "Organization:             {$_POST['organization']}\n\n";
//$message .= "Field Of Science:         {$_POST['field_of_science']}\n\n";

$time_requested = date('D, F j, Y \a\t g:i A');

$message .= "Time Account Requested:   $time_requested\n\n";

//$message .= "Desired Username:   {$_POST['desired_username']}\n\n";

if (!empty($additional_info)) {
  $message .= "\nAdditional Information -------------------------- \n\n$additional_info\n";
}

$mail = ZendMailWrapper::init();

//$mail->setFrom($mailer_sender, 'XDMoD');

//Original sender's e-mail must be in the 'From' field for the XDMoD Request Tracker to function
$mail->setFrom($_POST['email']);
$mail->setSubject("[XDMoD] A visitor has signed up");
$mail->addTo($recipient);
$mail->setReplyTo($_POST['email']);
$mail->setBodyText($message);

// -------------------

$status = $mail->send();

// ------------------------

$response['success'] = true;

echo json_encode($response);

