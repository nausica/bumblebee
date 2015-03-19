<?php
/**
* an object that manages data related by an SQL JOIN but pretends to be a single form field.
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: joindata.php,v 1.23 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'field.php';
/** database row manipulation object */
require_once 'dbrow.php';
/** connect to database */
require_once 'inc/db.php';

/**
* an object that manages data related by an SQL JOIN but pretends to be a single form field.
*
* If the element in the table is a selection list then the setup will be
* as a join table.
*
* We respect the 'field' interface while overriding pretty much all of it.
*
* Primitive class for managing join data. Can be used on its own to just
* join data or with a selection lists class to make a join table.
* This may be used to determine the choices
* that a user is permitted to select (e.g. dropdown list or radio buttons)
*
* Used in a many:many or many:1 relationships (i.e. a field in a 
* table that is the listed in a join table 
*
* Typical usage:<code>
*   $f = new JoinData('jointable', 'id1', $table1_key, 'fieldname', 'label1');
*   $f2 = new DropList('id2', 'label2');
*   $f2->connectDB('table2', array('id', 'name'));
*   $f2->list->prepend(array('-1','(none)'));
*   $f2->setFormat('id', '%s', array('name'), ' (%s)', array('longname'));
*   $f->addElement($f2);
*   $f3 = new TextField('field3', '');
*   $f->addElement($f3, 'sum_is_100');
*   $f->joinSetup('id2', array('total' => 3));
*   $f->use2StepSync = 1;
* </code>
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class JoinData extends Field {
  /** @var string   name of the join table (table has columns of form (LeftID, RightID) to join Left and Right tables */
  var $joinTable;
  /** @var string   column name in the join table for the column with keys/Ids from the left table */
  var $jtLeftIDCol;
  /** @var string   value of the left column ID that we are matching */
  var $jtLeftID;
  /** @var string   column name in the join table for the column with keys/Ids from the right table */
  var $jtRightIDCol;
  /** @var DBRow    prototype DBRow object that is replicated for each entry in the join table (1:many join) */
  var $protoRow;
  /** @var array    list of DBRow objects for each row returned in a 1:many join */
  var $rows;
  /** @var string   number of columns that this field should span in the table */
  var $colspan;
  /** @var array    formatting control arguments (e.g. maximum number permitted in 1:many) */
  var $format;
  /** @var integer  number of rows in the join */
  var $number = 0;
  /** @var array    list of columns to return when the containing object asks for the SQL column=value sequence */
  var $reportFields = array();
  /** @var array    list of functions that should be applied to the group of results collectively to test validity */
  var $groupValidTest;
  /** @var boolean  SQL errors should be fatal (die()) */
  var $fatalsql = 0;

  /**
  *  Create a new joindata object
  *
  * @param string $jointable    see $this->joinTable
  * @param string $jtLeftIDCol  see $this->jtLeftIDCol
  * @param string $jtLeftID     see $this->jtLeftID
  * @param string $name   the name of the field (db name, and html field name
  * @param string $description  used in the html title or longdesc for the field
  */
  function JoinData($joinTable, $jtLeftIDCol, $jtLeftID,
                     $name, $description='') {
    //$this->DEBUG=10;
    $this->Field($name, '', $description);
    $this->joinTable = $joinTable;
    $this->jtLeftIDCol = $jtLeftIDCol;
    $this->jtLeftID = $jtLeftID;
    $this->protoRow = new DBRow($joinTable, $jtLeftID, $jtLeftIDCol);
    $field = new Field($jtLeftIDCol);
    $this->protoRow->addElement($field);
    $this->protoRow->editable = 1;
    $this->protoRow->autonumbering = 0;
    $this->rows = array();
    $this->groupValidTest = array();
    $this->notifyIdChange = 1;
  }

  /**
  * Connect the join table and fill from the database
  *
  * @param string $jtRightIDCol  see $this->jtRightIDCol
  * @param mixed  $format        see $this->format (string is converted to array)
  */
  function joinSetup($jtRightIDCol, $format='') {
    $this->jtRightIDCol = $jtRightIDCol;
    $this->format = (is_array($format) ? $format : array($format));
    $this->_fill();
    //preDump($this);
  }

  /**
  * Calculate the maximum number of rows to display (e.g. including spares)
  */
  function _calcMaxNumber() {
    if (isset($this->format['total'])) {
      $this->number = $this->format['total'];
      return;
    }
    $this->number = $this->_countRowsInJoin();
    if (isset($this->format['minspare'])) {
      $this->number += $this->format['minspare'];
      return;
    }
    if (isset($this->format['matrix'])) {
      $this->number = $this->protoRow->fields[$this->jtRightIDCol]->list->length;
      return;
    }
  }

  /**
  * add a field to the join table
  * Field will appear in each row returned from the join table
  * @param Field  $field            the field to be added
  * @param string  $groupValidTest  data validation routine for this field
  */
  function addElement($field, $groupValidTest=NULL) {
    $this->protoRow->addElement($field);
    $this->groupValidTest[$field->name] = $groupValidTest;
  }

  /**
  * Create a new row from the protorow for storing data
  * @param integer  $rowNum   number of this row (used as unique identifier in the namebase)
  */
  function _createRow($rowNum) {
    $this->rows[$rowNum] = $this->protoRow;
    $this->rows[$rowNum]->setNamebase($this->name.'-'.$rowNum.'-');
  }

  /**
  * Fill from the database
  */
  function _fill() {
    $this->_fillFromProto();
  }

  /**
  * Fill from the database one row at a time
  */
  function _fillFromProto() {
    $oldnumber = $this->number;
    $this->_calcMaxNumber();
    $this->log('Extending rows from '.$oldnumber.' to '.$this->number);
    for ($i=$oldnumber; $i < $this->number; $i++) {
      $this->_createRow($i);
      $this->rows[$i]->recNum = 1;
      $this->rows[$i]->recStart = $i;
      $this->rows[$i]->fill();
      $this->rows[$i]->restriction = $this->jtRightIDCol .'='. qw($this->rows[$i]->fields[$this->jtRightIDCol]->value); 
      $this->rows[$i]->insertRow = ! ($this->rows[$i]->fields[$this->jtRightIDCol]->value > 0);
      $this->log('This row flagged with insertRow '.$this->rows[$i]->insertRow);
    }
  }
  
  function display() {
    //check how many fields we need to have (again) as we might have to show more this time around.
    $this->_fillFromProto();
    return $this->selectable();
  }

  function selectable($cols=2) {
    $t = '';
    #$errorclass = ($this->isValid ? '' : "class='inputerror'");
    $errorclass = '';
    for ($i=0; $i<$this->number; $i++) { 
      $t .= "<tr $errorclass><td colspan='$this->colspan'>\n";
      #$t .= "FOO$i";
      $t .= $this->rows[$i]->displayInTable(2);
      $t .= "</td>\n";
      for ($col=0; $col<$cols-2; $col++) {
        $t .= '<td></td>';
      }
      $t .= "</tr>\n";
    }
    return $t;
  }
  
  function selectedValue() {
    return $this->selectable();
  }

  function displayInTable($cols) {
    //check how many fields we need to have (again) as we might have to show more this time around.
    $this->_fillFromProto();
    //$cols += $this->colspan;
    $t = "<tr><td colspan='$cols'>$this->description</td></tr>\n";
    if ($this->editable) {
      $t .= $this->selectable($cols);
    } else {
      //preDump($this);
      //preDump(debug_backtrace());
      $t .= $this->selectedValue();
      $t .= "<input type='hidden' name='$this->name' value='$this->value' />";
    }
    return $t;
  }

  function update($data) {
    for ($i=0; $i < $this->number; $i++) {
      $rowchanged = $this->rows[$i]->update($data);
      if ($rowchanged) {
        $this->log('JoinData-Row '.$i.' has changed.');
        foreach (array_keys($this->rows[$i]->fields) as $k) {
          #$this->rows[$i]->fields[$this->jtRightIDCol]->changed = $rowchanged;
          #if ($v->name != $this->jtRightIDCol && $v->name != $this->jtLeftIDCol) {
            $this->rows[$i]->fields[$k]->changed = $rowchanged;
          #}
        }
      }
      $this->changed += $rowchanged;
    }
    $this->log('Overall JoinData row changed='.$this->changed);
    return $this->changed;
  }

  /**
  *  Count the number of rows in the join table so we know how many to retrieve
  * @return integer number of rows found
  */ 
  function _countRowsInJoin() {
    $g = quickSQLSelect($this->joinTable, $this->jtLeftIDCol, $this->jtLeftID, $this->fatalsql, 1);
    $this->log('Found '.$g[0].' rows currently in join');
    return $g[0];
  }

  /**
  * Trip the complex field within this object to sync()
  * This allows the object to then know our actual value (at last) -- this has to be
  * delayed for as long as possible as an INSERT might be needed before the value of the
  * selection is actually known, but that shouldn't be done until all the data has passed
  * all validation tests
  * @return string  sql name=value sequence
  */
  function sqlSetStr() {
    //$this->DEBUG=10;
    #echo "JoinData::sqlSetStr";
    $this->_joinSync();
    if (count($this->reportFields) < 1) {
      //We return an empty string as this is only a join table entry,
      //so it has no representation within the row itself.
      return ''; 
    } else {
      // then we can return the value of the first row (any more doesn't make sense)
      $t = array();
      foreach ($this->reportFields as $f) {
        if (is_array($f)) {
          reset($f);
          $realfield = key($f);
          $aliasfield = current($f);
          $t[] = $this->rows[0]->fields[$realfield]->sqlSetStr($aliasfield);
        } else {
          $t[] = $this->rows[0]->fields[$f]->sqlSetStr();
        }
      }
      return join(', ',$t);
    }
  }

  /**
  * synchronise the join table
  */
  function _joinSync() {
    for ($i=0; $i < $this->number; $i++) {
      #echo "before sync row $i oob='".$this->oob_status."' ";
      $this->changed += $this->rows[$i]->changed;
      //preDump($this->rows[$i]->fields[$this->jtRightIDCol]);
      if ($this->rows[$i]->fields[$this->jtRightIDCol]->value !== ''   // damned PHP '' == 0 
          && $this->rows[$i]->fields[$this->jtRightIDCol]->value == 0
          && $this->rows[$i]->fields[$this->jtRightIDCol]->changed) {
        //then this row is to be deleted...
        $this->oob_status |= $this->rows[$i]->delete();
      } else {
        $this->log('JoinData::_joinSync(): Syncing row '.$i);
//         preDump($this->rows[$i]);
        $this->oob_status |= $this->rows[$i]->sync();
      }
      $this->oob_errorMessage .= $this->rows[$i]->errorMessage;
      $this->changed += $this->rows[$i]->changed;
      #echo " after sync row $i oob='".$this->oob_status."'";
    }
  }
  
  
  /**
  * Check validity of data
  *
  * override the isValid method of the Field class, using the
  * checkValid method of each member row completed as well as 
  * cross checks on other fields.
  * @return boolean data is valid
  */
  function isValid() {
    $this->log('Check JoinData validity: '.$this->name);
    $this->isValid = 1;
    for ($i=0; $i < $this->number; $i++) {
      #echo "val". $this->rows[$i]->fields[$this->jtRightIDCol]->value.";";
      //$this->log('Row: '.$i);
      $this->rows[$i]->isValid = 1;
      if ($this->rows[$i]->fields[$this->jtRightIDCol]->value !== ''   // damned PHP '' == 0 
          && $this->rows[$i]->fields[$this->jtRightIDCol]->value == 0
          && $this->rows[$i]->changed) {
        // this row will be deleted to mark it valid in the mean time
        $this->rows[$i]->isValid = 1;
      } elseif ( ($this->rows[$i]->fields[$this->jtRightIDCol]->value == -1 
                   || $this->rows[$i]->fields[$this->jtRightIDCol]->value > 0)
                 && $this->rows[$i]->changed) {
        //this row will be sync'd against the database, so check its validity
        //$this->log('Checking valid for row: '.$i);
        $this->isValid = $this->rows[$i]->checkValid() && $this->isValid;
      }
      //echo "JoinData::isValid = '$this->isValid'";
      //echo "JoinData::isValid[$i] = '".$this->rows[$i]->isValid."'";
    }
    //now we need to check the validity of sets of data (e.g. sum of the same
    //field across the different rows.
    foreach ($this->rows[0]->fields as $k => $f) {
      if (isset($this->groupValidTest[$f->name])) {
        $allvals = array();
        for ($i=0; $i < $this->number; $i++) {
          if ($this->rows[$i]->fields[$this->jtRightIDCol]->value > 0) {
            $allvals[] = $this->rows[$i]->fields[$k]->value;
          }
        }
        $fieldvalid = ValidTester($this->groupValidTest[$f->name], $allvals);
        if (! $fieldvalid) {
          for ($i=0; $i < $this->number; $i++) {
            $this->rows[$i]->fields[$k]->isValid = 0;
          }
        }
        $this->isValid = $fieldvalid && $this->isValid;
      }
    }
    //echo "JoinData::isValid = '$this->isValid'";
    return $this->isValid;
  }
  
  /**
  * Change the Id value of each row
  */
  function idChange($newId) {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setId($newId);
    }
  }
  
  /**
  * Set the name base of the rows
  */
  function setNamebase($namebase='') {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setNamebase($namebase);
    }
    $this->protoRow->setNamebase($namebase);
  }

  /**
  * set whether each row is editable
  */
  function setEditable($editable=false) {
    for ($i=0; $i < $this->number; $i++) {
      $this->rows[$i]->setEditable($editable);
    }
    $this->protoRow->setEditable($editable);
  }
  
} // class JoinData

?> 
