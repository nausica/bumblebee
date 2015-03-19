<?php
/**
* a dropdown selection list using a ChoiceList
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: droplist.php,v 1.14 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'choicelist.php';

/**
* a dropdown selection list using a ChoiceList
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class DropList extends ChoiceList {

  /**
  *  Create a new dropdown list object
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $description  used in the html title or longdesc for the field
  */
  function DropList($name, $description='') {
    //$this->DEBUG=10;
    $this->ChoiceList($name, $description);
    $this->extendable = 0;
  }

  function display() {
    return $this->selectable();
  }

  function format($data) {
    //preDump($this->formatid);
    //preDump($data);
    $data['_field'] = '0';
    $selected = ($data[$this->formatid] == $this->getValue() ? " selected='1' " : '');
    $t  = '<option '
         ."value='".$data[$this->formatid]."' $selected> ";
    foreach (array_keys($this->formatter) as $k) {
      $t .= $this->formatter[$k]->format($data);
    }
    //if (isset($data['_field']) && $data['_field']) {
    //  echo 'foo'.$data['_field'].'bar';
    //  $t .= $data['_field']->selectable();
    //}
    $t .= "</option>\n";
    return $t;
  }


  function selectable() {
    $t = "<select name='$this->namebase$this->name'>";
    foreach ($this->list->choicelist as $v) {
//       echo "droplist: $k => $v<br />\n";
      //preDump($v);
      $t .= $this->format($v);
    }
    $t .= "</select>";
    return $t;
  }

  function selectedValue() {
    $value = $this->getValue();
    foreach ($this->list->choicelist as $data) {
      if ($data[$this->formatid] == $value) {
        break;
      }
    }
    //preDump($data);
    $t  = '<input type="hidden" '
          .'value="'.$data[$this->formatid].'" /> ';
    foreach (array_keys($this->formatter) as $k) {
      $t .= $this->formatter[$k]->format($data);
    }
    $t .= "\n";
    return $t;
  }
  
} // class DropList


?> 
