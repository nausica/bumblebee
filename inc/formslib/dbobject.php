<?php
/**
* database object base class (self-initialising and self-updating object)
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: dbobject.php,v 1.21 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** type checking and data manipulation */
require_once 'inc/typeinfo.php';
/** database connection script */
require_once 'inc/db.php';
/** sql manipulation routines */
require_once 'sql.php';
/** status codes for success/failure of database actions */
require_once 'inc/statuscodes.php';

/**
* database object base class (self-initialising and self-updating object)
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class DBO {
  /** @var string   db table from which to take data */
  var $table;
  /** @var string   db column that is the id/key */
  var $idfield;
  /** @var string   ??? FIXME: unused? */
  var $idfieldreal;
  /** @var string   the id/key value for this particlar row. -1 means not assigned yet (new object not in table) */
  var $id=-1;
  /** @var boolean  if true, *don't* use the ID field in updates etc */
  var $ignoreId = false;
  /** @var array    list of Field objects in this row */
  var $fields;
  /** @var boolean  this field is able to be edited by the user  */
  var $editable = 0;
  /** @var boolean  this field is able to be deleted (or marked as deleted) */
  var $deletable = 1;
  /** @var boolean  the fields in this row have changed cf the database */
  var $changed = 0;
  /** @var boolean  the data in the fields are valid */
  var $isValid = 0;
  /** @var boolean  don't do validation on the data */
  var $suppressValidation = 0;
  /** @var string   output when doing text dumps of the object */
  var $dumpheader = 'DBO object';
  /** @var boolean  sql errors should be considered fatal and the script will die()  */
  var $fatal_sql = 1;
  /** @var string   prefixed to all name="$field[name]" sections of the html code */
  var $namebase;
  /** @var string   current error message  */
  var $errorMessage = '';
  /** @var integer  status code from operation from statuscodes.php  */
  var $oob_status = STATUS_NOOP;
  /** @var string    */
  var $oob_errorMessage = '';

  /** @var integer   debug level 0=off    */
  var $DEBUG = 0;
  
  /**
  *  Create a new database object, designed to be superclasses
  *
  * @param string $table   see $this->table
  * @param string $id       see $this->id
  * @param mixed $idfield  if string, $this->idfield. if array, ($this->idfield, $this->idfieldreal).
  */
  function DBO($table, $id, $idfield = 'id') {
    $this->table = $table;
    $this->id = $id;
    if (is_array($idfield)) {
      $this->idfieldreal = $idfield[0];
      $this->idfield = $idfield[1];
    } else {
      $this->idfield = $idfield;
    }
    $this->fields = array();
  }
  
  /**
  *  Sets a new name base for row and all fields within it
  *
  * The name base is prepended to the field name in all html name="" sequences for the widgets
  * @param string $newname  new name-base to use
  */
  function setNamebase($newname='') {
    $this->namebase = $newname;
    foreach (array_keys($this->fields) as $k) {
      $this->fields[$k]->setNamebase($newname);
    }
  }

  /**
  * Sets whether a row and all fields within it are editable
  *
  * @param boolean $editable  new editable state
  */
  function setEditable($editable=1) {
    $this->editable = $editable;
    foreach (array_keys($this->fields) as $k) {
      $this->fields[$k]->setEditable($editable);
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

} // class dbo

?> 
