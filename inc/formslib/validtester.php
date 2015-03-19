<?php
/**
* test the validity of data according to a set of rules
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: validtester.php,v 1.9 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** type checking and data manipulation */
require_once 'inc/typeinfo.php';

/**
* check if data is valid
*
* @param string $validator   name of a function to call
* @param mixed  $data        data to be validated (string, number, array etc)
* @param integer $DEBUG      (optional) debug level for extra data output
* @return boolean data is valid
*/
function ValidTester($validator, $data, $DEBUG=0) {
  //global $VERBOSEDATA;
  $isValid = 1;
  if (isset($validator) && is_callable($validator)) {
    $isValid = $validator($data);
  } 
  if ($DEBUG > 9) echo "[$data, $validator, $isValid]";
  return $isValid;
}

?> 
