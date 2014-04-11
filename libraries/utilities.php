<?php

namespace xd_utilities;

// Shared INI data.
$iniData = null;

// --------------------------------------------------------------------------------
// Parse the configuration file information for the requested section and
// option.  Note that the configuration information is cached the first time
// that this function is called unless the $cache is set to FALSE.
//
// @param $section Desired configuration section
// @param $option Desired option within the section
// @param $useCachedOptions Cache the parsed options file after the first
//   call to this function.  Set to TRUE by default.  Setting this to FALSE
//   will cause the file to be parsed again.
//
// @throws Exception if the section or option is not provided
// @throws Exception if the configuration file could not be parsed
// @throws Exception if the requested section is not found in the file
// @throws Exception if the requested option is not found in the section
//
// @returns The requested configuration option.
// --------------------------------------------------------------------------------

function getConfiguration($section, $option, $useCachedOptions = TRUE)
{
  global $iniData;

  if (empty($section) || empty($option)) {
    $msg = "Configuration section or option not specified";
    throw new Exception($msg);
  }

  // Parse the file and cache it, verifying that the section and options
  // exist.

  if (NULL === $iniData || ! $useCachedOptions) {
    if (!is_readable(CONFIG_PORTAL_SETTINGS)) {
      $msg = "Could not read settings file: " . CONFIG_PORTAL_SETTINGS;
      throw new \Exception($msg);
    }
    $iniData = parse_ini_file(CONFIG_PORTAL_SETTINGS, TRUE);
  }  // if ( NULL === $iniData || ! $useCachedOptions )

  if (!array_key_exists($section, $iniData)) {
    $msg = "Undefined configuration section: '$section'";
    throw new \Exception($msg);
  }

  if (!array_key_exists($option, $iniData[$section])) {
    $msg = "Option '$option' does not exist in section '$section'";
    throw new \Exception($msg);
  }

  return $iniData[$section][$option];
}  // getConfiguration()

// same as getConfiguration however it returns the whole section as
// an associative array
//
function getConfigurationSection($section, $useCachedOptions = TRUE)
{
  global $iniData;

  if (empty($section)) {
    $msg = "Configuration section not specified";
    throw new Exception($msg);
  }

  // Parse the file and cache it, verifying that the section and options
  // exist.

  if (NULL === $iniData || ! $useCachedOptions) {
    if (!is_readable(CONFIG_PORTAL_SETTINGS)) {
      $msg = "Could not read settings file: " . CONFIG_PORTAL_SETTINGS;
      throw new \Exception($msg);
    }
    $iniData = parse_ini_file(CONFIG_PORTAL_SETTINGS, TRUE);
  }  // if ( NULL === $iniData || ! $useCachedOptions )

  if (!array_key_exists($section, $iniData)) {
    $msg = "Undefined configuration section: '$section'";
    throw new \Exception($msg);
  }

  return $iniData[$section];
}  // getConfigurationSection()

function clearConfigurationCache()
{
  global $iniData;
  $iniData = null;
}

function getConfiguration_old($section, $field)
{
  $config_data = file(dirname(__FILE__).'/../configuration/portal_settings.ini');

  $entry_point_reached = false;

  foreach ($config_data as $line) {
    $line = chop($line);

    if (preg_match('/^\[(.+)\]$/', $line, $matches) == 1) {
      $entry_point_reached = ($matches[1] == $section);
      continue;
    }

    if ($entry_point_reached) {
      if (preg_match("/^$field = \'(.+)\'$/", $line, $matches) == 1) {
        return $matches[1];
      }
    }
  }

 return "";
}//getConfiguration

// --------------------------------

function quote($entity)
{
  return "'$entity'";
}//quote

// --------------------------------

function tokenResolver($input)
{
  $user = \xd_security\getLoggedInUser();

  $mappings = array(
    "username" => $user->getUsername()
    //"date_range" => $_SESSION[$_SESSION['currentTab'].'_formal_date_selected']
  );

  $output = $input;

  foreach ($mappings as $find => $replace) {
    $output = str_replace("<$find>", $replace, $output);
  }

  $output = mysql_escape_string($output);

  return $output;
}//tokenResolver

// --------------------------------

function remove_element_by_value(&$array, $value)
{
  $index = array_search($value, $array);
  if (!is_bool($index)) unset($array[$index]);
}//remove_element_by_value

// --------------------------------

function power_cube($arr, $minLength = 1)
{
  $pp = power_set($arr, $minLength);

  /*
  foreach ($pp as $key => $value) {
    echo implode(",", $value). "\n";
  }
  echo "\n";
   */

  foreach ($pp as $key => $value) {
    if (count($value) <= 0) { continue; }
    $pp_copy = $pp;
    unset($pp_copy[$key]);

    $value_string = implode(",",$value);

    foreach ($pp_copy as $pp_copy_el) {
      $el_value = implode(",",$pp_copy_el);
      if (string_begins_with($el_value, $value_string)) {
        unset($pp[$key]);
        //echo "removing $key $value_string: subset of $el_value";
        //echo "\n";
        break;
      }
    }
  }

  /*
  foreach ($pp as $key => $value) {
    echo implode(",", $value). "\n";
  }
  echo "\n";
  */

  return $pp;
}

function string_begins_with($string, $search)
{
 return (strncmp($string, $search, strlen($search)) == 0);
}


function power_perms($arr, $minLength = 1)
{
  $power_set = power_set($arr, $minLength);
  $result = array();
  foreach ($power_set as $set) {
    $perms = perms($set);
    $result = array_merge($result,$perms);
  }
  return $result;
}

function power_set($in,$minLength = 1)
{
  $count = count($in);
  $members = pow(2,$count);
  $return = array();
  for ($i = 0; $i < $members; $i++) {
    $b = sprintf("%0".$count."b",$i);
    $out = array();
    for ($j = 0; $j < $count; $j++) {
      if ($b{$j} == '1') { $out[] = $in[$j]; }
    }
    if (count($out) >= $minLength) {
      $return[] = $out;
    }
  }

  //usort($return,"cmp");  //can sort here by length
  return $return;
}

function factorial($int)
{
  if ($int < 2) {
    return 1;
  }

  for ($f = 2; $int-1 > 1; $f *= $int--);

  return $f;
}

function perm($arr, $nth = null)
{
  if ($nth === null) {
    return perms($arr);
  }

  $result = array();
  $length = count($arr);

  while ($length--) {
    $f = factorial($length);
    $p = floor($nth / $f);
    $result[] = $arr[$p];
    array_delete_by_key($arr, $p);
    $nth -= $p * $f;
  }

  $result = array_merge($result, $arr);

  return $result;
}

function perms($arr)
{
  $p = array();

  for ($i = 0; $i < factorial(count($arr)); $i++) {
    $p[] = perm($arr, $i);
  }

  return $p;
}

function array_delete_by_key(&$array, $delete_key, $use_old_keys = FALSE)
{
  unset($array[$delete_key]);

  if (!$use_old_keys) {
    $array = array_values($array);
  }

  return TRUE;
}

/*
   @function getParameterIn
   Locates a value for a parameter ($param) in a string ($haystack) with
   the format  /param1=value/param2=value/.…
   or param1=value&param2=value&…

   If no match is found, an empty string is returned
*/

function getParameterIn($param, $haystack)
{
  $num_matches = preg_match("/$param=(.+)/", $haystack, $matches);

  $param_value = '';

  if ($num_matches > 0) {
    $frags = explode('&', str_replace('/', '&', $matches[1]));
    $param_value = $frags[0];
  }

  return $param_value;
}//getParameterIn


// --------------------------------------------------------------------------------
// Create an XML error message
//
// @param $dom Document object model that the error will be inserted into
// @param $nodeRoot Root of the error node
// @param $code Error code
// @param $message Error message
//
// @returns TRUE if successful
// --------------------------------------------------------------------------------

function generateError($dom, $nodeRoot, $code, $message)
{
  \xd_domdocument\createElement($dom, $nodeRoot, "code",  $code);
  \xd_domdocument\createElement($dom, $nodeRoot, "reason",  $message);

  return TRUE;
}  // generate_error()


function printAndDelete($message)
{
  $message_length = strlen($message);

  print ($message);
  print (str_repeat(chr(8) , $message_length));

  return $message_length;
}

// --------------------------------------------------------------------------------

function checkForCenterLogo($apply_css = true)
{
  $use_center_logo = false;

  try {
    $logo = getConfiguration('general', 'center_logo');
    $logo_width = getConfiguration('general', 'center_logo_width');

    $logo_width = intval($logo_width);

    if (strlen($logo) > 0 && $logo[0] !== '/') {
      $logo = dirname(__FILE__).'/'.$logo;
    }

    if (file_exists($logo)) {
      $use_center_logo = true;
      $img_data = base64_encode(file_get_contents($logo));
    }
  } catch(\Exception $e) {
  }

  if ($use_center_logo == true && $apply_css == true) {
print <<<EOF
   <style type="text/css">
      .custom_center_logo {
         height: 25px;
         width: {$logo_width}px;
         background: url(data:image/png;base64,$img_data) right no-repeat;
      }
   </style>
EOF;
  }//if ($use_center_logo == true && $apply_css == true)

  return $use_center_logo;
}//checkForCenterLogo

