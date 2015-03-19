<?php
/**
* Equivalent to DBRow where the database is not involved
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: nondbrow.php,v 1.6 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** database uber-object that we will emulate */
require_once 'dbobject.php';
/** status codes for success/failure of database actions */
require_once 'inc/statuscodes.php';


/**
* Equivalent to DBRow where the database is not involved
*
* Object representing a NON-database row (and extensible to represent joined rows)
*
* Usage:<code>
*   #set database connection parameters
*   $obj = new nonDBRow();
*   #set the fields required and their attributes
*   $obj->addElement(....);
*   #check to see if user data changes some values
*   $obj->update($POST);
*   $obj->checkValid();
* </code>
* @package    Bumblebee
* @subpackage FormsLibrary
*/
 
class nonDBRow {
  /** @var string   name of this field */
  var $name;
  /** @var string   label for the field in the display */
  var $longname;
  /** @var string   description for the field in the mouse-over */
  var $description;
  /** @var boolean  this is a new object the form for which has not yet been shown to the user */
  var $newObject = 0;
  /** @var boolean  this row is editable */
  var $editable = 1;
  /** @var string   prefixed to all name="$field[name]" sections of the html code */
  var $namebase;
  /** @var string   current error message  */
  var $errorMessage = '';
  /** @var boolean  the fields in this row have changed cf the database */
  var $changed = 0;
  /** @var boolean  the data in the fields are valid */
  var $isValid = 0;
  /** @var boolean   don't to validation on this row */
  var $suppressValidation = 0;
  /** @var string   output when doing text dumps of the object */
  var $dumpheader = 'nonDBRow object';
  /** @var boolean  sql errors should be considered fatal and the script will die()  */
  var $fatal_sql = 1;
  /** @var array    additional rows to be included at the end of the display table */
  var $extrarows;
  /** @var array    list of Field objects in this row */
  var $fields;

  /** @var integer   debug level 0=off    */
  var $DEBUG = 0;
  
  
  /**
  *  Create a new generic field/row object not linked to the db
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $longname  long name to be used in the label of the field in display
  * @param string $description  used in the html title or longdesc for the field
  */
  function nonDBRow($name, $longname, $description) {
    $this->name = $name;
    $this->longname = $longname;
    $this->description = $description;
  }
  
  /** 
  *  Update the object with the user-submitted data
  *
  *  update the value of each of the objects fields according to the user 
  *  input data, and validate the data if appropriate
  *  @param array user supplied data (field => $value)
  *  @return boolean data is valid 
  */
  function update($data) {
    // We're a new object, but has the user filled the form in, or is the
    // user about to fill the form in?
    $this->newObject = 1;
    foreach (array_keys($this->fields) as $k) {
      if (isset($data[$this->namebase.$k])) {
        $this->log('I AM NOT NEW '.$k.':changed');
        $this->newObject = 0;
        break;
      } else {
        $this->log('Still new '.$k.':unchanged');
      }
    }
  
    // check each field in turn to allow it to update its data
    foreach (array_keys($this->fields) as $k) {
      $this->log("Check $k ov:".$this->fields[$k]->value
                            .'('.$this->fields[$k]->useNullValues .'/'. $this->newObject.')');
      if (!($this->fields[$k]->useNullValues && $this->newObject)) {
        $this->changed += $this->fields[$k]->update($data);
      }
      $this->log('nv:'.$this->fields[$k]->value.' '.($this->changed ? 'changed' : 'not changed'));
    }
    #$this->checkValid();
    return $this->changed;
  }


  /**
  * check the validity of the data
  * @return boolean data is valid
  */
  function checkValid() {
    $this->isValid = 1;
    // check each field in turn to allow it to update its data
    // if this object has not been filled in by the user, then 
    // suppress validation
    foreach (array_keys($this->fields) as $k) {
      if (! $this->newObject) {
        $this->log('Checking valid '.$this->fields[$k]->namebase . $k);
        if (! $this->fields[$k]->isValid()) {
          $this->errorMessage .= 'Invalid data: '.$this->fields[$k]->longname
                                    .'('.$this->fields[$k]->name.')'
                                  .' = "'. $this->fields[$k]->getValue() .'"<br />';
          $this->isValid = false;
        }
      }
    }
    if (! $this->isValid) {
      $this->errorMessage .= '<br />Some values entered into the form are not valid '
                  .'and should be highlighted in the form below. '
                  .'Please check your data entry and try again.';
    }
    return $this->isValid;
  }

  /** 
  * Add a new field to the row
  *
  * Add an element into the fields[] array. The element must conform
  * to the Fields class (or at least its interface!) as that will be
  * assumed elsewhere in this object.
  * Inheritable attributes are also set here.
  *
  * @param Field $el the field to add 
  */
  function addElement($el) {
    $this->fields[$el->name] = $el;
    if ($this->fields[$el->name]->editable == -1) {
      $this->fields[$el->name]->editable = $this->editable;
    }
    if (! isset($this->fields[$el->name]->namebase)) {
      $this->fields[$el->name]->namebase = $this->namebase;
      #echo "Altered field $el->name to $this->namebase\n";
    }
    if ($this->fields[$el->name]->suppressValidation == -1) {
      $this->fields[$el->name]->suppressValidation = $this->suppressValidation;
      #echo "Altered field $el->name to $this->namebase\n";
    }
    #echo $el->name;
    #echo "foo:".$this->fields[$el->name]->name.":bar";
  }

  /** 
  * Add multiple new fields to the row
  *
  * Adds multiple elements into the fields[] array.
  * 
  * @param array $els array of Field objects
  */
  function addElements($els) {
    foreach ($els as $e) {
      #echo $e->text_dump();
      $this->addElement($e);
    }
  }

  /**
  * Create a quick text representation of the object
  *
  * @return string text representation
  */
  function text_dump() {
    $t  = "<pre>$this->dumpheader $this->table (id=$this->id)\n{\n";
    foreach ($this->fields as $v) {
      $t .= "\t".$v->text_dump();
    }
    $t .= "}\n</pre>";
    return $t;
  }

  /**
  * Display the object
  *
  * @return string object representation
  */
  function display() {
    return $this->text_dump();
  }

  /**
  * Display the row as a form in a table
  *
  * @param integer $j      (optional) number of columns in the table (will pad as necessary)
  * @return string  html table
  */
  function displayInTable($numCols=2) {
    $t  = '<h3>'.$this->longname.'</h3>';
    $t .= '<table class="tabularobject" title="'.$this->description.'">';
    foreach ($this->fields as $v) {
      $t .= $v->displayInTable($numCols);
    }
    if (is_array($this->extrarows)) {
      foreach ($this->extrarows as $v) {
        $t .= '<tr>';
        foreach ($v as $c) {
          $t .= '<td>'.$c.'</td>';
        }
        $t .= '</tr>';
      }
    }
    $t .= '</table>';
    return $t;
  }

  /**
  * Debug print function.
  *
  * @param string $logstring debug message to be output
  * @param integer $priority  priority of the message, will not be written unless $priority <= $this->DEBUG
  */
  function log($logstring, $priority=10) {
    if ($priority <= $this->DEBUG) {
      echo $logstring."<br />\n";
    }
  }

} // class dbrow

?> 
