<?php
/**
* Output formatter object that controls output of other objects with sprintf statements
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: outputformatter.php,v 1.11 2006/01/09 01:31:21 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/**
* Output formatter object that controls output of other objects with sprintf statements
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class OutputFormatter {
  /** @var string  sprintf format string for this formatter  */
  var $format;
  /** @var mixed   field name or array of names (keys to $data) to format using this formatter */
  var $formatfields;

 /**
  *  Create a new OutputFormatter object
  *
  * @param string $format  see $this->format
  * @param mixed $fields  string or array, see $this->fields
  */
   function OutputFormatter($format, $fields) {
    $this->format = $format;
    $this->formatfields = $fields;
  }

  /**
  * Format the data
  *
  * @param array $data     data to be formatted, $data[$formatfields[$i]] 
  * @return string formatted data
  */
  function format($data) {
    #echo "Formatting string";
    $fields = is_array($this->formatfields) ? $this->formatfields : array($this->formatfields);
    $s = array();
    foreach ($fields as $v) {
      if (! is_object($v)) {
        if (isset($data[$v])) {
          $val = isset($data[$v]) ? xssqw($data[$v]) : '';
          if ($val !== '' && $val !== NULL) $s[] = $val;
        }
      } else {
        $val = $v->format($data);
        if ($val !== '' && $val !== NULL) $s[] = $val;
      }
    }
    return count($s) ? vsprintf($this->format, $s) : '';
/*    $t = '';
    #preDump($this);
    #preDump($data);
    if (is_array($this->formatfields)) {
      $s = array();
      foreach ($this->formatfields as $v) {
        $s[] = isset($data[$v]) ? xssqw($data[$v]) : '';
        #if (isset($data[$v]) && $data[$v]) {
          #$s = $data[$v];
          #$t .= sprintf($this->format, $s);
        #}
      }
      $t .= vsprintf($this->format, $s);
    } else {
     $s = $this->formatfields->format($data);
      if ($s != '') {
        $t .= sprintf($this->format, xssqw($s));
      }
    }
    return $t;*/
//     $fields = is_array($this->formatfields) ? $this->formatfields : array($this->formatfields);
//     $s = array();
//     foreach ($this->formatfields as $v) {
//       $s[] = isset($data[$v]) ? xssqw($data[$v]) : '';
//     }
//     return vsprintf($this->format, $s);
  }
/*  function format($data) {
    $t = '';
    #preDump($this);
    #preDump($data);
    if (is_array($this->formatfields)) {
      $s = array();
      foreach ($this->formatfields as $v) {
        $s[] = isset($data[$v]) ? xssqw($data[$v]) : '';
        #if (isset($data[$v]) && $data[$v]) {
          #$s = $data[$v];
          #$t .= sprintf($this->format, $s);
        #}
      }
      $t .= vsprintf($this->format, $s);
    } else {
     $s = $this->formatfields->format($data);
      if ($s != '') {
        $t .= sprintf($this->format, xssqw($s));
      }
    }
    return $t;
//     $fields = is_array($this->formatfields) ? $this->formatfields : array($this->formatfields);
//     $s = array();
//     foreach ($this->formatfields as $v) {
//       $s[] = isset($data[$v]) ? xssqw($data[$v]) : '';
//     }
//     return vsprintf($this->format, $s);
  }*/
  
} // class OutputFormatter

?> 
