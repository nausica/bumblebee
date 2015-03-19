<?php
/**
* Anchor list similar to AnchorList, but this time in a table not dot points
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: anchortablelist.php,v 1.11 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** anchorlist parent object */
require_once 'anchorlist.php';

/**
* Anchor list similar to AnchorList, but this time in a table not dot points
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class AnchorTableList extends AnchorList {
  /** @var integer  number of columns in the table*/
  var $numcols    = '';
  /** @var string   html/css class for each row in the table  */
  var $trclass    = 'itemrow';
  /** @var string   html/css class for left-side table cell */
  var $tdlclass   = 'itemL';
  /** @var string   html/css class for right-side table cell */
  var $tdrclass   = 'itemR';
  /** @var string   html/css class for entire table */
  var $tableclass = 'selectlist';
  /** @var array    list of table headings to be put at the top of the table */
  var $tableHeading;

  /**
  *  Create a new AnchorTableList
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $description  used in the html title of the list
  * @param integer $numcols (optional) number of columns in the table
  */
  function AnchorTableList($name, $description='', $numcols=2) {
    $this->AnchorList($name, $description);
    $this->numcols = $numcols;
  }

  /**
  *  Accessor method to set the table column headings
  *
  * @param array   new headings to use for the table
  */
  function setTableHeadings($headings) {
    $this->tableHeadings = $headings;
  }

  function format($data) {
    //preDump($this);
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
    $trclass  = (isset($this->trclass) ? " class='$this->trclass'" : '');
    $tdlclass  = (isset($this->tdlclass) ? " class='$this->tdlclass'" : '');
    $tdrclass  = (isset($this->tdrclass) ? " class='$this->tdrclass'" : '');
    $t  = "<tr $trclass>"
         ."<td $tdlclass>";
    $t .= "<a href='".str_replace('__id__', $data[$this->formatid], $this->hrefbase)."'$aclass>"
         .$this->formatter[0]->format($data)
         ."</a>";
    $t .= "</td>\n";
    for ($i=2; $i<=$this->numcols; $i++) {
      $t .= "<td $tdrclass>"
           .$this->formatter[$i-1]->format($data);
      $t .= "</td>";
    }
    $t .= "</tr>\n";
    return $t;
  }

  function display() {
    $tableclass = (isset($this->tableclass) ? " class='$this->tableclass'" : '');
    $t  = "<table title='$this->description' $tableclass>\n";
    if (isset($this->tableHeadings) && is_array($this->tableHeadings)) {
      $t .= '<tr>';
      foreach ($this->tableHeadings as $heading) {
        $t .= "<th>$heading</th>";
      }
      $t .= "</tr>\n";
    }
    if (is_array($this->list->choicelist)) {
      foreach ($this->list->choicelist as $v) {
        $t .= $this->format($v);
      }
    }
    $t .= "</table>\n";
    return $t;
  }

} // class AnchorList


?> 
