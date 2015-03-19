<?php
/**
* database primitive object for an individual field within a row
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: field.php,v 1.31.2.1 2006/05/16 15:05:14 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** type checking and data manipulation */
require_once 'inc/typeinfo.php';
/** type checking and data manipulation */
require_once 'validtester.php';
/** status codes for success/failure of database actions */
require_once 'inc/statuscodes.php';

/**
* Field object that corresponds to one field in a SQL table row.
*
* A number of fields would normally be held together in a DBRow,
* with the DBRow object controlling the updating to the SQL database.
*
* Typical usage is through inheritance, see example for DBRow.
* <code>
*     $f = new TextField("name", "Name");
* </code>
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class Field {
  /** @var string   name of this field */
  var $name;
  /** @var string   label for the field in the display */
  var $longname;
  /** @var string   description for the field in the mouse-over */
  var $description;
  /** @var boolean  field is required */
  var $required = 0;
  /** @var string   value of this field */
  var $value;
  /** @var string   previous value of this field*/
  var $ovalue;
  /** @var string   default value of the field */
  var $defaultValue = '';
  /** @var string   duplicate the value of this field (when hidden) in a hidden field with this name  */
  var $duplicateName;
  /** @var tristate  this field is editable */
  var $editable = -1;
  /** @var string   the value of this field has changed */
  var $changed = 0;
  /** @var string   the field is a hidden widget */
  var $hidden;
  /** @var string   the data contained by the field is valid */
  var $isValid = 1;
  /** @var string   if the ID changes, then notify descendents */
  var $notifyIdChange = 0;
  /** @var tristate   don't to validation on this field */
  var $suppressValidation = -1;
  /** @var boolean  permit NULL values in this field */
  var $useNullValues = 0;
  /** @var array    display attributes */
  var $attr = array();
  /** @var string   CSS class to be used for marking fields with errors */
  var $errorclass = 'error';
  /** @var string   prepended to the name of the field in the html id and hence in the data array */
  var $namebase;
  /** @var string   function to call to check that the data is valid */
  var $isValidTest = 'isset';
  /** @var boolean  don't generate an SQL name=value representation for this field  */
  var $sqlHidden = 0;
  /** @var boolean  this field requires two-stage sync */
  var $requiredTwoStage = 0;
  /** @var string   status code of the descendents of this field (out-of-band data reporting) */
  var $oob_status = STATUS_NOOP;
  /** @var string   error message from this field or its descendents */
  var $oob_errorMessage = '';
  /** @var integer  debug log level */
  var $DEBUG = 0;

  /**
  *  Create a new generic field object, designed to be superclasses
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function Field($name, $longname='', $description='') {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }

  /**
  * update the value of the object with the user-supplied data in $data
  * 
  * $data is most probably from POST data etc.
  *
  * The validity of the data is *not* checked at this stage, the object
  * only takes the user-supplied value.
  *
  * $data is an array with the data relevant to this field being
  * in the key $this->namebase.$this->name. 
  *
  * For example:
  * <code>
  * $this->namebase = 'person-';
  * $this->name     = 'phonenumber';
  * $data['person-phonenumber'] // used for the value
  * </code>
  *
  * @param array $data is name => value pairs as above
  * @return boolean  did the value of this object change?
  */
  function update($data) {
    if (isset($data["$this->namebase$this->name"]) || $this->useNullValues) {
      $newval = issetSet($data, "$this->namebase$this->name");
      $this->log("$this->name, $this->value, $newval ($this->useNullValues)");
      if ($this->editable) {
        // we ignore new values if the field is not editable
        if ($this->changed = ($this->getValue() != $newval)) {
          $this->ovalue = $this->getValue();
          $this->value = $newval;
        }
      } else {
        //ensure that the value is copied in from the default value if
        //it is unset.
        $this->value = $this->getValue();
      }
    } else {
      if ($this->getValue() != $this->value) {
        $this->changed = 0;
        $this->value = $this->getValue();
      }
    }
    $this->log($this->name . ($this->changed ? ' CHANGED' : ' SAME'));
    return $this->changed;
  }

  /**
  * Check the validity of the current data value. 
  * 
  * This also checks the validity of the data even if the data is not newly-entered.
  * Returns true if the specified validity tests are passed:
  *  -   is the field required to be filled in && is it filled in?
  *  -   is there a validity test && is the data valid?
  *
  * @return boolean  is the data value valid
  */
  function isValid() {
     $this->isValid = 1;
    if ($this->required) {
      #$this->isValid = (isset($this->value) && $this->value != "");
      $this->isValid = ($this->getValue() != '');
      $this->log($this->name . ' Required: '.($this->isValid ? ' VALID' : ' INVALID'));
    }
    if ($this->isValid && $this->suppressValidation == 0) {
      $this->isValid = ValidTester($this->isValidTest, $this->getValue(), $this->DEBUG);
    }
    $this->log($this->name . ($this->isValid ? ' VALID' : ' INVALID'));
    return $this->isValid;
  }

  /**
  * set the value of this field 
  *
  * <b>without</b> validation or checking to see whether the field has changed.
  * 
  * @param string   the new value for this field
  */
  function set($value) {
    //echo "Updating field $this->name. New value=$value\n";
    $this->value = $value;
  }

  /**
  * create an SQL-injection-cleansed string for db statements
  *
  * Generates a string that represents this field that can be used in an SQL
  * UPDATE or INSERT statement. i.e. "name='Stuart'".
  *
  * @param string optional SQL name to use to change the default
  * @return string  in SQL assignable form
  */
  function sqlSetStr($name='') {
    if (! $this->sqlHidden) {
      if (empty($name)) {
        $name = $this->name;
      }
      return $name .'='. qw($this->getValue());
    } else {
      return '';
    }
  }

  /**
  * Set display attributes for the field.
  *
  * the attribute fielde are parsed differently for each different field subclass
  *
  * @param array $attrs attribute_name => value
  * @access public
  */
  function setattr($attrs) {
    $this->attr = array_merge($this->attr, $attrs);
  }

  /** 
  * Quick and dirty display of the field status
  *
  * @return string simple text representation of the class's value and attributes
  */
  function text_dump() {
    $t  = "$this->name =&gt; ".$this->getValue();
    $t .= ($this->editable ? "(editable)" : "(read-only)");
    $t .= ($this->isValid ? "" : "(invalid)");
    $t .= "\n";
    return $t;
  }

  /** 
  * Generic display function
  */
  function display() {
    return $this->text_dump();
  }

  /** 
  * html representation of this field as a "hidden" form widget
  */
  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->getValue())."' />";
  }

  /** 
  * render this form widget in an html table
  *
  * @param integer $cols  number of columns to be included in table (padding cols will be added)
  * @abstract
  */
  function displayInTable($cols=3) {
  }

  /** 
  * return the current value as text and the widget as a hidden form element
  *
  * @return string current value
  * @abstract
  */
  function selectedValue() {
  }
  
  /** 
  * return an html representation of the widget
  *
  * @return string html widget
  * @abstract
  */
  function selectable() {
  }

  /**
  * Obtain the value of the field, taking account of default values
  *
  * @return mixed field value
  */
  function getValue() {
    //echo "FIELD $this->name: ".$this->value.":".$this->defaultValue."<br />";
    return (isset($this->value) ? $this->value : $this->defaultValue);
  }

  /**
  * set whether this field is editable or not 
  *
  * @param boolean $editable  new editable state
  */
  function setEditable($editable=1) {
    $this->editable = $editable;
  }
  
  /**
  * set the namebase for the data storage in the html form
  *
  * @param boolean $editable  new editable state
  */
  function setNamebase($namebase='') {
    $this->namebase = $namebase;
  }
  
  /**
  * Generic logging function
  *
  * @param string $logstring   data to log
  * @param integer $priority   log level for this message
  */
  function log($logstring, $priority=10) {
    if ($priority <= $this->DEBUG) {
      echo $logstring."<br />\n";
    }
  }
  
} // class Field

?> 
