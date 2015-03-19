<?php
/**
* email lists for instrument users
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: emaillist.php,v 1.21 2006/01/06 10:07:20 stuart Exp $
* @package    Bumblebee
* @subpackage Actions
*/

/** TextBox object */
require_once 'inc/formslib/textfield.php';
/** CheckBox object */
require_once 'inc/formslib/checkbox.php';
/** CheckBoxTableList object */
require_once 'inc/formslib/checkboxtablelist.php';
/** db interrogation list object */
require_once 'inc/formslib/dblist.php';
/** form object */
require_once 'inc/formslib/nondbrow.php';
/** export status codes and formats */
require_once 'inc/export/exporttypes.php';
/** parent object */
require_once 'inc/actions/actionaction.php';


/**
* Generate a list of email addresses for users of particular instruments
*  
* An Action to find out what instruments the lists should be prepared for 
* and then return the email list.
* Designed to be use as a per instrument "announce" list.
*
* @todo should this class should be split some more, with some of the details abstracted?
* @package    Bumblebee
* @subpackage Actions
*/
class ActionEmailList extends ActionAction {
  /**
  * forces SQL errors to be fatal
  * @var    boolean
  */
  var $fatal_sql = 1;

  /**
  * Initialising the class 
  * 
  * @param  BumbleBeeAuth $auth  Authorisation object
  * @param  array $pdata   extra state data from the call path
  * @return void nothing
  */
  function ActionEmailList($auth, $pdata) {
    parent::ActionAction($auth, $pdata);
    $this->mungeInputData();
  }

  function go() {
    if (! isset($this->PD['selectlist'])) {
      $this->selectLists();
    } else {
      $this->returnLists();
    }
  }

  /**
  * Generate an HTML form for the user to select which email lists should be used
  *
  * - do DB look-ups on what instruments exist
  * - construct a table of checkboxes to allow the user to select the lists
  * 
  * @return void nothing
  */
  function selectLists() {
    $selectRow = new nonDBRow('listselect', 'Select email lists', 
              'Select which email lists you want to return');
    $select = new CheckBoxTableList('Instrument', 'Select which instrument to view');
    $hidden = new TextField('instrument');
    $select->addFollowHidden($hidden);
    $announce = new CheckBox('announce', 'Announce');
    $select->addCheckBox($announce);
    $unbook = new CheckBox('unbook', 'Unbook');
    $select->addCheckBox($unbook);
    //$select->numSpareCols = 1;
    $select->connectDB('instruments', array('id', 'name', 'longname'));
    $select->setFormat('id', '%s', array('name'), " %50.50s", array('longname'));
    $select->addSelectAllFooter(true);
    $selectRow->addElement($select);
    $separator = new TextField('separator', 'Value separator',
                'Separates the returned values so you can paste them into your email client');
    $separator->defaultValue = ',';
    $separator->setattr(array('size' => '2'));
    $selectRow->addElement($separator);
    echo $selectRow->displayInTable(4);
    echo '<input type="hidden" name="selectlist" value="1" />';
    echo '<input type="submit" name="submit" value="Select" />';
  }


  /**
  * Generate the email lists
  *
  * - work out which instruments the user has requested for inclusion
  * - construct an SQL query to get the email list
  * - return the formatted data to the user
  * 
  * @return void nothing
  */  
  function returnLists() {
    $where = array('0');  //start the WHERE with 0 in case nothing was selected (always get valid SQL)
    $namebase = 'Instrument-';
    for ($j=0; isset($this->PD[$namebase.$j.'-row']); $j++) {
      $instr = issetSet($this->PD,$namebase.$j.'-instrument');
      //echo "$j ($instr) => ($unbook, $announce)<br />";
      if (issetSet($this->PD,$namebase.$j.'-announce')) {
        $where[] ='(permissions.instrid='.qw($instr).' AND permissions.announce='.qw(1).')' ;
      }
      if (issetSet($this->PD,$namebase.$j.'-unbook')) {
        $where[] = '(permissions.instrid='.qw($instr).' AND permissions.unbook='.qw(1).')' ;
      }
    }
    #echo "Gathering email addresses: $q<br />";
    $fields = array(new sqlFieldName('email', 'Email Address'));
    $restriction = 'users.deleted<>1 AND (' .join($where, ' OR '). ')';  //don't return deleted users
    $list = new DBList('permissions', $fields, $restriction, true);
    $list->join[] = (array('table' => 'users', 'condition' => 'users.id=permissions.userid'));
    $list->setFormat('%s', array('email'));
    $list->fill();
    if (count($list->data) == 0) {
      echo '<p>No email addresses found</p>';
    } else {
      $list->formatList();
      $this->PD['separator'] = stripslashes($this->PD['separator']);
      if ($this->PD['separator'] == '\n') {
        $this->PD['separator'] = "\n";
      } elseif ($this->PD['separator'] == '\t') {
        $this->PD['separator'] = "\t";
      }
      echo join($list->formatdata, xssqw($this->PD['separator']).'<br />');
    }
  }

}
?> 
