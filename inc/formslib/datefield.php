<?php
/**
* textfield form widget designed to contain dates
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: datefield.php,v 1.7 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'field.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';
/** Date/Time classes */
require_once 'inc/date.php';

/**
* textfield form widget designed to contain dates
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class DateField extends Field {
  /** @var SimpleDate   date value  */
  var $date;
  /** @var boolean      date can be changed  */
  var $editableOutput=1;

  /**
  *  Create a new datefield object
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function DateField($name, $longname="", $description="") {
    parent::Field($name, $longname, $description);
    //$this->DEBUG=10;
    $this->date = new SimpleDate(0);
  }

  function displayInTable($cols) {
    $errorclass = ($this->isValid ? "" : "class='inputerror'");
    $t = "<tr $errorclass><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    $t .= $this->getdisplay();
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>';
    return $t;
  }

  
  /**
  * Generate the complete html representation of the field
  *
  * @return string html presentation
  */
  function getdisplay() {
    $t = '';
    if ($this->editable && $this->editableOutput && ! $this->hidden) {
      $t .= $this->selectable();
    } else {
      if (!$this->hidden) $t .= xssqw($this->value);
      $t .= $this->hidden();
    }
    if ($this->duplicateName) {
      $t .= "<input type='hidden' name='$this->duplicateName' "
             ."value='".xssqw($this->value)."' />";
    }
    return $t;
  }
  

  
  /**
  * Generate the html representation of the field in a textfield
  *
  * @return string html presentation
  */
  function selectable() {
    $t  = "<input type='text' name='$this->namebase$this->name' "
        ."value='".xssqw($this->date->datestring)."' ";
    $t .= (isset($this->attr['size']) ? "size='".$this->attr['size']."' " : "");
    $t .= (isset($this->attr['maxlength']) ? "maxlength='".$this->attr['maxlength']."' " : "");
    $t .= "/>";
    return $t;
  }
  
  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->value)."' />";
  }

  
  /**
  * Set the date (and value)
  *
  * @param SimpleDate $time 
  */
  function setDate($date) {
    $this->date = new SimpleDate($date);
    $this->value = $this->date->datestring;
//     $this->set($this->time->timestring);
  }
  
  /**
  * overload the parent's update method so that local calculations can be performed
  *
  * @param array $data html_name => value pairs
  *
  * @return boolean the value was updated
  */
  function update($data) {
    if (parent::update($data)) {
      $this->setDate($this->value);
    }
    return $this->changed;
  }

  /**
  *  isValid test (extend Field::isValid), looking at whether the string parsed OK
  * @return boolean data is valid
  */
  function isValid() {
    parent::isValid();
    $this->isValid = $this->isValid && $this->date->isValid;
    return $this->isValid;
  }
    
}

?> 
