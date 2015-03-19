<?php
/**
* the textfield widget primitive
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: textfield.php,v 1.17 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'field.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';

/**
* The textfield widget primitive
*
* Designed for strings to be edited in a text field widget in the HTML form, 
* but is inherited for TimeField, IdField etc
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class TextField extends Field {

  /**
  *  Create a new field object, designed to be superclasses
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function TextField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
  }

  function displayInTable($cols) {
    $t = '';
    if (! $this->hidden) {
      $errorclass = ($this->isValid ? '' : 'class="inputerror"');
      $t .= "<tr $errorclass><td>$this->longname</td>\n"
          ."<td title='$this->description'>";
      if ($this->editable) {
        $t .= $this->selectable();
        } else {
        $t .= $this->selectedValue();
      }
      if ($this->duplicateName) {
        $t .= '<input type="hidden" name="'.$this->duplicateName.'" '
              .'value="'.xssqw($this->getValue()).'" />';
      }
      $t .= "</td>\n";
      for ($i=0; $i<$cols-2; $i++) {
        $t .= "<td></td>";
      }
      $t .= "</tr>";
    } else {
      $t .= $this->hidden();
    }
    return $t;
  }

  function selectedValue() {
    return xssqw($this->getValue()).$this->hidden();
  }
  
  function selectable() {
    $t  = '<input type="text" name="'.$this->namebase.$this->name.'" ';
    $t .= 'title="'.$this->description.'" ';
    $t .= 'value="'.xssqw($this->getValue()).'" ';
    $t .= (isset($this->attr['size']) ? 'size="'.$this->attr['size'].'" ' : '');
    $t .= (isset($this->attr['maxlength']) ? 'maxlength="'.$this->attr['maxlength'].'" ' : '');
    $t .= '/>';
    return $t;
  }
  
  function hidden() {
    return "<input type='hidden' name='$this->namebase$this->name' "
           ."value='".xssqw($this->getValue())."' />";
  }

} // class TextField


?> 
