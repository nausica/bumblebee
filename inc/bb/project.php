<?php
/**
* Project object (extends dbo), with extra customisations for other links
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: project.php,v 1.21 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/

/** parent object */
require_once 'inc/formslib/dbrow.php';
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/radiolist.php';
require_once 'inc/formslib/droplist.php';
require_once 'inc/formslib/joindata.php';

/**
* Project object (extends dbo), with extra customisations for other links
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class Project extends DBRow {
  
  function Project($id) {
    $this->DBRow('projects', $id);
    //$this->DEBUG=10;
    $this->editable = 1;
    $this->use2StepSync = 1;
    $this->deleteFromTable = 0;
    $f = new IdField('id', 'Project ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'Name');
    $attrs = array('size' => '48');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('longname', 'Description');
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new RadioList('defaultclass', 'Default charging band');
    $f->connectDB('userclass', array('id', 'name'));
    $f->setFormat('id', '%s', array('name'));
    $newchargename = new TextField('name','');
    $newchargename->namebase = 'newcharge-';
    $newchargename->setAttr(array('size' => 24));
    $newchargename->isValidTest = 'is_nonempty_string';
    $newchargename->suppressValidation = 0;
    $f->list->append(array('-1','Create new: '), $newchargename);
    $f->setAttr($attrs);
    $f->required = 1;
    $f->extendable = 1;
    $f->editable = 1;
    $f->isValidTest = 'is_valid_radiochoice';
    $this->addElement($f);
    $f = new JoinData('projectgroups',
                       'projectid', $this->id, 
                       'groups', 'Group membership (%)');
    $groupfield = new DropList('groupid', 'Group');
    $groupfield->connectDB('groups', array('id', 'name', 'longname'));
    $groupfield->prepend(array('0','(none)', 'no selection'));
    $groupfield->setDefault(0);
    $groupfield->setFormat('id', '%s', array('name'), ' (%30.30s)', array('longname'));
    $f->addElement($groupfield);
    $percentfield = new TextField('grouppc', '');
    $percentfield->isValidTest = 'is_number';
    $f->addElement($percentfield, 'sum_is_100');
    $f->joinSetup('groupid', array('total' => 3));
    $f->colspan = 2;
    $this->addElement($f);
    $this->fill();
    $this->dumpheader = 'Project object';
  }

  function display() {
    return $this->displayAsTable();
  }


}  //class Project
