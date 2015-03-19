<?php
/**
* User class name
*
* @author    Stuart Prescott
* @copyright  Copyright Stuart Prescott
* @license    http://opensource.org/licenses/gpl-license.php GNU Public License
* @version    $Id: userclass.php,v 1.4 2006/01/06 10:07:21 stuart Exp $
* @package    Bumblebee
* @subpackage DBObjects
*/

/** parent object */
require_once 'inc/formslib/dbrow.php';
require_once 'inc/formslib/textfield.php';
require_once 'inc/formslib/idfield.php';

/**
* User class name
*
* @package    Bumblebee
* @subpackage DBObjects
*/
class UserClass extends DBRow {
  
  function UserClass($id) {
    //$this->DEBUG=10;
    $this->DBRow('userclass', $id);
    $this->deleteFromTable = 0;
    $this->editable = 1;
    $f = new IdField('id', 'Class ID');
    $f->editable = 0;
    $this->addElement($f);
    $f = new TextField('name', 'User Class name');
    $attrs = array('size' => '24');
    $f->setAttr($attrs);
    $f->required = 1;
    $f->isValidTest = 'is_nonempty_string';
    $this->addElement($f);
    $this->fill($id);
    $this->dumpheader = 'UserClass object';
  }

  function display() {
    return $this->displayAsTable();
  }

} //class Group
