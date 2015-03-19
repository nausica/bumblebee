<?php
/**
* a radio button list based on the ChoiceList class
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: radiolist.php,v 1.14 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'choicelist.php';

/**
* a radio button list based on the ChoiceList class
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class RadioList extends ChoiceList {
  /** @var string   html/css class name to use in the generated output */
  var $radioclass = 'item';

  /**
  *  Create a new radiolist object
  *
  * @param string $name         the name of the field (db name, and html field name
  * @param string $description  long name to be used in the label of the field in display
  */
  function RadioList($name, $description='') {
    //$this->DEBUG = 10;
    $this->ChoiceList($name, $description);
  }

  function display() {
    return $this->selectable();
  }

  function format($data) {
    //$aclass  = (isset($this->aclass) ? " class='$this->aclass'" : "");
    #echo "<pre>".print_r($data,1)."</pre>";
    #echo $this->value;
    $selected = ($data[$this->formatid] == $this->getValue() ? ' checked="1" ' : '');
    $t  = '<label><input type="radio" name="'.$this->name.'" '  
         .'value="'.$data[$this->formatid].'" '.$selected.' /> ';
    foreach (array_keys($this->formatter) as $k) {
      $t .= $this->formatter[$k]->format($data);
    }
    $t .= '</label>';
    if (isset($data['_field']) && $data['_field']) {
      $t .= $data['_field']->selectable();
    }
    return $t;
  }


  function selectable() {
    $t = '';
    foreach ($this->list->choicelist as $v) {
      $t .= $this->format($v);
      $t .= "<br />\n";
    }
    return $t;
  }

} // class RadioList


?> 
