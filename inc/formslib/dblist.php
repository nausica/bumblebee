<?php
/**
* generic database list/export class
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: dblist.php,v 1.15 2006/01/06 10:07:22 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** uses an OutputFormatter to format the data */
require_once 'inc/formslib/outputformatter.php';
/** export formatting codes */
require_once 'inc/exportcodes.php';

/**
* generic database list/export class
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class DBList {
  var $restriction;
  var $unionrestriction;
  var $join = array();
  var $order;
  var $group;
  var $union;
  var $returnFields;
  var $omitFields = array();
  var $fieldOrder;
  var $formatter;
  var $distinct = 0;
  var $table;
  var $data;
  var $formatdata;
  var $outputFormat = EXPORT_FORMAT_CUSTOM;
  var $breakfield;
  var $fatal_sql = 1;
  
  /**
  *  Create a new DBList object
  *
  * @param string $table   name of (primary) table to be interrogated
  * @param mixed  $returnFields   single column name or list of column names to be returned from the db
  * @param mixed  $restriction    single or list of restrictions to be 'AND'ed together
  * @param boolean $distinct      select only DISTINCT rows from the db
  */
  function DBList($table, $returnFields, $restriction, $distinct=false) {
    $this->table = $table;
    $this->distinct = $distinct;
    if (is_array($restriction)) {
      $this->restriction = $restriction;
    } else {
      $this->restriction = array($restriction);
    }
    if (is_array($returnFields)) {
      $this->returnFields = $returnFields;
    } else {
      $this->returnFields = array($returnFields);
    }
  }

  /**
  * Fill the object from the database
  *
  * @todo mysql specific function
  */
  function fill() {
    // construct the query
    if (is_array($this->union) && count($this->union)) {
      $union = array();
      foreach ($this->union as $u) {
        $u->restriction = array_merge($u->restriction, $this->unionrestriction);
        $union[] = $u->_getSQLsyntax();
      }
      $q = '('.join($union, ') UNION (').')';
      $q .= (is_array($this->order) ? ' ORDER BY '.join($this->order,', ') : '');
      $q .= (is_array($this->group) ? ' GROUP BY '.join($this->group,', ') : '');
    } else {
      $q = $this->_getSQLsyntax();
    }
    $sql = db_get($q, $this->fatal_sql);
    $data = array();
    // FIXME: mysql specific functions
    while ($g = mysql_fetch_array($sql)) {
      $data[] = $g;
    }
    if (isset($this->manualGroup) && $this->manualGroup != '') {
      $sumdata = array();
      $row=0; 
      while ($row < count($data)) {
        $current = $data[$row][$this->manualGroup];
        $currentRow = $data[$row];
        $sums = array();
        foreach ($this->manualSum as $col) {
          $sums[$col] = 0;
        }
        while ($row < count($data) && $data[$row][$this->manualGroup] == $current) {
          foreach ($this->manualSum as $col) {
            $sums[$col] += $data[$row][$col];
          }
          $row++;
        }
        foreach ($this->manualSum as $col) {
          $currentRow[$col] = $sums[$col];
        }
        $sumdata[] = $currentRow;
      }
      $this->data = $sumdata;
    } else {
      $this->data = $data;
    }
  }
  
  /**
  * generate the appropriate SQL syntax for this query
  *
  * @return string SQL query for this object
  */
  function _getSQLsyntax() {
    global $TABLEPREFIX;
    $fields = array();
    foreach ($this->returnFields as $v) {
      $fields[] = $v->name .(isset($v->alias) ? ' AS '.$v->alias : '');
    }
    $q = 'SELECT '.($this->distinct ? 'DISTINCT ' : ' ')
          .join($fields, ', ')
        .' FROM '.$TABLEPREFIX.$this->table.' AS '.$this->table.' ';
    foreach ($this->join as $t) {
      $q .= ' LEFT JOIN '.$TABLEPREFIX.$t['table'].' AS '.(isset($t['alias']) ? $t['alias'] : $t['table'])
           .' ON '.$t['condition'];
    }
    $q .= ' WHERE '. join($this->restriction, ' AND ');
    $q .= (is_array($this->order) ? ' ORDER BY '.join($this->order,', ') : '');
    $q .= (is_array($this->group) ? ' GROUP BY '.join($this->group,', ') : '');
    return $q;
  }

  /**
  * format the list using the designated formats into another list
  */
  function formatList() {
    //preDump($this->omitFields);
    $this->formatdata = array();
    if (! is_array($this->fieldOrder)) {
      $this->fieldOrder = array();
      foreach ($this->returnFields as $f) {
        $this->fieldOrder[] = $f->alias;
      }
    }
    for ($i=0; $i<count($this->data); $i++) {
      $this->formatdata[$i] = $this->format($this->data[$i]);
    }
  }
    
  /**
  * format a row of data 
  * @param array $data   name=>value pairs of data
  * @return string   formatter line of data 
  */
  function format($data/*, $isHeader=false*/) {
    $d = array();
    foreach ($this->fieldOrder as $f) {
      if (! array_key_exists($f, $this->omitFields)) {
        $d[$f] = $data[$f];
      }
    }
    if (EXPORT_FORMAT_CSV & $this->outputFormat) 
      return join(preg_replace(array('/"/',     '/^(.*,.*)$/'), 
                               array('\\"',   '"$1"'       ), $d), ',');
    if (EXPORT_FORMAT_TAB & $this->outputFormat) 
        return join(preg_replace("/^(.*\t.*)$/", '"$1"', $d), "\t");
    if (EXPORT_FORMAT_USEARRAY & $this->outputFormat) 
        return $this->_makeArray($d/*, $isHeader*/);
        
    return $this->formatter->format($d);
  }
    
  /**
  * format a header row 
  * @return string   formatter header row
  */
  function outputHeader() {
    $d = array();
    foreach ($this->returnFields as $f) {
      $d[$f->alias] = $f->heading;
    }
    return $this->format($d/*, true*/);
  }

  /**
  * create a row of data with the value and some formatting data for use by the Array/HTML/PDF Export
  * @return array   list of array(value=>$value, format=>$format, width=>$width)
  */
  function _makeArray($d/*, $isHeader=false*/) {
    $row = array();
    foreach ($d as $alias => $val) {
      for ($i=0; $i<count($this->returnFields) && $this->returnFields[$i]->alias != $alias; $i++) {
      }
      $f = $this->returnFields[$i];
      $cell = array();
      //$cell['value'] = $this->formatVal($val, $f->format, $isHeader);
      $cell['value'] = $val;
      $cell['format'] = $f->format;
      $cell['width'] =  isset($f->width) ? $f->width : 10;
      $row[] = $cell;
    }
    return $row;
  }

 /**
  * Create a set of OutputFormatter objects to handle the display of this object. 
  *
  * @param string $f1    sprintf format (see PHP manual)
  * @param array $v1     array of indices that will be used to fill the fields in the sprintf format from a $data array passed to the formatter later.
  */
  function setFormat($f, $v) {
    $this->formatter = new OutputFormatter($f, $v);
  }

} // class DBList


?> 
