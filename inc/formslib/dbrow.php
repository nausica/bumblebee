<?php
/**
* Database row base class
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: dbrow.php,v 1.41 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'dbobject.php';
/** status codes for success/failure of database actions */
require_once 'inc/statuscodes.php';


/**
* Object representing a database row (and extensible to represent joined rows)
*
* Typical usage:<code>
*   #set database connection parameters
*   $obj = new DBRow("users", 14, "userid");
*   #set the fields required and their attributes
*   $obj->addElement(....);
*   #connect to the database
*   $obj->fill();
*   #check to see if user data changes some values
*   $obj->update($POST);
*   $obj->checkValid();
*   #synchronise with database
*   $obj->sync();</code>
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class DBRow extends DBO {
  /** @var boolean  this is a new object the form for which has not yet been shown to the user */
  var $newObject = 0;
  /** @var boolean  this row should be inserted into the db */
  var $insertRow = 0;
  /** @var boolean  include all fields in the SQL statement not just ones that have changed or have values  */
  var $includeAllFields = 0;
  /** @var boolean  automatically number new objects (i.e. the database will do it for us) */
  var $autonumbering = 1;
  /** @var string   a restriction that is included in the WHERE statement for all queries  */
  var $restriction = '';
  /** @var string   number of the start record number for a LIMIT statement */
  var $recStart = '';
  /** @var string   number of the stop record number for a LIMIT statement */
  var $recNum   = '';
  /** @var string   do a two-step synchronisation routine whereby the record is created first then updated second */
  var $use2StepSync;
  /** @var array    additional rows to be included at the end of the display table */
  var $extrarows;
  /** @var boolean  row is marked as deleted in the table (but not actually deleted)  */
  var $isDeleted = false;
  /** @var string   this object can be deleted from the table (using DELETE); otherwise set the delete column to 1 for delete */
  var $deleteFromTable = 1;
    
  /**
  *  Create a new database row object
  *
  * @param string $table   name of the table to be used
  * @param integer $id     row id number in the table (-1 for new object)
  * @param string $idfield (optional) the column in the table for the primary key (id)
  */
  function DBRow($table, $id, $idfield='id') {
    $this->DBO($table, $id, $idfield);
    #$this->fields = array();
  }
  
  /**
  * Set the value of the primary key (id) for this object
  *
  * @param integer $newId    the id value to use
  */
  function setId($newId) {
    $this->log('DBRow: setting new id'.$newId);
    $this->id = $newId;
    $this->fields[$this->idfield]->set($this->id);
    foreach (array_keys($this->fields) as $k) {
      if ($this->fields[$k]->notifyIdChange) {
        $this->fields[$k]->idChange($newId);
      }
    }
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
    $this->log('DBRow:'.$this->namebase.' Looking for updates:');
    // First, check to see if this record is new
    if ($this->id == -1 && ! $this->ignoreId) {
      $this->insertRow = 1;
    }
    
    // We're a new object, but has the user filled the form in, or is the
    // user about to fill the form in?
    $this->newObject = 1;
    foreach (array_keys($this->fields) as $k) {
      if ($k != $this->idfield && isset($data[$this->namebase.$k])) {
        $this->log('I AM NOT NEW '.$k.':changed');
        $this->newObject = 0;
        break;
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
      if (! ($this->newObject && $this->insertRow)) {
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
  * Synchronise this object's fields with the database
  *
  * If the object is new, then INSERT the data, if the object is pre-existing
  * then UPDATE the data. Fancier fields that are only pretending to
  * do be simple fields (such as JOINed data) should perform their updates
  * during the _sqlvals() call 
  *
  * @return integer  from statuscodes
  */
  function sync() {
    global $TABLEPREFIX;
    // If the input isn't valid then bail out straight away
    if (! $this->changed) {
      $this->log('not syncing: changed='.$this->changed);
      return STATUS_NOOP;
    } elseif (! $this->isValid) {
      $this->log('not syncing: valid='.$this->isValid);
      return STATUS_ERR;
    }
    $this->log('syncing: changed='.$this->changed.' valid='.$this->isValid);
    if ($this->use2StepSync) {
      $this->_twoStageSync();
    }
    $sql_result = STATUS_NOOP;
    //obtain the *clean* parameter='value' data that has been SQL-cleansed
    //this will also trip any complex fields to sync
    $vals = $this->_sqlvals($this->insertRow || $this->includeAllFields);
    if ($vals != '') {
      //echo "changed with vals=$vals<br/>";
      if (! $this->insertRow) {
        //it's an existing record, so update
        $q = 'UPDATE '.$TABLEPREFIX.$this->table 
            .' SET '.$vals 
            .' WHERE '.$this->idfield.'='.qw($this->id)
            .(($this->restriction !== '') ? ' AND '.$this->restriction : '');
        $sql_result = db_quiet($q, $this->fatal_sql);
      } else {
        //it's a new record, insert it
        $q = 'INSERT '.$TABLEPREFIX.$this->table.' SET '.$vals;
        $sql_result = db_quiet($q, $this->fatal_sql);
        # FIXME: do we need to check that this was successful in here?
        if ($this->autonumbering) {
          //the record number can now be copied into the object's data.
          $this->setId(db_new_id());
        }
        $this->insertRow = 0;
      }
    } 
    $this->errorMessage .= $this->oob_errorMessage;
    //echo "sql=$sql_result, oob=$this->oob_status\n";
    return $sql_result | $this->oob_status;
  }
  
  /**
  * An alternative way of synchronising this object's fields with the database.
  *
  * Using this approach, we:
  *   - If the object is new, then INSERT a temp row first. 
  *   - Then, trip the sqlvals() calls.
  *   - Then, UPDATE the data. 
  *
  * Here, we to the 'create temp row' part.
  */
  function _twoStageSync() {
    if ($this->id == -1) {
      $row = new DBRow($this->table, -1, 'id');
      $f = new Field($this->idfield);
      $f->value = -1;
      $row->addElement($f);
      foreach ($this->fields as $field) {
        if ($field->requiredTwoStage) {
          $row->addElement($field);
        }
      }
      $row->isValid = 1;
      $row->changed = 1;
      $row->insertRow = 1;
      $row->sync();
      if ($this->autonumbering) {
        //the record number can now be copied into the object's data.
        $this->setId($row->id);
      }
      $this->insertRow = 0;
      $this->log('Created temp row for locking, id='.$this->id.')');
    }
  }  
  
  /**
  * Delete this object's row from the database.
  *
  * @param mixed (optional) string or array of column => value added to the UPDATE statement objects are only to be marked as deleted not actually deleted.
  * @return integer from statuscodes
  */
  function delete($extraUpdates=NULL) {
    global $TABLEPREFIX;
    if ($this->id == -1) {
      // nothing to do
      $this->log('$id == -1, so nothing to do');
      return STATUS_NOOP;
    }
    if (! $this->deletable) {
      $this->log('Object not deletable by rule.');
      $this->errorMessage = 'Cannot delete this item. Permission denied.';
      return STATUS_FORBIDDEN;
    }  
    $sql_result = -1;
    if ($this->deleteFromTable) {
      $q = 'DELETE FROM '.$TABLEPREFIX.$this->table 
          .' WHERE '.$this->idfield.'='.qw($this->id)
          .(($this->restriction !== '') ? ' AND '.$this->restriction : '')
          .' LIMIT 1';
    } else {
      $updates = array();
      if (is_array($extraUpdates)) {
        $updates = array_merge($updates, $extraUpdates);
      } elseif ($extraUpdates !== NULL) {
        $updates[] = $extraUpdates;
      } 
      // toggle the deleted state
      $updates[] = 'deleted='.($this->isDeleted?0:1); // old MySQL cannot handle true, use 0,1 instead
      $q = 'UPDATE '.$TABLEPREFIX.$this->table
          .' SET '.join($updates, ', ')
          .' WHERE '.$this->idfield.'='.qw($this->id)
          .(($this->restriction !== '') ? ' AND '.$this->restriction : '')
          .' LIMIT 1';
    }
    #$this->log($q);
    $sql_result = db_quiet($q, $this->fatal_sql);
    return $sql_result;
  }

  /**
  * Generate name='value' data for the SQL statement
  *
  * @param boolean $force (optional) force all fields to be included
  * @return string of data statements
  */
  function _sqlvals($force=0) {
    $vals = array();
    foreach (array_keys($this->fields) as $k) {
      if ($this->fields[$k]->changed || $force) {
        //obtain a string of the form "name='Stuart'" from the field.
        //Complex fields can use this as a JIT syncing point, and may
        //choose to return nothing here, in which case their entry is
        //not added to the return list for the row
        $this->log('Getting SQL string for '.$this->fields[$k]->name, 8);
        $sqlval = $this->fields[$k]->sqlSetStr('', $force);
        #echo "$k,oob = '".$this->fields[$k]->oob_status."' ";
        $this->oob_status |= $this->fields[$k]->oob_status;
        $this->oob_errorMessage .= $this->fields[$k]->oob_errorMessage;
        if ($sqlval) {
          #echo "SQLUpdate: '$sqlval' <br />";
          $vals[] = $sqlval;
        }
        #$vals[] = "$k=" . qw($v->value);
      }
    }
    #echo "<pre>"; print_r($vals); echo "</pre>";
    return join(', ',$vals);
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
  * Perform the SQL lookup to fill the object with the current data
  *
  * Fill this object (i.e. its fields) from the SQL query
  * @global string  prefix for table names
  */
  function fill() {
    global $TABLEPREFIX;
    //echo "foo:$this->id:bar";
    if (($this->id !== NULL && $this->id !== '' && $this->id != -1) || $this->ignoreId) {
      //FIXME: can we do this using quickSQLSelect()?
      $where = array();
      if (! $this->ignoreId) {
        $where[] = $this->idfield.'='.qw($this->id);
      }
      if (is_array($this->restriction)) {
        $where = array_merge($where, $this->restriction);
      } elseif ($this->restriction !== '') {
        $where[] = $this->restriction;
      }
      $q = 'SELECT * FROM '
          .$TABLEPREFIX.$this->table .' AS '. $this->table
          .' WHERE '.join($where, ' AND ')
          .(($this->recStart !== '') && ($this->recNum !== '') 
                          ? " LIMIT $this->recStart,$this->recNum" : '');
      $g = db_get_single($q);
      if (is_array($g)) { 
        foreach (array_keys($this->fields) as $k) {
          if (! $this->fields[$k]->sqlHidden) {
            $val = issetSet($g,$k);
            $this->fields[$k]->set($val);
          }
        }
        $this->isDeleted = issetSet($g, 'deleted', false);
      } else {
        $this->insertRow = $this->ignoreId;
      }
    }
    if ($this->ignoreId) {
      $this->id = $this->fields[$this->idfield]->value;
    } else {
      //we have to have an id present otherwise we're in trouble next time
      $this->fields[$this->idfield]->set($this->id);
    }
  }

  /** 
  * Quick and dirty dump of fields (values only, not a full print_r
  */
  function text_dump() {
    $t  = "<pre>$this->dumpheader $this->table (id=$this->id)\n{\n";
    foreach ($this->fields as $v) {
      $t .= "\t".$v->text_dump();
    }
    $t .= "}\n</pre>";
    return $t;
  }

  function display() {
    return $this->text_dump();
  }

  /**
  * Display the row as a form in a table
  *
  * @param integer $j      (optional) number of columns in the table (will pad as necessary)
  * @return string  html table
  */
  function displayInTable($j) {
    $t = '<table class="tabularobject">';
    foreach ($this->fields as $v) {
      $t .= $v->displayInTable($j);
    }
    $t .= '</table>';
    if (is_array($this->extrarows)) {
      foreach ($this->extrarows as $v) {
        $t .= '<tr>';
        foreach ($v as $c) {
          $t .= '<td>'.$c.'</td>';
        }
        $t .= '</tr>';
      }
    }
    return $t;
  }

  function displayAsTable($j=2) {
    return $this->displayInTable($j);
  }


} // class dbrow

?> 
