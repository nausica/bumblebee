<?php
/**
* a non-editable reference object to explain a table entry
*
* e.g. translates the userid into the user's real name.
*
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: referencefield.php,v 1.6 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'field.php';
/** uses ExampleEntries object */
require_once 'exampleentries.php';
/** type checking and data manipulation */
require_once 'inc/typeinfo.php';

/**
* a non-editable reference object to explain a table entry
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class ReferenceField extends Field {
   /** @var ExampleEntries   list of example entries from the db */
   var $example;

  /**
  *  Create a new reference field object
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function ReferenceField($name, $longname='', $description='') {
    parent::Field($name, $longname, $description);
  }

  /**
  * add an extra field to the end of the formatted data
  *
  * @param string $table       db table for extra field
  * @param string $matchfield  id/key field 
  * @param string $field       field to return in the list of examples
  * @param integer $numentries (optional) the number of entries to return
  */
  function extraInfo($table, $matchfield, $field, $numentries=1) {
    $this->example = new ExampleEntries('id', $table, $matchfield, $field, $numentries);
  }

  function displayInTable($cols) {
    $t = "<tr><td>$this->longname</td>\n"
        ."<td title='$this->description'>";
    $t .= xssqw($this->getValue());
    $refdata = array('id'=>$this->getValue());
    $t .= ' ('. $this->example->format($refdata).')';
    $t .= "<input type='hidden' name='$this->namebase$this->name' "
         ."value='".xssqw($this->getValue())."' />";
    if (isset($this->duplicateName)) {
      $t .= "<input type='hidden' name='$this->duplicateName' "
           ."value='".xssqw($this->getValue())."' />";
    }
    $t .= "</td>\n";
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>';
    return $t;
  }

} // class ReferenceField


?> 
