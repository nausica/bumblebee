<?php
/**
* Provide example entries for existing values next to the choices in a list, e.g. radio list
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: exampleentries.php,v 1.8 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** uses choice list object for examples */
require_once 'dbchoicelist.php';
/** sql manipulation routines */
require_once 'sql.php';

/**
* Provide example entries for existing values next to the choices in a list, e.g. radio list
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class ExampleEntries {
  /** @var string   field in the supplied data array supplied to format that should be used for looking up examples */
  var $source;
  /** @var string   table from which to take examples (SQL FROM $table) */
  var $table;
  /** @var string   column in the table that has to match the supplied data  (SQL: WHERE $columnmatch=$data[$source]) */
  var $columnmatch;
  /** @var string   column in the table to return for matched data (SQL: SELECT $columnreturn) */
  var $columnreturn;
  /** @var integer  number of entries to return in the list (SQL: LIMIT) */
  var $limit;
  /** @var string   order for in which entries should be returned (SQL: ORDER BY $order) */
  var $order;
  /** @var string   delimeter between example entries returned */
  var $separator = ', ';
  /** @var DBChoiceList  object that obtains the data from the db */
  var $list;

  /**
  * Create a new ExampleEntries object
  *
  * @param string $source   see $this->source
  * @param string $table    see $this->table
  * @param string $columnmatch see $this->columnmatch
  * @param string $columnreturn see $this->columnreturn
  * @param string $maxentries (optional, default 3 entries) see $this->limit
  * @param string $order      (optional, use $columnreturn by default) see $this->order
  */
  function ExampleEntries($source, $table, $columnmatch, $columnreturn,
                          $maxentries=3, $order='') {
    $this->source = $source;
    $this->table = $table;
    $this->columnmatch = $columnmatch;
    $this->columnreturn = $columnreturn;
    $this->limit = $maxentries;
    $this->order = ($order != '' ? $order : $columnreturn);
  }

  /**
  * Obtain the example entries from the db
  *
  * @param string $id   the id number (or string) to match 
  */
  function fill($id) {
    #echo "Filling for $id";
    $safeid = qw($id);
    $this->list = new DBChoiceList($this->table, $this->columnreturn,
                             "{$this->columnmatch}=$safeid",
                             $this->order,
                             $this->columnmatch, $this->limit);
  }
    
  /**
  * Obtain the example entries and format them as appropriate
  *
  * @param array  $data  $data[$this->source] contains the id for which we should find examples 
  * (passed by ref for efficiency only)
  * @return string list of examples
  */
  function format(&$data) {
    #var_dump($data);
    $this->fill($data[$this->source]);
    $entries = array();
    foreach ($this->list->choicelist as $v) {
      $entries[] = $v[$this->columnreturn];
    }
    $t = implode($this->separator, $entries);
    return $t;
  }

} // class ExampleEntries


?> 
