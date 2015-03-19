<?php
/**
* Similar to JoinData, but presents the options in a matrix not a list
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: joinmatrix.php,v 1.7 2006/01/09 01:31:21 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'field.php';
/** uses ID field objects */
require_once 'idfield.php';
/** database row manipulation object */
require_once 'dbrow.php';
/** connect to database */
require_once 'inc/db.php';

/**
* Similar to JoinData, but presents the options in a matrix not a list
*
* The entries in one table can be a matrix with the coordinates from two others
*
* We respect the 'field' interface while overriding pretty much all of it.
*
* Typical usage:<code>
*   // jointable has structure: key1 key2 field1 field2 field3...
*   // where key1 is the id key of table1 and key2 is the id key for table2.
*   // we will display field* in rows for each key2 for a given key1
*   $f = new JoinMatrix('jointable', 'jtid'
                      'key1', 'id1', 'table1',
                      'key2', 'id2', 'table2', $name, $longname, $description);
*   $key1 = new TextField('key1', 'label1');
*   $key1->setFormat('id', '%s (%s)', array('name', 'longname'));
*   $key2 = new TextField('key2', 'label2');
*   $f->addKeys($key1, $key2);
*   $f1 = new TextField('field1', 'label');
*   $f->addElement($f1);
*   $f2 = new TextField('field2', 'label');
*   $f->addElement($f2);
*   $f->setKey($val);
*   </code>
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class JoinMatrix extends Field {
  /** @var string   name of the join table (table has columns of form (LeftID, RightID) to join Left and Right tables */
  var $joinTable;
  /** @var string  column in the join table that is the ID field for entries (unused) */
  var $jtIdField;
  
  /** @var string   value of the key for the left table that should be constant */
  var $jtConstKeyId;
  /** @var string   column name in the join table for the column with keys/Ids from the left table (id will be constant throughout the process) */
  var $jtConstKeyCol;
  /** @var string   name of the left table */
  var $table1;
  /** @var string   column name of ID column in the left table */
  var $table1IdCol;
  /** @var Field    representation of the key for the left table (unused?)  */
  var $key1;
  /** @var array    list of Field objects to be included along the header of the matrix */
  var $header1 = array();
  
  /** @var string   column name in the join table for the column with keys/Ids from the right table (id will vary throughout the process) */
  var $jtVarKeyCol;
  /** @var string   name of the right table */
  var $table2;
  /** @var string   column name of ID column in the right table */
  var $table2IdCol;
  /** @var Field    representation of the key for the right table  */
  var $key2;
  
  /** @var DBRow    prototype DBRow object that is replicated for each entry in the join table (1:many join) */
  var $protoRow;
  /** @var array    list of DBRow objects for each row returned in a 1:many join */
  var $rows;
  /** @var integer  number of rows in the join */
  var $number = 0;
  /** @var string   number of columns that this field should span in the table */
  var $colspan = 1;
  /** @var array    list of id-pairs in the join table */
  var $table2All;
  /** @var integer  number of fields that are also in the join table */
  var $numFields = 0;
  /** @var boolean  SQL errors should be fatal (die()) */
  var $fatal_sql = 0;

  /**
  *  Create a new joinmatrix object
  *
  * @param string $jointable    see $this->joinTable
  * @param string $jtIdField    (presently unused)
  * @param string $jtConstKeyCol  see $this->jtConstKeyCol
  * @param string $table1IdCol  see $this->table1IdCol
  * @param string $table1       see $this->table1
  * @param string $jtVarKeyCol  see $this->jtVarKeyCol
  * @param string $table2IdCol  see $this->table2IdCol
  * @param string $table2       see $this->table2
  * @param string $name       the name of the field (db name, and html field name
  * @param string $longname    used in the html label
  * @param string $description  used in the html title or longdesc for the field
  */
  function JoinMatrix($joinTable, $jtIdField,
                      $jtConstKeyCol, $table1IdCol, $table1,
                      $jtVarKeyCol,   $table2IdCol, $table2,
                      $name, $longname, $description='') {
    parent::Field($name, $longname, $description);
    //$this->DEBUG=10;
    $this->joinTable     = $joinTable;
    $this->jtIdField     = $jtIdField;
    $this->jtConstKeyCol = $jtConstKeyCol;
    $this->table1IdCol   = $table1IdCol;
    $this->table1        = $table1;
    $this->jtVarKeyCol   = $jtVarKeyCol;
    $this->table2IdCol   = $table2IdCol;
    $this->table2        = $table2;
    $this->protoRow = new DBRow($this->joinTable, -1);
    $id = new IdField($jtIdField);
    $this->protoRow->addElement($id);
    $k1 = new IdField($jtConstKeyCol);
    $this->protoRow->addElement($k1);
    $k2 = new IdField($jtVarKeyCol);
    $this->protoRow->addElement($k2);
    $this->protoRow->editable = 1;
    $this->protoRow->autonumbering = 1;
    $this->header2 = array();
    $this->rows = array();
    $this->notifyIdChange = 1;
  }

  /**
  * Add key Fields for the id columns in the left and right tables
  *
  * @param Field $key1   key field for left table (unused?)
  * @param Field $key2   key field for right table 
  */
  function addKeys($key1, $key2) {
    $this->key1 = $key1;
    //$this->key1->namebase = $this->table1;
    $this->key2 = $key2;
    //$this->key2->namebase = $this->table2;
  }
  
  /**
  * set the id that this object will match in the left table 
  *
  * @param integer   $id    id from the left table to match
  */
  function setKey($id) {
    $this->jtConstKeyId = $id;
    $this->_fillFromProto();
    //preDump($this);
  }
  
  /**
  * populates the matrix from the database
  *
  * @global string prefix to table names
  * @todo mysql specific functions
  */
  function _populateList() {
    global $TABLEPREFIX;
    $this->table2All = array();
    $q = 'SELECT '.$this->table2IdCol.', '.$this->key2->name
        .' FROM '.$TABLEPREFIX.$this->table2.' AS '.$this->table2
        .' ORDER BY '.$this->key2->name;
    $sql = db_get($q, $this->fatal_sql);
    // FIXME: mysql specific functions
    if (mysql_num_rows($sql)==0) {
      $this->number = 0;
      return;
    } else {
      while ($g = mysql_fetch_array($sql)) {
        $this->table2All[] = $g;
      }
    }
    $this->number = count($this->table2All);
  }

  /**
  * add a field to the join table
  * Field will appear in each row returned from the join table
  * @param Field  $field            the field to be added
  * @param string  $groupValidTest  data validation routine for this field
  */
  function addElement($field) {
    $this->protoRow->addElement($field);
    $this->header1[] = $field;
    $this->numFields++;
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
  * Fill from the database one row at a time
  */
  function _fillFromProto() {
    $this->_populateList();
    $this->log('Creating rows: '.$this->number);
    for ($i=0; $i < $this->number; $i++) {
      $this->_createRow($i);
      $this->rows[$i]->fields[$this->jtConstKeyCol]->value = $this->jtConstKeyId;
      $this->rows[$i]->fields[$this->jtVarKeyCol]->value = $this->table2All[$i][$this->table2IdCol];
      $this->rows[$i]->ignoreId = true;
      $this->rows[$i]->recStart = $i;
      $this->rows[$i]->restriction = 
                      $this->jtVarKeyCol   .'='. qw($this->table2All[$i][$this->table2IdCol])
             .' AND '.$this->jtConstKeyCol .'='. qw($this->jtConstKeyId); 
      $this->rows[$i]->fill();
//       $this->rows[$i]->insertRow = ! ($this->rows[$i]->fields[$this->table2IdCol]->value > 0);
    }
  }
  
  function display() {
    return $this->selectable();
  }

  function selectable() {
    $eol = "\n";
    $t = '<table><tr><td></td>'.$eol;
    for ($field=0; $field < $this->numFields; $field++) {
      $t .= '<td title="'.$this->header1[$field]->description.'">'
              .$this->header1[$field]->longname.'</td>'.$eol;
    }
    $t .= '</tr>'.$eol;
    for ($row=0; $row<$this->number; $row++) {
      $t .= '<tr><td>'.$this->table2All[$row][$this->key2->name]
           .$this->rows[$row]->fields[$this->jtVarKeyCol]->hidden()
           .'</td>'.$eol;
      for ($field=0; $field < $this->numFields; $field++) {
        $f =& $this->rows[$row]->fields[$this->header1[$field]->name];
        if ($this->editable) {
          $ft = $f->selectable();
        } else { 
          $ft = $f->selectedValue();
        }
        $t .= '<td title="'.$f->description.'">'.$ft.'</td>'.$eol;
      }
      $t .= '</tr>'.$eol;
    }
    $t .= '</table>';
    return $t;
  }
  
  function selectedValue() {
    $editable = $this->editable;
    $this->editable = 0;
    $t = $this->selectable();
    $this->editable = $editable;
    return $t;
  }

  function displayInTable($cols) {
    //check how many fields we need to have (again) as we might have to show more this time around.
    //$cols += $this->colspan;
    $eol = "\n";
    $t = '<tr><td colspan="'.($cols+$this->colspan).'" title="'.$this->description.'">'
                      .$this->longname.'</td></tr>'
        .'<tr><td colspan="'.$this->colspan.'">'.$eol;
    $t .= $this->selectable();
    for ($i=0; $i<$cols-2; $i++) {
      $t .= '<td></td>';
    }
    $t .= '</tr>'.$eol;
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
  * trip the complex field within us to sync(), which allows us
  * to then know our actual value (at last).
  */
  function sqlSetStr($name='') {
    //$this->DEBUG=10;
    #echo "JoinData::sqlSetStr";
    $this->_joinSync();
    //We return an empty string as this is only a join table entry,
    //so it has no representation within the row itself.
    return '';
  }

  /**
  * synchronise the join table
  */
  function _joinSync() {
    for ($i=0; $i < $this->number; $i++) {
      #echo "before sync row $i oob='".$this->oob_status."' ";
      $this->changed += $this->rows[$i]->changed;
      $this->log('JoinData::_joinSync(): Syncing row '.$i);
      $this->oob_status |= $this->rows[$i]->sync();
      $this->oob_errorMessage .= $this->rows[$i]->errorMessage;
      $this->changed += $this->rows[$i]->changed;
      #echo " after sync row $i oob='".$this->oob_status."'";
    }
  }
  
  
  /**
  * override the isValid method of the Field class, using the
  * checkValid method of each member row completed as well as 
  * cross checks on other fields.
  *
  * @return boolean data is valid
  */
  function isValid() {
    $this->isValid = 1;
    for ($i=0; $i < $this->number; $i++) {
      $this->isValid = $this->rows[$i]->checkValid() && $this->isValid;
    }
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
  
} // class JoinMatrix

?> 
