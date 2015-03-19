<?php
/**
* a choice list field from which a select, list of hrefs etc can be built
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: choicelist.php,v 1.25 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'field.php';
/** formatter helper object */
require_once 'outputformatter.php';
/** list (db) object */
require_once 'dbchoicelist.php';
/** list (non-db) object */
require_once 'arraychoicelist.php';

/** 
* a choice list field from which a select, list of hrefs etc can be built
*
* A field class that can only take a restricted set of values, such as
* a radio list or a drop-down list.
*
* This class cannot adequately represent itself and is designed to be
* inherited by a representation (such as RadioList or DropDownList)
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class ChoiceList extends Field {
  /** @var DBChoiceList  contains the DBChoiceList object which has the restricted choices */
  var $list; 
  /** @var array         array of OutputFormatter objects  */
  var $formatter;
  /** @var integer       name of the id field for the value of the field */
  var $formatid;
  /** @var boolean       additional items can be added to the choicelist */
  var $extendable = 0;
  /** @var tristate      in filling list, include only deleted=true, deleted=false or both (true, false, NULL) */
  var $deleted=false; //deleted=true/false in SQL; NULL means don't restrict

  /**
  *  Create a new choice list
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $description  used in the html title or longdesc for the field
  */
  function ChoiceList($name, $description='') {
    parent::Field($name, '', $description);
    //$this->DEBUG=10;
  }

  /**
  * Create a DBList object within this class that is connected to the
  * available choices in the database and will handle the actual parsing 
  * of user input etc
  *
  * If php supported multiple inheritance, then $this->list would not be
  * a member of the class, rather DBList would be multiply inherited along
  * with Field.
  *
  * @param string $table the name of the DB table to query
  * @param mixed $fields array or string specifying the field(s) to query
  * @param string $restriction a restriction that will be applied to the query (WHERE...)
  * @param string $order the order to display the results (ORDER BY...)
  * @param string $idfield the name of the field that will be used to identify the selected item (value=)
  * @param string $limit limits that will be applied to the query (LIMIT...)
  * @param string $join any tables that should be LEFT JOINed in the query to make sense of it
  * @param boolean $distinct return only DISTINCT rows (default: false)
  *
  */
  function connectDB($table, $fields='', $restriction='', $order='name',
                      $idfield='id', $limit='', $join='', $distinct=false) {
    $this->list = new DBChoiceList($table, $fields, $restriction, $order,
                      $idfield, $limit, $join, $distinct, $this->deleted);
  }
  
  /** 
  * Provides a set of values for the droplist rather than filling it from a db query
  *
  * cf. ChoiceList::connectDB
  *
  * @param array $list List of label=>value pairs 
  */
  function setValuesArray($list, $idfield='id', $valfield='iv'){
    $this->list = new ArrayChoiceList($list, $idfield, $valfield);
  }  

  function text_dump() {
    return $this->list->text_dump();
  }

  function display() {
    return $this->text_dump();
  }

  /**
  * Create a set of OutputFormatter objects to handle the display of this object. 
  *
  *  called as: setFormat($id, $f1, $v1, $f2, $v2, ...) {
  *    - f1, v1 etc must be in pairs.
  *    - f1 is an sprintf format (see PHP manual)
  *    - v1 is an array of array indices that will be used to fill the
  *      fields in the sprintf format from a $data array passed to the
  *      formatter when asked to display itself
  */
  function setFormat() {
    $argc = func_num_args();
    $argv = func_get_args();
    $this->formatid = $argv[0];
    $this->formatter = array();
    for ($i = 1; $i < $argc; $i+=2) {
      $this->formatter[] = new OutputFormatter($argv[$i], $argv[$i+1]);
    }
  }

  /**
  * A test text-based format function for the object. 
  *
  * @param array $data the data to be formatted by this object's formatter object
  * @return string text-based representation
  */
  function format($data) {
    $s = $data[$this->formatid] .":". $this->formatter[0]->format($data)
        ."(". $this->formatter[1]->format($data).")";
    return $s;
  }

  /** 
  * Display the field inside a table
  *
  * @param integer $cols the number of columns to include in the table (extras are padded out)
  * @return string a single row HTML representation of the field
  */
  function displayInTable($cols) {
    if (! $this->hidden) {
      $errorclass = ($this->isValid ? "" : "class='inputerror'");
      $t = "<tr $errorclass><td>{$this->description}</td>\n"
          ."<td title='{$this->description}'>";
      if ($this->editable) {
        $t .= $this->selectable();
      } else {
        $t .= $this->selectedValue();
        $t .= "<input type='hidden' name='{$this->name}' value='{$this->value}' />";
      }
      $t .= "</td>\n";
      for ($i=0; $i<$cols-2; $i++) {
        $t .= "<td></td>";
      }
      $t .= "</tr>";
      return $t;
    }
  }

  function selectedValue() {
    return $this->list->selectedValue();
  }

  /**
  * Update the value of the field and also the complex field within based on the user data.
  *
  * This does *not* create new elements within the complex field (list)
  * at this stage: that is deferred until the latest possible point
  * for all error checking to be performed.
  *
  * @param array $data a set of name => value pairs from which the value of this field
  *               will be extracted
  * @return boolean did the field change?
  */
  function update($data) {
    parent::update($data);
    $this->list->editable = $this->editable;
    $this->list->extendable = $this->extendable;
    #echo "ID=".$this->list->id;
    if ($this->changed) {
      $this->log('haschanged');
      // only update the list if things have changed
      $this->list->update($this->value, $data);
      $this->changed = $this->list->changed;
      $this->isValid = $this->list->isValid;
    }
    $this->log('ChoiceList::Update->isValid= '.$this->isValid);
    #Field::set($this->list->id);
    #echo $this->list->id;
    #echo " (nv: $this->value)";
    return $this->changed;
  }

  /**
  * Set the value of this field, both in the Field and in the DBList
  * @param string $value  value to be set
  */
  function set($value) {
    $this->list->set($value);
    parent::set($value);
  }

  /**
   * Check the validity of the data. 
   *
   * Return TRUE iff the DBList isValid and the Field isValid.
   * This permits two rounds of checks on the data to be performed.
   *
   * @return boolean the field's current value is valid?
   */
  function isValid() {
    $this->log('ChoiceList::isValid='.$this->isValid);
    return $this->isValid && Field::isValid();
  }

  /**
  * Obtain the SQL data necessary for including the foreign key in
  * the DBRow to which we belong.
  * trip the complex field within us to sync(), which allows us
  * to then know our actual value (at last). 
  *
  * @param boolean $force force the field to return a name=value statement even
  *                if it would prefer not to
  * @return string name='value' from parent class
  */
  function sqlSetStr($name, $force) {
    #echo "Choicelist::sqlSetStr";
    $this->oob_status = $this->list->sync();
    //preDump($this->list);
    $this->oob_errorMessage = $this->list->oob_errorMessage;
    $this->value = $this->list->id;
    #preDump($this);
    return Field::sqlSetStr($name, $force);
  }

  /**
  * prepends a value to the dropdown list
  *
  * @param array $a see DBChoiceList for details
  */
  function prepend($a) {
    // templist is a horrible hack around a PHP 4.3 bug
    // This is all we want to do:
    //    $this->list->prepend($a);
    // but that causes $this->list to suddenly become a reference: Object not &Object (see a var_dump)
    // see http://bugs.php.net/bug.php?id=24485 and http://bugs.php.net/bug.php?id=30787
    // Note that PHP 4.4.x claims to have fixed this bug. (although 4.4.0 does not)
    $templist = $this->list;
    $templist->prepend($a);
    $this->list = $templist;
  }

  /**
  * appends a value to the dropdown list
  *
  * @param array $a see DBChoiceList for details
  */
  function append($a) {
    // templist is a horrible hack around a PHP 4.3 bug, see above
    // This is all we want to do:
    //    $this->list->append($a);
    $templist = $this->list;
    $templist->append($a);
    $this->list = $templist;
  }

  /**
  * set the default value for this object
  */
  function setDefault($val) {
    // templist is a horrible hack around a PHP 4.3 bug, see above
    // This is all we want to do:
    //    $this->list->append($a);
    $this->defaultValue = $val;
    $templist = $this->list;
    $templist->setDefault($val);
    $this->list = $templist;
  }

  function getValue() {
    return (isset($this->value) ? $this->value : $this->defaultValue);
  }

} // class ChoiceList

?> 
