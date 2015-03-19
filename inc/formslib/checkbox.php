<?php
/**
* a checkbox object
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: checkbox.php,v 1.8 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** field parent object */
require_once 'field.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';

/**
* a checkbox object
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class CheckBox extends Field {

  /**
  *  Create a new checkbox object
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function CheckBox($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
    $this->useNullValues = 1;
  }

  function update($data) {
    if (parent::update($data)) {
      $this->log("CHECKBOX $this->name: $this->value, $this->ovalue");
      $this->value = ($this->value ? 1 : 0);
      $this->ovalue = ($this->ovalue ? 1 : 0);
      $this->log("CHECKBOX $this->name: $this->value, $this->ovalue");
      $this->changed = ($this->value != $this->ovalue);
    }
    return $this->changed;
  }

  function displayInTable($cols) {
    $errorclass = ($this->isValid ? '' : "class='inputerror'");
    $t = "<tr $errorclass><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    if ($this->editable) {
      $t .= $this->selectable();
    } else {
      $t .= xssqw($this->value);
      $t .= "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->value)."' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>';
    return $t;
  }

  function selectable() {
    $t  = "<input type='checkbox' name='$this->namebase$this->name' "
         ."value='1' ";
    $t .= (($this->getValue()) ? 'checked="1"' : '');
    $t .= '/>';
    return $t;
  }

} // class CheckBox

?> 
