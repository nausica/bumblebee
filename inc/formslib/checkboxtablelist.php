<?php
/**
* a table of checkboxes for different options
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: checkboxtablelist.php,v 1.8 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage FormsLibrary
*/

/** parent object */
require_once 'choicelist.php';
/** uses checkbox objects */
require_once 'checkbox.php';
/** uses textfield objects */
require_once 'textfield.php';

/**
* a table of checkboxes for different options
*
* @package    Bumblebee
* @subpackage FormsLibrary
*/
class CheckBoxTableList extends ChoiceList {
  /** @var integer  number of columns in the table  */
  var $numcols    = '';
  /** @var integer  number of extra columns to be added to the table  */
  var $numExtraInfoCols = '';
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
  /** @var array    list of checkboxes (each checkbox added creates a new column) */
  var $checkboxes;
  /** @var Field    a hidden field that takes a different user-supplied value for each row in the table */
  var $followHidden;
  /** @var string   the key in $data used to populated the $this->followHidden field */
  var $followHiddenField;
  /** @var TextField  a hidden field that takes a programatically-generated value for each row in the table */
  var $hidden;
  /** @var array    list of strings to be included in the footer of the table */
  var $footer;
  /** @var boolean  generate select/deselect links at the bottom of each column */
  var $includeSelectAll = false;

  /**
  *  Create a new AnchorTableList
  *
  * @param string $name   the name of the field (db name, and html field name
  * @param string $description  used in the html title of the list
  * @param integer $numExtraInfoCols (optional) number of *extra* columns in the table
  */
  function CheckBoxTableList($name, $description='', $numExtraInfoCols=-1) {
    $this->ChoiceList($name, $description);
    $this->numExtraInfoCols = $numExtraInfoCols;
    $this->checkboxes = array();
    $this->footer = array();
    $this->hidden = new TextField('row');
    $this->hidden->hidden = 1;
  }

  /**
  *  Accessor method to set the table column headings
  *
  * @param array   new headings to use for the table
  */
  function setTableHeadings($headings) {
    $this->tableHeadings = $headings;
  }
  
  /**
  * Add a new column of checkboxes to the table
  *
  * @param CheckBox $cb checkbox Field to add
  */
  function addCheckBox($cb) {
    $this->checkboxes[] = $cb;
    $this->numcols = count($this->checkboxes);
  }
  
  /**
  * Add an extra hidden field to each row to record further details about what the selections mean
  *
  * @param Field $h  hidden field to replicate throughout table
  * @param string  $follow    index in the $data provided to CheckBoxTableList::format to use for the field's value
  */
  function addFollowHidden($h, $follow='id') {
    $h->hidden = 1;
    $this->followHidden = $h;
    $this->followHiddenField = $follow;
  }

  /**
  * Toggle the creation of a footer with javascript select all/deselect all buttons
  *
  * @param boolean  $bool   include the footer
  */
  function addSelectAllFooter($bool) {
    $this->includeSelectAll = $bool;
  }
  
  /**
  * Include an additional footer in the table
  *
  * @param array $f  array of fields to be included at the bottom of the table
  */
  function addFooter($f) {
    $this->footer = $f;
  }

  function format($data, $j, $numcols) {
    $aclass  = (isset($this->aclass) ? " class='$this->aclass'" : '');
    $trclass  = (isset($this->trclass) ? " class='$this->trclass'" : '');
    $tdlclass  = (isset($this->tdlclass) ? " class='$this->tdlclass'" : '');
    $tdrclass  = (isset($this->tdrclass) ? " class='$this->tdrclass'" : '');

    $namebase = $this->name.'-'.$j.'-';
    $fh = $this->followHidden;
    $fh->value = $data[$this->followHiddenField];
    $fh->namebase = $namebase;
    $h = $this->hidden;
    $h->value = $j;
    $h->namebase = $namebase;
    
    $t  = "<tr $trclass>"
         ."<td $tdlclass>";
    $t .= "<span $aclass>"
         .$this->formatter[0]->format($data)
         .'</span>';
    $t .= $fh->hidden() . $h->hidden();
    $t .= "</td>\n";
    for ($i=1; $i<=$this->numExtraInfoCols; $i++) {
      $t .= "<td $tdrclass>"
           .$this->formatter[$i]->format($data);
      $t .= "</td>";
    }
    
    for ($i=0; $i<$this->numcols; $i++) {
      $cb = $this->checkboxes[$i];
      $cb->namebase = $namebase;
      $t .= '<td>'.$cb->selectable().'</td>';
    }
    for ($i=0; $i<=$numcols; $i++) {
      $t .= '<th></th>';
    }
    $t .= "</tr>\n";
    return $t;
  }

  function display() {
    $tableclass = (isset($this->tableclass) ? " class='$this->tableclass'" : "");
    $t  = "<table title='$this->description' $tableclass>\n";
    $t .= $this->displayInTable($this->numcols);
    $t .= "</table>\n";
    return $t;
  }

  
  function displayInTable($numCols) {
    $totalCols = 1 + $this->numExtraInfoCols + $this->numcols;
    $t='';
    if ($this->numExtraInfoCols = -1) {
      $this->numExtraInfoCols = count($this->formatter)-1;
    }
    if (isset($this->tableHeadings) && is_array($this->tableHeadings)) {
      $t .= '<tr>';
      foreach ($this->tableHeadings as $heading) {
        $t .= "<th>$heading</th>";
      }
      $t .= "</tr>\n";
    }
    $t  .= '<tr>';
    for ($i=0; $i<=$this->numExtraInfoCols; $i++) {
      $t .= '<th></th>';
    }
    for ($i=0; $i<$this->numcols; $i++) {
      $t .= '<th>'.$this->checkboxes[$i]->longname.'</th>';
    }
    for ($i=$totalCols; $i<=$numCols; $i++) {
      $t .= '<th></th>';
    }
    $t .= '</tr>'."\n";    
    if (is_array($this->list->choicelist)) {
      for ($j=0; $j<count($this->list->choicelist); $j++) {
        $t .= $this->format($this->list->choicelist[$j], $j, $numCols - $totalCols);
      }
    }
    // SelectAll/DeselectAll footer
    if ($this->includeSelectAll) {
      $t .= '<tr>';
      for ($i=0; $i<=$this->numExtraInfoCols; $i++) {
        $t .= '<td></td>';
      }
      for ($i=0; $i<$this->numcols; $i++) {
          $t .= '<td>'.$this->_getSelectAllFooter($i).'</td>';
      }
      for ($i=$totalCols; $i<=$numCols; $i++) {
        $t .= '<td></td>';
      }
      $t .= '</tr>'."\n";    
    }
    if (is_array($this->footer) && count($this->footer)) {
      $t .= '<tr>';
      for ($i=0; $i<=$this->numExtraInfoCols; $i++) {
        $t .= '<td></td>';
      }
      for ($i=0; $i<$this->numcols; $i++) {
          $t .= '<td>'.sprintf($this->footer[$i], $i,
                                              $i).'</td>';
      }
      for ($i=$totalCols; $i<=$numCols; $i++) {
        $t .= '<td></td>';
      }
      $t .= '</tr>'."\n";    
    }
    return $t;
  }  

  /**
  * create a pair of select/deselect all quick buttons or links
  *
  * @uses jsfunctions.php
  * @param integer $col  the column number to run the quick select sequence on.
  * @return string html for links
  * @todo include interface for groups of instruments.
  */
  function _getSelectAllFooter($col) {
    return '(<a href="#" onclick="return deselectsome(\''.$this->name.'-\', '.$col.' ,'.$this->numcols.');">deselect all</a>)<br />'
        .'(<a href="#" onclick="return selectsome(\''.$this->name.'-\', '.$col.' ,'.$this->numcols.');">select all</a>)';
  }
  
} // class CheckBoxTableList


?> 
