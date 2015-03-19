<?php
/**
* a choice list like DBChoiceList *not* based on an SQL statement
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: arraychoicelist.php,v 1.6 2005/12/01 07:37:54 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/**
* Provide a non-database filled (i.e. array-filled) version of DBChoiceList
*
* See DBChoiceList for more details of interface.
* @see DBChoiceList
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class ArrayChoiceList {
  /** @var string  id value currently selected  */
  var $id=-1;
  /** @var boolean  list is editable  */
  var $editable = 0;
  /** @var boolean  list is extensible by user */
  var $extendable = 0;
  /** @var boolean  list has changed */
  var $changed = 0;
  /** @var array    list of array($listKey => $unique_name, $displayKey => $displayed_value) */
  var $choicelist;
  /** @var integer  number of items in the list */
  var $length;
  /** @var string   array index in which to store the unique_name */
  var $listKey;
  /** @var string   array index in which to store the displayed_value */
  var $displayKey;
  /** @var integer  status code for out-of-band error reporting  */
  var $oob_status = STATUS_NOOP;
  /** @var string   error message for out-of-band error reporting  */
  var $oob_errorMessage = '';

  /** @var integer  debug level for object  0=off.  */
  var $DEBUG = 0;
  
  /**
  *  Create a new ArrayChoiceList object
  *
  * @param array $array  list of unique_name => displayed_value 
  * @param string $iv    index in which the unique_name should be stored in the ChoiceList
  * @param string $dv    index in which the displayed_value should be stored in the ChoiceList
  */
  function ArrayChoiceList($array, $iv, $dv) {
    $this->choicelist = array();
    $this->listKey = $iv;
    $this->displayKey = $dv;
    $item=0;
    foreach ($array as $internalVal => $displayVal) {
      $this->choicelist[$item] = array();
      $this->choicelist[$item][$this->listKey] = $internalVal;
      $this->choicelist[$item][$this->displayKey] = $displayVal;
      $this->choicelist[$item]['_field'] = 0;
      $item++;
    }
  }
  
  /**
   * Construct an array suitable for storing the field and the values it takes for later reuse
   * @param array $values list of values to be displayed
   * @param Field $field  Field object associated with these values
   */
  function _mkaddedarray($values, $field='') {
    $a = array();
    $a[$this->listKey] = $values[0];
    $a[$this->displayKey] = $values[1];
    $a['_field'] = $field;
    return $a;
  }

  /**
  * append a special field (such as "Create new:") to the choicelist
  *
  * This method is much simpler than the DB equivalent as we won't need to remember them.
  *
  * @see DBChoiceList::append()
  * @param array $values list of values to be displayed
  * @param Field $field (optional) a field class object to be placed next to this entry, if possible
  */
  function append($values, $field='') {
    $fa = $this->_mkaddedarray($values, $field);
    array_push($this->choicelist, $fa);
  }

  /**
  * prepend a special field (such as "Create new:") to the choicelist
  *
  * This method is much simpler than the DB equivalent as we won't need to remember them.
  *
  * @param array $values list of values to be displayed
  * @param Field $field (optional) a field class object to be placed next to this entry, if possible
  */
  function prepend($values, $field='') {
    $fa = $this->_mkaddedarray($values, $field);
    array_unshift($this->choicelist, $fa);
  }

  /**
  * display the contents of the list
  * @return string text representation of the field
  */
  function display() {
    return $this->text_dump();
  }

  /**
  * display the contents of the list
  * @return string text representation of the field
  */
  function text_dump() {
    return "<pre>ArrayChoiceList:\n".print_r($this->choicelist, true).'</pre>';
  }

  /** 
  * update the value of the list based on user data:
  *   - if it is within the range of current values, then take the value
  *   - if the field contains a new value (and is allowed to) then keep
  *     an illegal value, mark as being changed, and wait until later for
  *     the field to be updated
  *   - if the field contains a new value (and is not allowed to) or an 
  *     out-of-range value, then flag as being invalid
  * 
  * @param string $newval the (possibly) new value for the field
  * @param array ancillary user data (passed on to any appended or prepended fields)
  */
  function update($newval, $data) {
    if ($this->DEBUG) {
      echo 'ArrayChoiceList update: ';
      echo "(changed=$this->changed)";
      echo "(id=$this->id)";
      echo "(newval=$newval)";
    }
    if (isset($newval)) {
      //check to see if the newval is legal (does it exist on our choice list?)
      $isExisting = 0;
      foreach ($this->choicelist as $v) {
        if ($this->DEBUG) echo "($isExisting:".$v['id'].":$newval)";
        if ($v['id'] == $newval && $v['id'] >= 0) {
          $isExisting = 1;
          break;
        }
      }
      if ($isExisting) {
        // it is a legal, existing value, so we adopt it 
        if ($this->DEBUG) echo 'isExisting';
        $this->changed += ($newval != $this->id);
        $this->id = $newval;
        $this->isValid = 1;
        //isValid handling done by the Field that inherits it
      } elseif ($this->extendable) {
        // then it is a new value and we should accept it
        if ($this->DEBUG) echo 'isExtending';
        $this->changed += 1;
        //$this->id = $newval;
        //If we are extending the list, then we should have a negative
        //number as the current value to trip the creation of the new
        //entry later on in sync()
        $this->id = -1;
        foreach ($this->choicelist as $k => $v) {
          //preDump($v);
          if (isset($v['_field']) && $v['_field'] != "") {
            $this->choicelist[$k]['_field']->update($data);
            $this->isValid += $this->choicelist[$k]['_field']->isValid();
          }
        }
      } else {
        if ($this->DEBUG) echo 'isInvalid';
        // else, it's a new value and we should not accept it
        $this->isValid = 0;
      }
    }
    #echo " DBchoiceList::changed=$this->changed<br />";
    return $this->isValid;
  }

  /**
  * set the currently selected value
  *
  * @param string $value new value to use as curretly selected
  */
  function set($value) {
    #echo "DBchoiceList::set = $value<br/>";
    $this->id = $value;
  }

  /**
  * synchronise: but we have nothing to do as we are not attached to the db
  * (provided to give interface to normal Field database)
  * @return boolean false -- there was nothing to do
  */
  function sync() {
    return false;
  }

  /**
  * generate name=value pairs in case someone actually wants them!
  * 
  * @return string of form name='value'
  */
  function _sqlvals() {
    $vals = array();
    if ($this->changed) {
      #echo "This has changed";
      foreach ($this->choicelist as $v) {
        if (isset($v['_field'])) {
          $vals[] = $v['_field']->name ."=". qw($v['_field']->value);
        }
      }
    }
    #echo "<pre>"; print_r($this->choicelist); echo "</pre>";
    #echo "<pre>"; print_r($this->fields); echo "</pre>";
    #echo "<pre>"; print_r($vals); echo "</pre>";
    return join(",",$vals);
  }

  /**
  * determine whih values are selected and return them
  * @param boolean $returnArray  return an array of values
  * @return mixed list of selected values (array) or current value (string)
  */
  function selectedValue() {
    $val = array();
    foreach ($this->choicelist as $v) {
      //echo "H:$this->idfield, $k, $v, $this->id";
      if ($v[$this->listKey] == $this->id) {
        foreach ($this->fields as $f) {
          //echo "G=$f";
          $val[] = $v[$f];
        }
      }
    }
    return implode(' ', $val);
  }

  /**
  * set which option in the selection list is the default option
  * @param string $val   default value to use
  */
  function setDefault($val) {
    //echo "ArrayChoiceList::setDefault: $val";
    if (isset($this->id) || $this->id < 0) {
      $this->id = $val;
    }
    //echo $this->id;
  }

} // class ArrayChoiceList

?> 
