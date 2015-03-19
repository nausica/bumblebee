<?php
/**
* Construct an array for exporting the data
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: arrayexport.php,v 1.8 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage Export
*/

/** constants for defining export formatting and codes */
require_once 'inc/exportcodes.php';

/**
* Construct an array for exporting the data
*
* Create a monolithic array with all the data for export. The array is an
* intermediary form for creating PDF and HTML tables of data.
*
* @package    Bumblebee
* @subpackage Export
*/
class ArrayExport {
  /** @var DBList  raw data list */
  var $dblist;
  /** @var string  field name on which the report should be broken into sections */
  var $breakfield;
  /** @var         unknown? unused? */
  var $exporter;
  /** @var array   data array of exported data */
  var $export;
  /** @var string  header for the report */
  var $header;
  /** @var string  report Author (report metadata) */
  var $author = 'BumbleBee';
  /** @var string  report Creator (report metadata) */
  var $creator = 'BumbleBee Instrument Management System : bumblebeeman.sf.net';
  /** @var string  report Subject (report metadata) */
  var $subject = 'Instrument and consumable usage report';
  /** @var string  report Keywords (report metadata) */
  var $keywords = 'instruments, consumables, usage, report, billing, invoicing';
  /** @var array   list of subtotals for each section of the report for fields that are totalled  */
  var $_totals;
  /** @var boolean calculate column totals */
  var $_doingTotalCalcs = false;

  /**
  *  Create a new array export object to be used by both HTML and PDF export
  *
  * @param DBList &$dblist data to be exported (passed by reference for efficiency only)
  * @param string $breakfield   name of field to use to break report into sections
  */
  function ArrayExport(&$dblist, $breakfield) {
    $this->dblist   =& $dblist;
    $this->breakfield = $breakfield;
  }

  /**
  *  Parsed the exported data and create the marked-up array of data
  */
  function makeExportArray() {
    $ea = array();   //export array
    $ea[] = array('type' => EXPORT_REPORT_START,  
                  'data' => '');
    $ea[] = array('type' => EXPORT_REPORT_HEADER, 
                  'data' => $this->header);
    $entry = 0;
    $numcols = count($this->dblist->formatdata[0]);
    $breakfield = $this->breakfield;
    $breakReport = (!empty($breakfield) && isset($this->dblist->data[$entry][$breakfield]));
    //echo $breakReport ? 'Breaking' : 'Not breaking';
    while ($entry < count($this->dblist->formatdata)) {
      //$this->log('Row: '.$entry);
      $this->_resetTotals();
      $ea[] = array('type' => EXPORT_REPORT_SECTION_HEADER, 
                    'data' => $this->_sectionHeader($this->dblist->data[$entry]),
                    'metadata' => $this->_getColWidths($numcols, $entry));
      if ($breakReport) {
        $initial = $this->dblist->data[$entry][$breakfield];
      }
      $ea[] = array('type' => EXPORT_REPORT_TABLE_START,  
                    'data' => '');
      $ea[] = array('type' => EXPORT_REPORT_TABLE_HEADER, 
                    'data' => $this->dblist->outputHeader());
      while ($entry < count($this->dblist->formatdata) 
                && (! $breakReport
                    || $initial == $this->dblist->data[$entry][$breakfield]) ) {
        $ea[] = array('type' => EXPORT_REPORT_TABLE_ROW, 
                      'data' => $this->_formatRowData($this->dblist->formatdata[$entry]));
        $this->_incrementTotals($this->dblist->formatdata[$entry]);
        $entry++;
      }
      if ($this->_doingTotalCalcs) {
        $ea[] = array('type' => EXPORT_REPORT_TABLE_TOTAL, 
                      'data' => $this->_getTotals());
      }
      $ea[] = array('type' => EXPORT_REPORT_TABLE_END,   
                    'data' => '');
    }  
    $ea[] = array('type' => EXPORT_REPORT_END,  
                  'data' => '');
    $ea['metadata'] = $this->_getMetaData();
    //preDump($ea);
    $this->export =& $ea;
  }
  
  /**
  * create the section header
  *
  * @param array  current data row
  * @return string header string
  */
  function _sectionHeader($row) {
    $s = '';
    if (empty($this->breakfield)) {
      //$s .= $this->header;
    } else {
      $s .= $row[$this->breakfield];
    }
    return $s;
  }  
  
  /**
  * get the column widths for the columns (if defined)
  *
  * @param integer  number of columns
  * @param array    a row from the table
  * @return array   number of columns and a picture of the column widths spec
  */
  function _getColWidths($numcols, $entry) {
    $columns = array();
    foreach ($this->dblist->formatdata[$entry] as $f) {
      $columns[] = $f['width'];
    }
    return array(
                  'numcols' => $numcols,
                  'colwidths' => $columns
                );
  }

  /**
  * create an array of metadata to include in the output
  *
  * @return array  key => value metadata
  */
  function _getMetaData() {
    return array(
                  'author'  => $this->author,
                  'creator' => $this->creator,
                  'title'   => $this->header,
                  'keywords' => $this->keywords,
                  'subject' => $this->subject
                );
  }

  /**
  * reset the column subtotals to 0
  */
  function _resetTotals() {
    foreach ($this->dblist->formatdata[0] as $key => $val) {
      $this->_totals[$key] = $val;
      if ($val['format'] & EXPORT_CALC_TOTAL) {
        $this->_totals[$key]['value'] = 0;
        $this->_doingTotalCalcs = true;
      } else {
        $this->_totals[$key]['value'] = '';
      }
    }
  }
  
  /**
  * increment each column subtotal
  */
  function _incrementTotals($row) {
    if (! $this->_doingTotalCalcs) return;
    foreach ($row as $key => $val) {
      if ($val['format'] & EXPORT_CALC_TOTAL) {
        $this->_totals[$key]['value'] += $val['value'];
      }
    }
  }
  
  /**
  * get the column subtotals
  */
  function _getTotals() {
    $total = $this->_totals;
    foreach ($total as $key => $val) {
      if ($val['format'] & EXPORT_CALC_TOTAL) {
        $total[$key]['value'] = $this->_formatVal($val['value'],$val['format']);
      }
    }
    return $total;
  }

  /**
  * format a row of data using the formmatting information defined
  *
  * @param array  &$row   data row 
  * @return array   formatted data row
  */
  function _formatRowData(&$row) {
    $newrow = array();
    foreach ($row as $key => $val) {
      $newrow[$key] = $val;
      $newrow[$key]['value'] = $this->_formatVal($val['value'], $val['format']);
    }
    return $newrow;
  }
  
  /**
  * format a data value according to the defined rules for decimal places and currency
  *
  * @param string  $val   value to be formatted
  * @return string formatted value
  */
  function _formatVal($val, $format) {
    global $CONFIG;
    switch ($format & EXPORT_HTML_NUMBER_MASK) {
      case EXPORT_HTML_MONEY:
        $val = sprintf($CONFIG['export']['moneyFormat'], $val);
        break;
      case EXPORT_HTML_DECIMAL_1:
        $val = sprintf('%.1f', $val);
        break;
      case EXPORT_HTML_DECIMAL_2:
        $val = sprintf('%.2f', $val);
        break;
      default:
        //echo ($format& EXPORT_HTML_NUMBER_MASK).'<br/>';
    }
    return $val;
  }
  
  /**
  * join another ArrayExport object into this one.
  *
  * @param ArrayExport &$ea  ArrayExport object to be appended to this one
  */
  function appendEA(&$ea) {
    $this->export = array_merge($this->export, $ea->export);
  }
  
      
} // class ArrayExport

?> 
