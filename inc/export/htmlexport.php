<?php
/**
* Construct an HTML export from array representation
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: htmlexport.php,v 1.6 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage Export
*/

/** constants for defining export formatting and codes */
require_once 'inc/exportcodes.php';

/**
* Construct an HTML export from array representation
*
* @package    Bumblebee
* @subpackage Export
*/
class HTMLExport {
  /** @var string       html-rendered data   */
  var $export;
  /** @var boolean      export the data as one big table with header rows between sections  */
  var $bigtable = true;
  /** @var ArrayExport  data to export    */
  var $ea;
  /** @var string       header to the report  */
  var $header;
  
  /**
  *  Create the HTMLExport object
  *
  * @param ArrayExport  &$exportArray
  */
  function HTMLExport(&$exportArray) {
    $this->ea =& $exportArray;
  }

  /**
  * convert the 2D array into an HTML table representation of the data
  */
  function makeHTMLBuffer() {
    //$this->log('Making HTML representation of data');
    $ea =& $this->ea->export;
    $eol = "\n";
    $metaData = $ea['metadata'];
    unset($ea['metadata']);
    $buf = '';
    for ($i=0; $i<count($ea); $i++) {
      if (! $this->bigtable) {
        switch ($ea[$i]['type']) {
          case EXPORT_REPORT_START:
            $buf .= '<div id="bumblebeeExport">';
            break;
          case EXPORT_REPORT_END:
            $buf .= '</div>';
            break;
          case EXPORT_REPORT_HEADER:
            $buf .= '<div class="exportHeader">'.$ea[$i]['data'].'</div>'.$eol;
            $this->header = $ea[$i]['data'];
            break;
          case EXPORT_REPORT_SECTION_HEADER:
            $buf .= '<div class="exportSectionHeader">'.$ea[$i]['data'].'</div>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_START:
            $tableMetaData = $ea[$i]['metadata'];
            $numcols = $tableMetaData['numcols'];
            $buf .= '<table class="exportdata">'.$eol;
            break;
          case EXPORT_REPORT_TABLE_END:
            $buf .= '</table>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_HEADER:
            $buf .= '<tr class="header">'
                        .$this->_formatRowHTML($ea[$i]['data'], true)
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_TOTAL:
            $buf .= '<tr class="totals">'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_FOOTER:
            $buf .= '<tr class="footer">'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_ROW:
            $buf .= '<tr>'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
        }
      } else {
        switch ($ea[$i]['type']) {
          case EXPORT_REPORT_START:
            $buf .= '<div id="bumblebeeExport">';
            break;
          case EXPORT_REPORT_END:
            $buf .= '</table></div>';
            break;
          case EXPORT_REPORT_HEADER:
            $buf .= '<div class="exportHeader">'.$ea[$i]['data'].'</div>'.$eol;
            $buf .= '<table class="exportdata">'.$eol;
            break;
          case EXPORT_REPORT_SECTION_HEADER:
            $tableMetaData = $ea[$i]['metadata'];
            $numcols = $tableMetaData['numcols'];
            $buf .= '<tr class="exportSectionHeader"><td colspan="'.$numcols.'" class="exportSectionHeader">'
                        .$ea[$i]['data'].'</td></tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_START:
            break;
          case EXPORT_REPORT_TABLE_END:
            break;
          case EXPORT_REPORT_TABLE_HEADER:
            $buf .= '<tr class="header">'
                        .$this->_formatRowHTML($ea[$i]['data'], true)
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_FOOTER:
            $buf .= '<tr class="footer">'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_TOTAL:
            $buf .= '<tr class="totals">'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
          case EXPORT_REPORT_TABLE_ROW:
            $buf .= '<tr>'
                        .$this->_formatRowHTML($ea[$i]['data'])
                    .'</tr>'.$eol;
            break;
        }
      }
    }      
    $this->export =& $buf;
  }
  
  /**
  * generate the report header
  *
  * @return string report header
  */
  function _reportHeader() {
    $start = $this->_daterange->getStart();
    $stop  = $this->_daterange->getStop();
    $s = $this->_export->description .' for '. $start->datestring .' - '. $stop->datestring;
    return $s;
  }  

  /**
  * generate the header for a section
  *
  * @return string section header
  */
  function _sectionHeader($row) {
    $s = $row[$this->_export->breakField];
    return $s;
  }  
  
  /**
  * generate the HTML for a row
  *
  * @return string row
  */
  function _formatRowHTML($row, $isHeader=false) {
    $b = '';
    for ($j=0; $j<count($row); $j++) {
      $b .= $this->_formatCellHTML($row[$j], $isHeader);
    }
    return $b;
  }

  /**
  * generate the HTML for a single cell
  *
  * @return string cell
  */
  function _formatCellHTML($d, $isHeader) {
    $t = '';
    $val = $d['value'];
    if (! $isHeader) {
      switch($d['format'] & EXPORT_HTML_ALIGN_MASK) {
        case EXPORT_HTML_CENTRE:
          $align='center';
          break;
        case EXPORT_HTML_LEFT:
          $align='left';
          break;
        case EXPORT_HTML_RIGHT:
          $align='right';
          break;
        default:
          $align='';
      }
      $align = ($align!='' ? 'align='.$align : '');
      $t .= '<td '.$align.'>'.htmlentities($val).'</td>';
    } else {
      $t .= '<th>'.htmlentities($val).'</th>';
    }
    return $t;
  }
  
  /**
  * embed the html within a blank page to create the report in a separate window
  *
  * @global array   config settings
  * @global string  base URL for installation
  * @return string  html snippet that will open a new window with the html report
  */
  function wrapHTMLBuffer() {
    global $CONFIG;
    global $BASEPATH;
    $filename = $CONFIG['export']['htmlWrapperFile'];
    $fd = fopen($filename, 'r');
    $contents = fread($fd, filesize ($filename));
    fclose($fd); 
    $title = 'Data export';
    $table = preg_replace('/\$/', '&#'.ord('$').';', $this->export);
    $contents = preg_replace('/__TITLE__/', $title, $contents);
    $contents = preg_replace('/__BASEPATH__/', $BASEPATH, $contents);
    //return $contents;
    //preDump($contents);
    $contents = preg_replace('/__CONTENTS__/', $table, $contents);
    //encode the HTML so that it doesn't get interpreted by the browser and cause big problems
    //the PHP function rawurlencode() can be reversed by the JavaScript function unescape()
    //which is then a convenient pairing to use rather than replacing everything manually.
    $enchtml = rawurlencode($contents);
    $jsbuf = '<script type="text/javascript">
<!--
  function BBwriteAll(data) {
    bboutwin = window.open("", "bumblebeeOutput", "");
    bboutwin.document.write(unescape(data));
    bboutwin.document.close();
  }
  
  data = "'.$enchtml.'";
  
  BBwriteAll(data);
  
//-->
</script><a href="javascript:BBwriteAll(data)">Open Window</a>';
    return $jsbuf;
  }


} // class HTMLExport

?> 
