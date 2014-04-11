<?php

// Operation: mailer->contact

$response = array();

\xd_security\assertParametersSet(array(
  'name'              => RESTRICTION_FIRST_NAME,
  'email'             => RESTRICTION_EMAIL,
  'message'           => RESTRICTION_NON_EMPTY,
  'username'          => RESTRICTION_NON_EMPTY,
  'token'             => RESTRICTION_NON_EMPTY,
  'timestamp'         => RESTRICTION_NON_EMPTY
));

$user_info = ($_POST['username'] == '__public__') ? 'Public Visitor' : "Username:     ".$_POST['username'];

// ----------------------------------------------------------

$captcha_private_key = xd_utilities\getConfiguration('mailer', 'captcha_private_key');

if ($captcha_private_key !== '' && !isset($_SESSION['xdUser'])) {

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

// ----------------------------------------------------------

$mailer_sender = xd_utilities\getConfiguration('mailer', 'sender_email');

$recipient
  = (xd_utilities\getConfiguration('general', 'debug_mode') == 'on')
  ? xd_utilities\getConfiguration('general', 'debug_recipient')
  : xd_utilities\getConfiguration('general', 'contact_page_recipient');

$mail = ZendMailWrapper::init();

$subject = "[XDMoD] Message sent from a portal visitor";

$mail->setSubject($subject);
$mail->addTo($recipient);

//$mail->setFrom($mailer_sender, 'XDMoD');

//Original sender's e-mail must be in the 'From' field for the XDMoD Request Tracker to function
$mail->setFrom($_POST['email']);
$mail->setReplyTo($_POST['email']);

$timestamp = date('m/d/Y, g:i:s A', $_POST['timestamp']);

$message = "Below is a message from '{$_POST['name']}' ({$_POST['email']}):\n\n";
$message .= $_POST['message'];
$message .="\n------------------------\n\nSession Tracking Data:\n\n  ";
$message .="$user_info\n\n  Token:        {$_POST['token']}\n  Timestamp:    $timestamp";

$mail->setBodyText($message);
$status = $mail->send();

// =====================================================

$mail = ZendMailWrapper::init();
$mail->setFrom($mailer_sender, 'XDMoD');
$mail->setSubject("Thank you for your message.");
$mail->addTo($_POST['email']);

// -------------------

$message
    = "Hello, {$_POST['name']}\n\n"
    . "This e-mail is to inform you that the XDMoD Portal Team has received your message, and will\n"
    . "be in touch with you as soon as possible.\n\n"
    . "The TAS Project Team\n"
    . "Center for Computational Research\n"
    . "University at Buffalo, SUNY\n";

$mail->setBodyText($message);

// -------------------

$status = $mail->send();

// =====================================================

$response['success'] = true;

echo json_encode($response);

