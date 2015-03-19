<?php
/**
* reflect all submitted data back to the user
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: datareflector.php,v 1.3 2005/11/25 12:51:55 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/**
* Reflects all inputted data through hidden fields 
*
* Can optionally exclude some fields
*
* Typical usage:<code>
*     $ref = new DataReflector();
*     $ref->exclude(array('id', 'name'));
*     $ref->excludeRegEx(array('/^setting-.+/'));
*     echo $ref->display($_POST);
* </code>
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class DataReflector {
  /** @var array   list of fields to exclude from the datareflector */ 
  var $excludes = array();
  /** @var string  list of regexp fields to exclude from the datareflector  */ 
  var $excludesRegEx = array();
  /** @var integer   debug level    */ 
  var $DEBUG = 0;

  /**
  *  Create a datareflector object
  */
  function DataReflector() {
  }
  
  /**
  *  Creates hidden fields html representation
  *
  * @param array $PD  array of $field => $value 
  * @return string  html hidden fields
  */
  function display($PD) {
    $t = '';
    foreach ($PD as $key => $val) {
      if (in_array($key, $this->excludes)) {
        break;
      }
      foreach ($this->excludesRegEx as $re) {
        if (preg_match($re, $key)) {
          break(2);
        }
      }
      // if we got this far then we should be included.
      $t .= '<input type="hidden" name="'.$key.'" value="'.xssqw($val).'" />';
    }
    return $t;
  }

  /**
  *  Exclude these fields from the reflection
  *
  * @param array $arr list of fields to exclude
  */
  function exclude($arr) {
    $this->excludes = $arr;
  }
  
  /**
  *  Exclude the fields that match these regexps from the reflection
  *
  * @param array $arr list of regexps to use for exclusion
  */
  function excludeRegEx($arr) {
    $this->excludesRegEx = $arr;
  }
  
} // class DataReflector

?> 
