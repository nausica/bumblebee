<?php
/**
* Group editing object
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: group.php,v 1.15 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/

/** parent object */
require_once 'inc/formslib/dbrow.php';
require_once 'inc/formslib/idfield.php';
require_once 'inc/formslib/textfield.php';

/**
* Group editing object
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class Group extends DBRow {
  
  function Group($id) {
    //$this->DEBUG=10;
    $this->DBRow('groups', $id);
    $this->editable = 1;
    //$this->use2StepSync = 1;
    $this->deleteFromTable = 0;
    $f = new IdField('id', 'Group ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'Addressee name');
    $attrs = array('size' => '48');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $f = new TextField('longname', 'Group name');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $f = new TextField('addr1', 'Address 1');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('addr2', 'Address 2');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('suburb', 'Suburb');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('state', 'State');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('code', 'Postcode');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('country', 'Country');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('email', 'email');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_email_format';
    $this->addElement($f);
    $f = new TextField('fax', 'Fax');
    $f->setAttr($attrs);
    $this->addElement($f);
    $f = new TextField('account', 'Account code');
    $f->setAttr($attrs);
    $this->addElement($f);
    $this->fill($id);
    $this->dumpheader = 'Group object';
  }

  function display() {
    return $this->displayAsTable();
  }

} //class Group
